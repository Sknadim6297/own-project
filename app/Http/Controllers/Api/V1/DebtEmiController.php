<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmRecorder;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Mobile Debt & EMI Calculator — same PfmCalculator maths as the website tool.
 * Guests may calculate; save / history / delete require Sanctum auth.
 */
class DebtEmiController extends Controller
{
    private const TOOL_KEY = 'debt-emi-calculator';

    public function options(): JsonResponse
    {
        $meta = PfmCatalog::tool(self::TOOL_KEY);

        return response()->json([
            'status' => 'success',
            'open' => $this->toolIsOpen(),
            'login_required_to_save' => true,
            'tool_key' => self::TOOL_KEY,
            'name' => $meta['name'] ?? 'Debt & EMI Calculator',
            'description' => $meta['description'] ?? '',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'limits' => [
                'principal_min' => 0.01,
                'down_payment_min' => 0,
                'rate_min' => 0,
                'rate_max' => 50,
                'tenure_years_min' => 0.01,
                'tenure_years_max' => 100,
                'extra_payment_min' => 0,
            ],
            'fields' => [
                'principal' => 'Total purchase amount (legacy key; financed = principal − down_payment).',
                'down_payment' => 'Optional upfront payment. Omit or 0 for legacy behaviour.',
            ],
            'disclaimer' => 'This is an estimate. Actual EMI may vary based on your lender’s terms.',
        ]);
    }

    public function calculate(Request $request, PfmCalculator $calculator): JsonResponse
    {
        $this->assertOpen();

        $data = $request->validate([
            'principal' => ['required', 'numeric'],
            'down_payment' => ['sometimes', 'nullable', 'numeric'],
            'annual_rate_percent' => ['required', 'numeric'],
            'tenure_years' => ['required', 'numeric'],
            'extra_payment' => ['sometimes', 'nullable', 'numeric'],
            'completion_key' => ['sometimes', 'nullable', 'uuid'],
        ]);

        try {
            $payload = $calculator->calculate(self::TOOL_KEY, [
                'principal' => $data['principal'],
                'down_payment' => $data['down_payment'] ?? 0,
                'annual_rate_percent' => $data['annual_rate_percent'],
                'tenure_years' => $data['tenure_years'],
                'extra_payment' => $data['extra_payment'] ?? 0,
            ]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['calculate' => $e->getMessage()]);
        }

        $completionKey = isset($data['completion_key']) && is_string($data['completion_key'])
            ? strtolower($data['completion_key'])
            : (string) Str::uuid();

        return response()->json([
            'status' => 'success',
            'completion_key' => $completionKey,
            'inputs' => $payload['normalized'],
            'result' => $this->presentResult($payload['result'], $payload['normalized']),
        ]);
    }

    public function save(Request $request, PfmCalculator $calculator, PfmRecorder $recorder): JsonResponse
    {
        $user = $this->customer($request);
        $this->assertOpen();
        Auth::guard('user')->setUser($user);

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'principal' => ['required', 'numeric'],
            'down_payment' => ['sometimes', 'nullable', 'numeric'],
            'annual_rate_percent' => ['required', 'numeric'],
            'tenure_years' => ['required', 'numeric'],
            'extra_payment' => ['sometimes', 'nullable', 'numeric'],
            'as_draft' => ['sometimes', 'boolean'],
        ]);

        try {
            $payload = $calculator->calculate(self::TOOL_KEY, [
                'principal' => $data['principal'],
                'down_payment' => $data['down_payment'] ?? 0,
                'annual_rate_percent' => $data['annual_rate_percent'],
                'tenure_years' => $data['tenure_years'],
                'extra_payment' => $data['extra_payment'] ?? 0,
            ]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['calculate' => $e->getMessage()]);
        }

        $record = $recorder->storePayload(
            (int) $user->id,
            self::TOOL_KEY,
            strtolower($data['completion_key']),
            $payload['result'],
            $payload['normalized'],
            $request->boolean('as_draft')
        );

        return response()->json([
            'status' => 'success',
            'created' => $record->wasRecentlyCreated,
            'calculation' => $this->presentRecord($record),
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $this->customer($request);

        $paginator = PfmCalculation::query()
            ->where('user_id', $user->id)
            ->where('tool_key', self::TOOL_KEY)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10);

        $items = collect($paginator->items())->map(fn (PfmCalculation $row) => $this->presentHistoryItem($row))->values();

        return response()->json([
            'status' => 'success',
            'calculations' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->customer($request);
        $record = PfmCalculation::query()
            ->where('user_id', $user->id)
            ->where('tool_key', self::TOOL_KEY)
            ->find($id);

        if ($record === null) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => 'Calculation not found.',
            ], 404));
        }

        return response()->json([
            'status' => 'success',
            'calculation' => $this->presentRecord($record),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->customer($request);
        $record = PfmCalculation::query()
            ->where('user_id', $user->id)
            ->where('tool_key', self::TOOL_KEY)
            ->find($id);

        if ($record === null) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => 'Calculation not found.',
            ], 404));
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Saved calculation deleted.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    private function presentResult(array $result, array $inputs): array
    {
        $purchaseAmount = (float) ($result['purchase_amount'] ?? $inputs['principal'] ?? 0);
        $downPayment = (float) ($result['down_payment'] ?? $inputs['down_payment'] ?? 0);
        // breakdown.principal = financed loan amount (equals purchase when down_payment is 0 / omitted).
        $principal = (float) ($result['financed_principal'] ?? max(0, $purchaseAmount - $downPayment));
        $interest = (float) ($result['standard_interest'] ?? 0);
        $total = max($principal + $interest, 0.01);

        return [
            'tool_key' => self::TOOL_KEY,
            'content_version' => $result['content_version'] ?? PfmCatalog::CONTENT_VERSION,
            'summary' => $result['summary'] ?? '',
            'emi' => (float) ($result['emi'] ?? 0),
            'purchase_amount' => $purchaseAmount,
            'down_payment' => $downPayment,
            'financed_principal' => $principal,
            'no_loan' => (bool) ($result['no_loan'] ?? ($principal <= 0)),
            'loan_repayment_total' => (float) ($result['loan_repayment_total'] ?? $result['standard_total'] ?? ($principal + $interest)),
            'total_purchase_outflow' => (float) ($result['total_purchase_outflow'] ?? ($downPayment + ($result['standard_total'] ?? ($principal + $interest)))),
            'standard_interest' => $interest,
            'standard_total' => (float) ($result['standard_total'] ?? ($principal + $interest)),
            'standard_months' => (int) ($result['standard_months'] ?? 0),
            'accelerated_interest' => (float) ($result['accelerated_interest'] ?? 0),
            'accelerated_months' => (int) ($result['accelerated_months'] ?? 0),
            'accelerated_total' => (float) ($result['accelerated_total'] ?? 0),
            'interest_saved' => (float) ($result['interest_saved'] ?? 0),
            'months_saved' => (int) ($result['months_saved'] ?? 0),
            'breakdown' => [
                'principal' => $principal,
                'interest' => $interest,
                'principal_percent' => round(($principal / $total) * 100, 1),
                'interest_percent' => round(($interest / $total) * 100, 1),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRecord(PfmCalculation $record): array
    {
        $inputs = is_array($record->inputs) ? $record->inputs : [];
        $outputs = is_array($record->outputs) ? $record->outputs : [];

        return [
            'id' => (int) $record->id,
            'completion_key' => $record->completion_key,
            'title' => $record->title,
            'is_draft' => (bool) $record->is_draft,
            'inputs' => $inputs,
            'result' => $this->presentResult($outputs, $inputs),
            'created_at' => optional($record->created_at)?->toIso8601String(),
            'updated_at' => optional($record->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentHistoryItem(PfmCalculation $record): array
    {
        $inputs = is_array($record->inputs) ? $record->inputs : [];
        $outputs = is_array($record->outputs) ? $record->outputs : [];

        return [
            'id' => (int) $record->id,
            'title' => $record->title,
            'is_draft' => (bool) $record->is_draft,
            'emi' => (float) ($outputs['emi'] ?? 0),
            'principal' => (float) ($inputs['principal'] ?? 0),
            'annual_rate_percent' => (float) ($inputs['annual_rate_percent'] ?? 0),
            'tenure_years' => (float) ($inputs['tenure_years'] ?? 0),
            'interest_saved' => (float) ($outputs['interest_saved'] ?? 0),
            'months_saved' => (int) ($outputs['months_saved'] ?? 0),
            'summary' => (string) ($record->summary ?? ''),
            'updated_at' => optional($record->updated_at)?->toIso8601String(),
        ];
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function toolIsOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', self::TOOL_KEY)->first();
        } catch (\Throwable) {
            return true;
        }

        if (! $tool) {
            return true;
        }

        return $tool->canLaunch();
    }

    private function assertOpen(): void
    {
        if (! $this->toolIsOpen()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => 'This tool is not available right now.',
            ], 403));
        }
    }
}
