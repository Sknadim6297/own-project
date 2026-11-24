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

        return $this->storePayload((int) $userId, $toolKey, $key, $result, $normalized, $asDraft);
    }

    /**
     * Persist a server-computed payload (used by the mobile Sanctum API).
     * Never trusts client-authored EMI / interest figures — callers must pass
     * outputs from PfmCalculator::calculate.
     *
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $normalized
     */
    public function storePayload(
        int $userId,
        string $toolKey,
        string $completionKey,
        array $result,
        array $normalized,
        bool $asDraft = false
    ): PfmCalculation {
        try {
            return $this->persist($userId, $toolKey, $completionKey, $result, $normalized, $asDraft);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return $this->existingForUser($completionKey, $userId);
            }
            throw $exception;
        }
    }

    /**
     * Update an existing saved calculation from an edit-mode draft (Task 24).
     * Preserves id, ownership, tool_key, completion_key, and created_at.
     *
     * @throws InvalidArgumentException incomplete draft
     * @throws RuntimeException missing record or stale concurrent edit
     */
    public function updateFromEdit(string $toolKey, PfmDraft $draft): PfmCalculation
    {
        $userId = Auth::guard('user')->id();
        $editId = $draft->editingId($toolKey);
        $expectedUpdatedAt = $draft->editingUpdatedAt($toolKey);
        $result = $draft->result($toolKey);
        $normalized = $draft->normalized($toolKey);

        if (! $userId || ! $editId || ! is_array($result) || ! is_array($normalized)) {
            throw new InvalidArgumentException('The editing context is incomplete.');
        }

        return DB::transaction(function () use ($userId, $toolKey, $editId, $expectedUpdatedAt, $result, $normalized) {
            $record = PfmCalculation::query()->whereKey($editId)->lockForUpdate()->first();
            if (! $record) {
                throw new RuntimeException('The saved calculation could not be found.');
            }

            if ((int) $record->user_id !== (int) $userId || $record->tool_key !== $toolKey) {
                abort(403);
            }

            $actualUpdatedAt = $record->updated_at?->getTimestamp();
            if ($expectedUpdatedAt !== null && $actualUpdatedAt !== null && (int) $actualUpdatedAt !== (int) $expectedUpdatedAt) {
                throw new RuntimeException(
                    'This calculation was updated elsewhere. Reload the latest saved version before editing again.'
                );
            }

            $record->inputs = $normalized;
            $record->outputs = $result;
            $record->summary = (string) ($result['summary'] ?? '');
            $record->content_version = (string) ($result['content_version'] ?? PfmCatalog::CONTENT_VERSION);
            $record->is_draft = false;
            $record->save();

            return $record->fresh();
        });
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
