<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\BookingSummaryMail;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingTimedate;
use App\Models\McqAnswer;
use App\Models\PaymentFailureLog;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Rate;
use App\Models\ServiceMCQ;
use App\Models\User;
use App\Services\Mobile\ExpertBookingCatalog;
use App\Services\Terms\TermsAcceptanceService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api;

/**
 * Mobile consultation booking — the website flow (session-store → booking summary →
 * initiate-payment → verify-payment → MCQ) without PHP sessions. The server prices the plan
 * itself; the app never sends an amount. Pending orders live in the cache, keyed by the
 * Razorpay order id, and are bound to the customer who created them.
 */
class ExpertBookingController extends Controller
{
    private const ORDER_TTL_MINUTES = 120;

    public function __construct(
        private readonly ExpertBookingCatalog $catalog,
        private readonly TermsAcceptanceService $terms,
    ) {
    }

    /** Booking summary page: plan, sessions and the GST breakdown, priced on the server. */
    public function checkout(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if ($blocked = $this->termsBlock($user)) {
            return $blocked;
        }
        [$professional, $rate, $picks] = $this->selection($request);

        return response()->json([
            'status' => 'success',
            'checkout' => $this->summary($professional, $rate, $picks) + [
                'customer' => ['name' => $user->name, 'email' => $user->email, 'phone' => $this->phoneOf($user)],
            ],
        ]);
    }

    /** Website initiatePayment: creates the Razorpay order for the server-computed total. */
    public function order(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if ($blocked = $this->termsBlock($user)) {
            return $blocked;
        }
        $request->validate(['phone' => ['required', 'string', 'regex:/^\d{10}$/']], [
            'phone.regex' => 'Enter a 10-digit mobile number.',
        ]);
        [$professional, $rate, $picks] = $this->selection($request);
        $summary = $this->summary($professional, $rate, $picks);

        $key = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');
        if (! $key || ! $secret) {
            Log::error('Mobile booking: Razorpay configuration missing');

            return response()->json([
                'status' => 'error',
                'message' => 'Payments are not available right now. Please try again later.',
            ], 503);
        }

        $paise = (int) round($summary['amounts']['total'] * 100);
        if (! $summary['payable']) {
            return response()->json([
                'status' => 'error',
                'message' => "This plan can't be booked online yet. Please choose another plan.",
            ], 422);
        }
        try {
            $order = (new Api($key, $secret))->order->create([
                'amount' => $paise,
                'currency' => 'INR',
                'payment_capture' => 1,
                'notes' => ['source' => 'mobile_app', 'professional_id' => (string) $professional->id, 'user_id' => (string) $user->id],
            ]);
        } catch (\Throwable $e) {
            Log::error('Mobile booking: Razorpay order failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => "We couldn't start the payment. Please try again.",
            ], 502);
        }

        Cache::put($this->orderKey($order->id), [
            'user_id' => $user->id,
            'professional_id' => $professional->id,
            'rate_id' => $rate->id,
            'plan_type' => $summary['plan_type'],
            'bookings' => $picks,
            'amounts' => $summary['amounts'],
            'service_name' => $summary['service_name'],
            'phone' => $request->input('phone'),
            'paise' => $paise,
        ], now()->addMinutes(self::ORDER_TTL_MINUTES));

        return response()->json([
            'status' => 'success',
            'order' => [
                'id' => $order->id,
                'amount' => $paise,
                'currency' => 'INR',
                'key' => $key,
                'name' => 'YuktiOne',
                'description' => "{$summary['plan_label']} session with {$professional->name}",
                'prefill' => ['name' => $user->name, 'email' => $user->email, 'contact' => $request->input('phone')],
            ],
        ]);
    }

    /** Website verifyPayment: checks the signature, then saves the booking exactly like the website. */
    public function verify(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:64'],
            'razorpay_payment_id' => ['required', 'string', 'max:64'],
            'razorpay_signature' => ['required', 'string', 'max:256'],
        ]);

        $existing = Booking::where('payment_id', $data['razorpay_payment_id'])->where('user_id', $user->id)->first();
        if ($existing) {
            return $this->confirmed($existing);
        }

        $intent = Cache::get($this->orderKey($data['razorpay_order_id']));
        if (! is_array($intent) || (int) $intent['user_id'] !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => "We couldn't find this payment's booking. If money was deducted, please contact support with your payment ID.",
            ], 404);
        }

        try {
            (new Api(config('services.razorpay.key'), config('services.razorpay.secret')))
                ->utility->verifyPaymentSignature($data);
        } catch (\Throwable $e) {
            Log::warning('Mobile booking: signature check failed', ['user_id' => $user->id, 'order' => $data['razorpay_order_id']]);

            return response()->json([
                'status' => 'error',
                'message' => "We couldn't verify this payment. If money was deducted, please contact support with your payment ID.",
            ], 422);
        }

        $lock = Cache::lock('v1-booking-verify:'.$data['razorpay_order_id'], 30);
        if (! $lock->get()) {
            return response()->json(['status' => 'error', 'message' => 'This payment is already being confirmed. Please wait a moment.'], 409);
        }

        try {
            $existing = Booking::where('payment_id', $data['razorpay_payment_id'])->where('user_id', $user->id)->first();
            if ($existing) {
                return $this->confirmed($existing);
            }

            $professional = Professional::with('profile')->findOrFail($intent['professional_id']);
            foreach ($intent['bookings'] as $entry) {
                // Paid already, so the booking is still saved; the team is alerted to resolve the clash.
                if (BookingTimedate::isSlotBooked($professional->id, $entry['date'], $entry['time_slot'])) {
                    Log::error('Mobile booking: slot taken between order and payment', ['order' => $data['razorpay_order_id'], 'slot' => $entry]);
                }
            }

            $booking = DB::transaction(function () use ($intent, $user, $professional, $data) {
                $amounts = $intent['amounts'];
                $first = Carbon::parse($intent['bookings'][0]['date']);

                $booking = new Booking();
                $booking->user_id = $user->id;
                $booking->professional_id = $professional->id;
                $booking->plan_type = $intent['plan_type'];
                $booking->customer_phone = $intent['phone'];
                $booking->service_name = $intent['service_name'] ?? 'N/A';
                $booking->session_type = 'online';
                $booking->customer_name = $user->name;
                $booking->customer_email = $user->email;
                $booking->amount = $amounts['total'];
                $booking->base_amount = $amounts['base_amount'];
                $booking->cgst_amount = $amounts['cgst'];
                $booking->sgst_amount = $amounts['sgst'];
                $booking->igst_amount = $amounts['igst'];
                $booking->payment_id = $data['razorpay_payment_id'];
                $booking->payment_status = 'paid';
                $booking->month = $first->format('M');
                $booking->booking_date = $first->format('Y-m-d');
                $booking->days = json_encode(array_map(fn ($b) => Carbon::parse($b['date'])->day, $intent['bookings']));
                $booking->time_slot = json_encode(array_column($intent['bookings'], 'time_slot'));
                $booking->save();

                foreach ($intent['bookings'] as $entry) {
                    BookingTimedate::create([
                        'booking_id' => $booking->id,
                        'date' => Carbon::parse($entry['date'])->format('Y-m-d'),
                        'time_slot' => $entry['time_slot'],
                        'status' => 'pending',
                    ]);
                }

                return $booking;
            });

            Cache::forget($this->orderKey($data['razorpay_order_id']));
            $this->sendEmails($booking, $professional);

            return $this->confirmed($booking);
        } finally {
            $lock->release();
        }
    }

    /** Website paymentFailed: logs the failure for the support team. */
    public function paymentFailed(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:64'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:64'],
            'error_code' => ['nullable', 'string', 'max:120'],
            'error_description' => ['nullable', 'string', 'max:500'],
            'error_source' => ['nullable', 'string', 'max:120'],
            'error_step' => ['nullable', 'string', 'max:120'],
            'error_reason' => ['nullable', 'string', 'max:120'],
        ]);

        $intent = Cache::get($this->orderKey($data['razorpay_order_id']));
        if (! is_array($intent) || (int) $intent['user_id'] !== (int) $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        try {
            PaymentFailureLog::create([
                'user_id' => $user->id,
                'professional_id' => $intent['professional_id'],
                'booking_type' => 'appointment',
                'amount' => $intent['paise'],
                'razorpay_payment_id' => $data['razorpay_payment_id'] ?? null,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'error_code' => $data['error_code'] ?? null,
                'error_description' => $data['error_description'] ?? null,
                'error_source' => $data['error_source'] ?? null,
                'error_step' => $data['error_step'] ?? null,
                'error_reason' => $data['error_reason'] ?? null,
                'phone' => $intent['phone'],
                'reference_id' => 'APT-FAIL-'.time().'-'.$user->id,
                'booking_data' => json_encode($intent),
            ]);
        } catch (\Throwable $e) {
            Log::error('Mobile booking: could not log payment failure', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    /** Website getMCQQuestions: the post-payment questionnaire for the expert's service. */
    public function questions(Request $request, int $booking): JsonResponse
    {
        $user = $this->customer($request);
        $owned = $this->ownedBooking($user, $booking);
        $serviceId = ProfessionalService::where('professional_id', $owned->professional_id)->value('service_id');

        $questions = $serviceId
            ? ServiceMCQ::getQuestionsForService($serviceId)->map(fn (ServiceMCQ $question) => [
                'id' => $question->id,
                'question' => strip_tags((string) $question->question),
                'type' => $question->question_type === 'text' ? 'text' : 'mcq',
                'options' => collect($question->formatted_options ?? [])
                    ->map(fn ($option) => trim(strip_tags((string) $option)))
                    ->filter()
                    ->values()
                    ->all(),
                'has_other' => (bool) $question->has_other_option,
            ])->values()->all()
            : [];

        return response()->json([
            'status' => 'success',
            'service_id' => $serviceId,
            'questions' => $questions,
        ]);
    }

    /** Website submitMCQAnswers, linked to the booking straight away. */
    public function answers(Request $request, int $booking): JsonResponse
    {
        $user = $this->customer($request);
        $owned = $this->ownedBooking($user, $booking);
        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1', 'max:50'],
            'answers.*.mcq_id' => ['required', 'integer'],
            'answers.*.answer' => ['required', 'string', 'max:1000'],
            'answers.*.other_answer' => ['nullable', 'string', 'max:1000'],
        ]);

        $serviceId = ProfessionalService::where('professional_id', $owned->professional_id)->value('service_id');
        if (! $serviceId) {
            return response()->json(['status' => 'error', 'message' => 'This booking has no questionnaire.'], 422);
        }
        $questions = ServiceMCQ::getQuestionsForService($serviceId)->keyBy('id');

        foreach ($data['answers'] as $answer) {
            $question = $questions->get((int) $answer['mcq_id']);
            if (! $question) {
                continue;
            }
            $other = isset($answer['other_answer']) ? trim($answer['other_answer']) : null;
            $selected = trim($answer['answer']);
            if ($selected === 'Other' && $other) {
                $selected = $other;
            }
            McqAnswer::updateOrCreate(
                ['user_id' => $user->id, 'service_mcq_id' => $question->id, 'service_id' => $serviceId],
                [
                    'question' => strip_tags((string) $question->question),
                    'selected_answer' => $selected,
                    'other_answer' => $other ?: null,
                    'booking_id' => $owned->id,
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Thank you, your answers were shared with your expert.']);
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            $this->fail(403, 'Please sign in with a customer account to book.');
        }

        return $user;
    }

    private function ownedBooking(User $user, int $booking): Booking
    {
        $owned = Booking::where('id', $booking)->where('user_id', $user->id)->first();
        if (! $owned) {
            $this->fail(404, 'Booking not found.');
        }

        return $owned;
    }

    private function fail(int $status, string $message): never
    {
        throw new HttpResponseException(response()->json(['status' => 'error', 'message' => $message], $status));
    }

    private function termsBlock(User $user): ?JsonResponse
    {
        if ($this->terms->hasAcceptedCurrent($user)) {
            return null;
        }

        return response()->json([
            'status' => 'terms_required',
            'message' => 'Please accept the latest Terms & Conditions on the YuktiOne website before booking.',
        ], 403);
    }

    /** @return array{0: Professional, 1: Rate, 2: list<array{date: string, time_slot: string}>} */
    private function selection(Request $request): array
    {
        $data = $request->validate([
            'professional_id' => ['required', 'integer'],
            'rate_id' => ['required', 'integer'],
            'slots' => ['required', 'array', 'min:1', 'max:31'],
            'slots.*.date' => ['required', 'date_format:Y-m-d'],
            'slots.*.time_slot' => ['required', 'string', 'max:40'],
        ]);

        $professional = Professional::with(['profile', 'professionalServices.service'])
            ->where('status', 'accepted')
            ->where('active', true)
            ->whereHas('profile')
            ->find($data['professional_id']);
        if (! $professional) {
            $this->fail(404, 'This expert is not taking bookings right now.');
        }

        $rate = $this->catalog->rates($professional->id)->firstWhere('id', (int) $data['rate_id']);
        if (! $rate instanceof Rate) {
            $this->fail(422, 'This plan is no longer available. Please choose another plan.');
        }

        return [$professional, $rate, $this->catalog->validatePicks($professional->id, $rate, $data['slots'])];
    }

    private function summary(Professional $professional, Rate $rate, array $picks): array
    {
        $service = $professional->professionalServices->first();
        $amounts = $this->catalog->amounts($rate);

        return [
            'professional' => [
                'id' => $professional->id,
                'name' => $professional->name,
                'specialization' => trim(strip_tags((string) ($professional->profile->specialization ?? ''))) ?: null,
            ],
            'service_name' => $service?->service?->name ?: ($service?->service_name ?: null),
            'rate_id' => $rate->id,
            'plan_type' => $this->catalog->planType($rate),
            'plan_label' => ucfirst((string) $rate->session_type),
            'num_sessions' => (int) $rate->num_sessions,
            'session_mode' => 'online',
            'bookings' => array_map(fn ($pick) => [
                'date' => $pick['date'],
                'time_slot' => BookingTimedate::normalizeTimeSlot($pick['time_slot']),
            ], $picks),
            'amounts' => $amounts,
            'currency' => 'INR',
            // Razorpay's smallest order is ₹1.
            'payable' => $amounts['total'] >= 1,
        ];
    }

    private function confirmed(Booking $booking): JsonResponse
    {
        $booking->loadMissing('timedates');
        $serviceId = ProfessionalService::where('professional_id', $booking->professional_id)->value('service_id');
        $hasQuestions = $serviceId ? ServiceMCQ::where('service_id', $serviceId)->whereIn('question_type', ['mcq', 'text'])->exists() : false;

        return response()->json([
            'status' => 'success',
            'message' => 'Payment successful and booking confirmed.',
            'booking' => [
                'id' => $booking->id,
                'professional' => ['id' => $booking->professional_id, 'name' => Professional::whereKey($booking->professional_id)->value('name')],
                'service_name' => $booking->service_name,
                'plan_type' => $booking->plan_type,
                'sessions' => $booking->timedates->map(fn ($slot) => [
                    'date' => substr((string) $slot->date, 0, 10),
                    'time_slot' => BookingTimedate::normalizeTimeSlot($slot->time_slot),
                ])->values()->all(),
                'amounts' => [
                    'base_amount' => (float) $booking->base_amount,
                    'cgst' => (float) $booking->cgst_amount,
                    'sgst' => (float) $booking->sgst_amount,
                    'igst' => (float) $booking->igst_amount,
                    'total' => (float) $booking->amount,
                ],
                'payment_id' => $booking->payment_id,
                'has_questions' => $hasQuestions,
            ],
        ]);
    }

    private function sendEmails(Booking $booking, Professional $professional): void
    {
        try {
            $timedates = BookingTimedate::where('booking_id', $booking->id)->get();
            Mail::to($booking->customer_email)->send(new BookingSummaryMail($booking, $professional, $timedates));
            if ($professional->email) {
                Mail::to($professional->email)->send(new BookingSummaryMail($booking, $professional, $timedates, 'professional'));
            }
            foreach (Admin::pluck('email')->filter() as $email) {
                Mail::to($email)->send(new BookingSummaryMail($booking, $professional, $timedates, 'admin'));
            }
        } catch (\Throwable $e) {
            Log::error('Mobile booking: summary emails failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }

    private function phoneOf(User $user): ?string
    {
        $phone = preg_replace('/\D/', '', (string) ($user->phone ?? ''));

        return strlen($phone) >= 10 ? substr($phone, -10) : null;
    }

    private function orderKey(string $orderId): string
    {
        return 'v1-booking-order:'.$orderId;
    }
}
