<?php

namespace App\Services\Pfm;

use App\Models\PfmCalculation;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PfmRecorder
{
    public function store(string $toolKey, PfmDraft $draft, bool $asDraft = false): PfmCalculation
    {
        $userId = Auth::guard('user')->id();
        $key = $draft->completionKey($toolKey);
        $result = $draft->result($toolKey);
        $normalized = $draft->normalized($toolKey);

        if (! $userId || ! is_string($key) || ! is_array($result) || ! is_array($normalized)) {
            throw new InvalidArgumentException('The calculation draft is incomplete.');
        }

        try {
            return $this->persist((int) $userId, $toolKey, $key, $result, $normalized, $asDraft);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return $this->existingForUser($key, (int) $userId);
            }
            throw $exception;
        }
    }

    private function persist(
        int $userId,
        string $toolKey,
        string $key,
        array $result,
        array $normalized,
        bool $asDraft
    ): PfmCalculation {
        return DB::transaction(function () use ($userId, $toolKey, $key, $result, $normalized, $asDraft) {
            $existing = PfmCalculation::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $meta = PfmCatalog::tool($toolKey);
            $title = is_array($meta) ? ($meta['name'] ?? $toolKey) : $toolKey;
            if ($toolKey === 'financial-goal-calculator' && ! empty($normalized['goal_name'])) {
                $title = (string) $normalized['goal_name'];
            }

            $record = new PfmCalculation([
                'tool_key' => $toolKey,
                'title' => $title,
                'inputs' => $normalized,
                'outputs' => $result,
                'summary' => (string) ($result['summary'] ?? ''),
                'content_version' => (string) ($result['content_version'] ?? PfmCatalog::CONTENT_VERSION),
                'completion_key' => $key,
                'is_draft' => $asDraft,
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): PfmCalculation
    {
        $existing = PfmCalculation::query()->where('completion_key', $key)->first();
        if (! $existing) {
            throw new RuntimeException('The calculation could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(PfmCalculation $record, int $userId): PfmCalculation
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }
}
