<?php

namespace App\Services;

use App\Models\BmiBmrCalculation;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BmiBmrRecorder
{
    public function store(BmiBmrDraft $draft): BmiBmrCalculation
    {
        $userId = Auth::guard('user')->id();
        $key = $draft->completionKey();
        $result = $draft->result();
        $normalized = $draft->normalized();

        if (! $userId || ! is_string($key) || ! is_array($result) || ! is_array($normalized)) {
            throw new InvalidArgumentException('The BMI/BMR draft is incomplete.');
        }

        try {
            $record = $this->persist((int) $userId, $key, $result, $normalized);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                $record = $this->existingForUser($key, (int) $userId);
            } else {
                throw $exception;
            }
        }

        $this->awardIfNew($record);

        return $record;
    }

    private function awardIfNew(BmiBmrCalculation $record): void
    {
        if (! $record->wasRecentlyCreated) {
            return;
        }

        app(YuktiCustomerRewardHooks::class)->onEligibleToolCompleted(
            (int) $record->user_id,
            'bmi-bmr-calculator',
            (string) $record->completion_key,
            (int) $record->id,
        );
    }

    private function persist(int $userId, string $key, array $result, array $normalized): BmiBmrCalculation
    {
        return DB::transaction(function () use ($userId, $key, $result, $normalized) {
            $existing = BmiBmrCalculation::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $record = new BmiBmrCalculation([
                'completion_key' => $key,
                'formula_version' => $result['formula_version'],
                'age' => $normalized['age'],
                'height_cm' => $normalized['height_cm'],
                'weight_kg' => $normalized['weight_kg'],
                'sex' => $normalized['sex'],
                'activity' => $normalized['activity'],
                'activity_factor' => $result['inputs']['activity_factor'],
                'excluded_population' => (bool) $normalized['excluded_population'],
                'bmi_exact' => $result['bmi']['exact'],
                'bmi_display' => $result['bmi']['display'],
                'bmi_category_key' => $result['bmi']['category']['key'] ?? null,
                'bmi_category_label' => $result['bmi']['category']['label'] ?? null,
                'bmr_kcal' => $result['bmr']['display'],
                'tdee_kcal' => $result['tdee']['display'],
                'payload' => $result,
                'calculated_at' => now(),
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): BmiBmrCalculation
    {
        $existing = BmiBmrCalculation::query()->where('completion_key', $key)->first();
        if (! $existing) {
            throw new RuntimeException('The calculation could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(BmiBmrCalculation $record, int $userId): BmiBmrCalculation
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }
}
