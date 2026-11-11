<?php

namespace App\Services;

use App\Models\TarotCard;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use App\Support\Tarot\TarotCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TarotReadingRecorder
{
    public function __construct(private readonly TarotDraft $draft)
    {
    }

    /**
     * Persist the server-owned reveal payload. Never trusts client card/insight input.
     */
    public function saveFromDraft(): TarotReading
    {
        $userId = Auth::guard('user')->id();
        if (! $userId) {
            throw new RuntimeException('Authentication required to save a Tarot reading.');
        }

        $draft = $this->draft->requireOwned();
        $key = is_string($draft['completion_key'] ?? null) ? $draft['completion_key'] : null;
        $revealed = is_array($draft['revealed'] ?? null) ? $draft['revealed'] : null;

        if ($key === null || $revealed === null) {
            throw new InvalidArgumentException('There is no completed reading to save.');
        }

        $this->assertRevealShape($revealed, $draft);

        try {
            $reading = $this->persist((int) $userId, $key, $revealed);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                $reading = $this->existingForUser($key, (int) $userId);
            } else {
                throw $exception;
            }
        }

        $this->awardIfNew($reading);
        $this->draft->markSaved((int) $reading->id);

        return $reading;
    }

    private function awardIfNew(TarotReading $reading): void
    {
        if (! $reading->wasRecentlyCreated) {
            return;
        }

        app(YuktiCustomerRewardHooks::class)->onEligibleToolCompleted(
            (int) $reading->user_id,
            'tarot-reflection',
            (string) $reading->completion_key,
            (int) $reading->id,
        );
    }

    /**
     * @param  array<string, mixed>  $revealed
     * @param  array<string, mixed>  $draft
     */
    private function assertRevealShape(array $revealed, array $draft): void
    {
        $spreadType = $draft['spread_type'] ?? null;
        $category = $draft['category'] ?? null;
        $question = $draft['question'] ?? null;

        if (! is_string($spreadType) || ! isset(TarotCatalog::spreads()[$spreadType])) {
            throw new InvalidArgumentException('Spread is missing.');
        }
        if (! is_string($category) || ! isset(TarotCatalog::categories()[$category])) {
            throw new InvalidArgumentException('Category is missing.');
        }
        if (! is_string($question) || trim($question) === '') {
            throw new InvalidArgumentException('Question is missing.');
        }

        $cards = is_array($revealed['cards'] ?? null) ? $revealed['cards'] : [];
        $needed = TarotCatalog::spreads()[$spreadType]['count'];
        if (count($cards) !== $needed) {
            throw new InvalidArgumentException('Reading card count does not match the spread.');
        }

        // Cross-check reveal against draft picks (server truth).
        $picks = is_array($draft['picks'] ?? null) ? array_values($draft['picks']) : [];
        if (count($picks) !== $needed) {
            throw new InvalidArgumentException('Draw picks are incomplete.');
        }

        foreach ($cards as $index => $card) {
            $pick = $picks[$index] ?? null;
            if (! is_array($pick) || ($pick['card_id'] ?? null) !== ($card['card_id'] ?? null)) {
                throw new InvalidArgumentException('Reading cards do not match the secured draw.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $revealed
     */
    private function persist(int $userId, string $key, array $revealed): TarotReading
    {
        return DB::transaction(function () use ($userId, $key, $revealed) {
            $existing = TarotReading::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $draft = $this->draft->requireOwned();
            $cards = $revealed['cards'];
            $cardIds = array_map(static fn (array $c) => $c['card_id'], $cards);
            $dbCards = TarotCard::query()->whereIn('card_id', $cardIds)->get()->keyBy('card_id');
            if ($dbCards->count() !== count(array_unique($cardIds))) {
                throw new RuntimeException('One or more cards could not be saved.');
            }

            $reading = new TarotReading([
                'completion_key' => $key,
                'category' => $draft['category'],
                'question' => $draft['question'],
                'spread_type' => $draft['spread_type'],
                'reversed_enabled' => ($draft['reversed_enabled'] ?? false) === true,
                'overall_reflection' => is_string($revealed['overall_reflection'] ?? null)
                    ? $revealed['overall_reflection']
                    : null,
            ]);
            $reading->user_id = $userId;
            $reading->save();

            foreach ($cards as $index => $cardPayload) {
                $pick = $draft['picks'][$index];
                /** @var TarotCard $card */
                $card = $dbCards[$pick['card_id']];
                $isReversed = ($draft['reversed_enabled'] ?? false) === true
                    ? (bool) ($pick['is_reversed'] ?? false)
                    : false;

                TarotReadingCard::query()->create([
                    'tarot_reading_id' => $reading->id,
                    'tarot_card_id' => $card->id,
                    'position' => (string) ($cardPayload['position'] ?? $pick['position']),
                    'position_order' => (int) ($pick['position_order'] ?? ($index + 1)),
                    'is_reversed' => $isReversed,
                    // Snapshot the already-generated insight; do not regenerate.
                    'insight_text' => (string) ($cardPayload['insight'] ?? ''),
                    'drawn_at' => now(),
                ]);
            }

            return $reading->load('cards.card');
        });
    }

    private function existingForUser(string $key, int $userId): TarotReading
    {
        $existing = TarotReading::query()->where('completion_key', $key)->first();
        if (! $existing) {
            throw new RuntimeException('The reading could not be saved.');
        }

        $owned = $this->owned($existing, $userId);
        $this->draft->markSaved((int) $owned->id);

        return $owned->load('cards.card');
    }

    private function owned(TarotReading $reading, int $userId): TarotReading
    {
        if ((int) $reading->user_id !== $userId) {
            abort(403);
        }

        return $reading;
    }
}
