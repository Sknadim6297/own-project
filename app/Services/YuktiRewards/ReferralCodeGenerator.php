<?php

namespace App\Services\YuktiRewards;

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persistent, cryptographically unpredictable referral codes (one per user).
 */
class ReferralCodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function forUser(User $user): ReferralCode
    {
        $existing = ReferralCode::query()->where('user_id', $user->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $length = max(8, min(32, (int) config('yuktione_rewards.referral_code_length', 10)));

        return DB::transaction(function () use ($user, $length) {
            $locked = ReferralCode::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if ($locked !== null) {
                return $locked;
            }

            for ($attempt = 0; $attempt < 12; $attempt++) {
                $code = $this->generate($length);

                try {
                    return ReferralCode::query()->create([
                        'user_id' => $user->id,
                        'code' => $code,
                        'is_active' => true,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Code collision or concurrent user_id insert — retry.
                    $again = ReferralCode::query()->where('user_id', $user->id)->first();
                    if ($again !== null) {
                        return $again;
                    }
                } catch (QueryException $e) {
                    if (! $this->isUniqueViolation($e)) {
                        throw $e;
                    }
                    $again = ReferralCode::query()->where('user_id', $user->id)->first();
                    if ($again !== null) {
                        return $again;
                    }
                }
            }

            throw new RuntimeException('Unable to allocate a unique referral code.');
        }, 3);
    }

    public function generate(int $length = 10): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $bytes = random_bytes($length);
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[ord($bytes[$i]) % $alphabetLength];
        }

        return $out;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || (string) $e->getCode() === '23000';
    }
}
