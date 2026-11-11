<?php

namespace App\Services\YuktiRewards;

use App\Models\Professional;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralAttributionService
{
    public function __construct(
        private readonly ReferralCaptureStore $store,
        private readonly ReferralLinkBuilder $links,
    ) {
    }

    /**
     * Resolve a public referral landing, store first-touch capture for guests, return redirect URL.
     *
     * @return array{ok:bool, redirect:string, captured:bool, message:?string}
     */
    public function captureFromLanding(
        string $code,
        string $referralType,
        ?string $toolKey = null,
        ?int $professionalId = null,
    ): array {
        $fallback = route('home');

        // Authenticated customers are never attributed as new signups from a link open.
        if (Auth::guard('user')->check()) {
            $destination = $this->safeDestinationOnly($code, $referralType, $toolKey, $professionalId) ?? $fallback;

            return [
                'ok' => true,
                'redirect' => $destination,
                'captured' => false,
                'message' => 'Authenticated customer — signup attribution skipped.',
            ];
        }

        // Other guards (admin/professional) also skip signup attribution.
        if (Auth::guard('admin')->check() || Auth::guard('professional')->check()) {
            return [
                'ok' => true,
                'redirect' => $fallback,
                'captured' => false,
                'message' => 'Non-customer guard — signup attribution skipped.',
            ];
        }

        $resolved = $this->resolveReferral($code, $referralType, $toolKey, $professionalId);
        if ($resolved === null) {
            // Uniform redirect for invalid codes/targets (limits enumeration).
            return [
                'ok' => false,
                'redirect' => $fallback,
                'captured' => false,
                'message' => 'Invalid referral.',
            ];
        }

        $payload = [
            'code' => $resolved['code']->code,
            'referral_type' => $resolved['referral_type'],
            'target_type' => $resolved['target_type'],
            'target_id' => $resolved['target_id'],
            'referrer_user_id' => (int) $resolved['code']->user_id,
            'destination_route' => $resolved['destination_route'],
            'destination_params' => $resolved['destination_params'],
        ];

        $stored = $this->store->remember($payload, overwrite: false);

        return [
            'ok' => true,
            'redirect' => $resolved['destination_url'],
            'captured' => $stored,
            'message' => $stored ? 'Attribution captured (first-touch).' : 'Existing capture retained (first-touch).',
        ];
    }

    /**
     * Bind a completed customer registration to a pending capture.
     * Status starts at `joined` — never rewards points.
     */
    public function bindCompletedRegistration(User $referee): ?ReferralAttribution
    {
        if (! $referee->registration_completed) {
            return null;
        }

        if (ReferralAttribution::query()->where('referee_user_id', $referee->id)->exists()) {
            $this->store->forget();

            return ReferralAttribution::query()->where('referee_user_id', $referee->id)->first();
        }

        $capture = $this->store->get();
        if ($capture === null) {
            return null;
        }

        $referrerId = (int) $capture['referrer_user_id'];
        if ($referrerId === (int) $referee->id) {
            $this->store->forget();
            Log::info('Referral bind skipped: self-referral', ['user_id' => $referee->id]);

            return null;
        }

        $code = ReferralCode::query()
            ->where('code', (string) $capture['code'])
            ->where('is_active', true)
            ->first();

        if ($code === null || (int) $code->user_id !== $referrerId) {
            $this->store->forget();

            return null;
        }

        // Re-validate target still public if tool/expert.
        if (! $this->targetsStillValid($capture)) {
            $this->store->forget();

            return null;
        }

        $idempotencyKey = 'signup:'.$referee->id;

        try {
            $attribution = DB::transaction(function () use ($capture, $code, $referee, $referrerId, $idempotencyKey) {
                $existing = ReferralAttribution::query()
                    ->where('referee_user_id', $referee->id)
                    ->lockForUpdate()
                    ->first();
                if ($existing !== null) {
                    return $existing;
                }

                return ReferralAttribution::query()->create([
                    'referrer_user_id' => $referrerId,
                    'referee_user_id' => $referee->id,
                    'referral_code' => $code->code,
                    'referral_type' => (string) $capture['referral_type'],
                    'target_type' => $capture['target_type'] ?? null,
                    'target_id' => isset($capture['target_id']) ? (int) $capture['target_id'] : null,
                    'status' => 'joined',
                    'idempotency_key' => $idempotencyKey,
                    'signed_up_at' => now(),
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $attribution = ReferralAttribution::query()->where('referee_user_id', $referee->id)->first();
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }
            $attribution = ReferralAttribution::query()->where('referee_user_id', $referee->id)->first();
        }

        $this->store->forget();

        return $attribution;
    }

    /**
     * @return array{
     *   code:ReferralCode,
     *   referral_type:string,
     *   target_type:?string,
     *   target_id:?int,
     *   destination_url:string,
     *   destination_route:string,
     *   destination_params:array<string,mixed>
     * }|null
     */
    private function resolveReferral(
        string $code,
        string $referralType,
        ?string $toolKey,
        ?int $professionalId,
    ): ?array {
        $referralType = strtolower($referralType);
        if (! in_array($referralType, ReferralAttribution::TYPES, true)) {
            return null;
        }

        $row = ReferralCode::query()
            ->where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->first();

        if ($row === null) {
            return null;
        }

        $referrer = User::query()->whereKey($row->user_id)->first();
        if ($referrer === null || ! $referrer->registration_completed) {
            return null;
        }

        if ($referralType === 'general') {
            return [
                'code' => $row,
                'referral_type' => 'general',
                'target_type' => null,
                'target_id' => null,
                'destination_url' => route('register'),
                'destination_route' => 'register',
                'destination_params' => [],
            ];
        }

        if ($referralType === 'tool') {
            $tool = $this->findLaunchableTool($toolKey);
            if ($tool === null) {
                return null;
            }

            return [
                'code' => $row,
                'referral_type' => 'tool',
                'target_type' => 'tool',
                'target_id' => (int) $tool->id,
                'destination_url' => $this->links->resolveToolDestination($tool),
                'destination_route' => (string) $tool->route_name,
                'destination_params' => [],
            ];
        }

        if ($referralType === 'expert') {
            if ($professionalId === null) {
                return null;
            }
            $expert = Professional::query()->whereKey($professionalId)->first();
            if ($expert === null) {
                return null;
            }
            try {
                $url = $this->links->resolveExpertDestination($expert);
            } catch (\InvalidArgumentException) {
                return null;
            }

            return [
                'code' => $row,
                'referral_type' => 'expert',
                'target_type' => 'professional',
                'target_id' => (int) $expert->id,
                'destination_url' => $url,
                'destination_route' => 'professionals.details.simple',
                'destination_params' => ['id' => $expert->id],
            ];
        }

        return null;
    }

    private function safeDestinationOnly(
        string $code,
        string $referralType,
        ?string $toolKey,
        ?int $professionalId,
    ): ?string {
        $resolved = $this->resolveReferral($code, $referralType, $toolKey, $professionalId);

        return $resolved['destination_url'] ?? null;
    }

    private function findLaunchableTool(?string $toolKey): ?Tool
    {
        if ($toolKey === null || trim($toolKey) === '') {
            return null;
        }

        $query = Tool::query();
        if (ctype_digit($toolKey)) {
            $query->whereKey((int) $toolKey);
        } else {
            $query->where('slug', $toolKey);
        }

        $tool = $query->first();
        if ($tool === null || ! $tool->canLaunch()) {
            return null;
        }

        return $tool;
    }

    /**
     * @param  array<string, mixed>  $capture
     */
    private function targetsStillValid(array $capture): bool
    {
        $type = (string) ($capture['referral_type'] ?? '');
        if ($type === 'general') {
            return true;
        }

        if ($type === 'tool') {
            $tool = Tool::query()->whereKey((int) ($capture['target_id'] ?? 0))->first();

            return $tool !== null && $tool->canLaunch();
        }

        if ($type === 'expert') {
            $expert = Professional::query()->whereKey((int) ($capture['target_id'] ?? 0))->first();
            if ($expert === null) {
                return false;
            }
            try {
                $this->links->assertPublicExpert($expert);

                return true;
            } catch (\InvalidArgumentException) {
                return false;
            }
        }

        return false;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || (string) $e->getCode() === '23000';
    }
}
