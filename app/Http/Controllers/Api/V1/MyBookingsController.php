<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Read-only booking history for the signed-in customer — same rows the website
 * shows on /user/my-bookings (Customer\MyBookingController). Never creates,
 * changes or pays for bookings, and never exposes payment gateway identifiers,
 * uploaded documents or professional-only remarks.
 */
class MyBookingsController extends Controller
{
    private const TIMEZONE = 'Asia/Kolkata';

    private const CLOSED_STATUSES = ['cancelled', 'canceled', 'failed', 'rejected', 'completed'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $data = $request->validate([
            'scope' => ['nullable', 'string', 'in:all,upcoming,past'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
        $scope = $data['scope'] ?? 'all';
        $perPage = (int) ($data['per_page'] ?? 10);
        $today = now(self::TIMEZONE)->toDateString();

        $base = fn () => Booking::query()->where('user_id', $user->id);

        $query = $base()
            ->with(['professional.profile', 'service', 'timedates' => fn ($q) => $q->orderBy('date')->orderBy('id')])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($scope === 'upcoming') {
            $query->where(fn (Builder $q) => $this->upcoming($q, $today));
        } elseif ($scope === 'past') {
            $query->whereNot(fn (Builder $q) => $this->upcoming($q, $today));
        }

        $page = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'status' => 'success',
            'bookings' => $page->getCollection()->map(fn (Booking $booking) => $this->payload($booking, $today))->values(),
            'counts' => [
                'all' => $base()->count(),
                'upcoming' => $base()->where(fn (Builder $q) => $this->upcoming($q, $today))->count(),
                'past' => $base()->whereNot(fn (Builder $q) => $this->upcoming($q, $today))->count(),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Upcoming = not closed, and either a pending session on/after today or,
     * for bookings without session rows, a booking date on/after today.
     */
    private function upcoming(Builder $query, string $today): void
    {
        $query->whereRaw('LOWER(status) NOT IN ('.implode(',', array_fill(0, count(self::CLOSED_STATUSES), '?')).')', self::CLOSED_STATUSES)
            ->where(function (Builder $dates) use ($today) {
                $dates->whereHas('timedates', fn ($t) => $t->where('date', '>=', $today)->where('status', 'pending'))
                    ->orWhere(function (Builder $single) use ($today) {
                        $single->whereDoesntHave('timedates')->where('booking_date', '>=', $today);
                    });
            });
    }

    private function payload(Booking $booking, string $today): array
    {
        $status = strtolower(trim((string) $booking->status));
        $sessions = $booking->timedates->map(fn ($slot) => [
            'id' => $slot->id,
            'date' => $this->date($slot->date),
            'time_slot' => $slot->time_slot,
            'status' => $slot->status,
        ])->values();

        $next = $sessions->first(fn ($slot) => $slot['status'] === 'pending' && $slot['date'] !== null && $slot['date'] >= $today);
        $bookingDate = $this->date($booking->booking_date);
        if (! $next && $sessions->isEmpty() && $bookingDate !== null && $bookingDate >= $today) {
            $next = ['date' => $bookingDate, 'time_slot' => $booking->time_slot];
        }

        $professional = $booking->professional;

        return [
            'id' => $booking->id,
            'service_name' => $booking->service_name ?: optional($booking->service)->name,
            'sub_service_name' => $booking->sub_service_name,
            'plan_type' => $booking->plan_type,
            'session_type' => $booking->session_type,
            'booking_date' => $bookingDate,
            'time_slot' => $booking->time_slot,
            'status' => $status !== '' ? $status : 'pending',
            'status_tone' => $this->tone($status),
            'payment_status' => $booking->payment_status,
            'paid_status' => $booking->paid_status,
            'amount' => $booking->amount !== null ? round((float) $booking->amount, 2) : null,
            'currency' => 'INR',
            'created_at' => optional($booking->created_at)->timezone(self::TIMEZONE)?->toIso8601String(),
            'professional' => $professional ? [
                'id' => $professional->id,
                'name' => $professional->name,
                'specialization' => $this->plain($professional->profile?->specialization),
                'photo_url' => $this->photoUrl($professional->profile?->photo),
            ] : null,
            'sessions' => $sessions,
            'sessions_total' => $sessions->count(),
            'sessions_completed' => $sessions->where('status', 'completed')->count(),
            'next_session' => $next ? ['date' => $next['date'], 'time_slot' => $next['time_slot']] : null,
        ];
    }

    /** Same mapping as the website status badge (components/tazen-v2/panel/status-badge). */
    private function tone(string $status): string
    {
        return match ($status) {
            'completed', 'paid', 'confirmed', 'approved', 'success', 'active' => 'success',
            'pending', 'processing', 'rescheduled' => 'warning',
            'cancelled', 'canceled', 'failed', 'rejected', 'error' => 'error',
            default => 'neutral',
        };
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 10);
    }

    private function plain(?string $value): ?string
    {
        $clean = trim(str_replace("\0", '', strip_tags((string) $value)));

        return $clean === '' ? null : $clean;
    }

    private function photoUrl(?string $path): ?string
    {
        $path = ltrim((string) $path, '/');
        if ($path === '') {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }
        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }
}
