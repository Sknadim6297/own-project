<?php

namespace App\Services\YuktiRewards;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;

/**
 * First-touch temporary attribution storage (session + encrypted cookie backup).
 * Does not create DB attributions or grant points.
 */
class ReferralCaptureStore
{
    public const SESSION_KEY = 'yukti.referral.capture';

    public const COOKIE_NAME = 'yukti_ref_capture';

    /**
     * @param  array{
     *   code:string,
     *   referral_type:string,
     *   target_type:?string,
     *   target_id:?int,
     *   referrer_user_id:int,
     *   destination_route:?string,
     *   destination_params?:array<string,mixed>
     * }  $payload
     */
    public function remember(array $payload, bool $overwrite = false): bool
    {
        if (! $overwrite && $this->get() !== null) {
            return false; // first-touch policy
        }

        $payload['captured_at'] = now()->timestamp;
        $payload['expires_at'] = now()
            ->addMinutes(max(1, (int) config('yuktione_rewards.referral_capture_ttl_minutes', 4320)))
            ->timestamp;

        session([self::SESSION_KEY => $payload]);

        $minutes = max(1, (int) config('yuktione_rewards.referral_capture_ttl_minutes', 4320));
        Cookie::queue(
            cookie(
                self::COOKIE_NAME,
                Crypt::encryptString(json_encode($payload)),
                $minutes,
                '/',
                null,
                config('session.secure', false),
                true,
                false,
                config('session.same_site', 'lax') ?: 'lax'
            )
        );

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        $payload = session(self::SESSION_KEY);
        if (! is_array($payload)) {
            $payload = $this->readCookie();
            if ($payload !== null) {
                session([self::SESSION_KEY => $payload]);
            }
        }

        if (! is_array($payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
            $this->forget();

            return null;
        }

        if (! isset($payload['code'], $payload['referral_type'], $payload['referrer_user_id'])) {
            $this->forget();

            return null;
        }

        return $payload;
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readCookie(): ?array
    {
        $raw = request()->cookie(self::COOKIE_NAME);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($raw), true);
        } catch (DecryptException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
