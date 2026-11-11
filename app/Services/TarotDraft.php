<?php

namespace App\Services;

use App\Support\Tarot\TarotCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

class TarotDraft
{
    public const KEY = 'tarot.reading_draft';

    public const STEPS = ['intro', 'category', 'question', 'spread', 'draw', 'reveal', 'reading'];

    public function readable(): ?array
    {
        $draft = session(self::KEY);
        if (! is_array($draft)) {
            return null;
        }

        if (($draft['expires_at'] ?? 0) < now()->getTimestamp()) {
            $this->forget();

            return null;
        }

        if (($draft['owner'] ?? null) !== $this->ownerOrNull()) {
            return null;
        }

        if (! in_array($draft['step'] ?? null, self::STEPS, true)) {
            return null;
        }

        return $draft;
    }

    public function requireOwned(): array
    {
        $draft = $this->readable();
        if ($draft === null) {
            throw new RuntimeException('Tarot reading session expired or is unavailable.');
        }

        return $draft;
    }

    public function startFresh(): array
    {
        $owner = $this->requireUserOwner();

        $draft = [
            'step' => 'intro',
            'completion_key' => (string) Str::uuid(),
            'category' => null,
            'question' => null,
            'spread_type' => null,
            'reversed_enabled' => false,
            'deck_order' => [],
            'slot_map' => [],
            'draw_nonce' => null,
            'picks' => [],
            'revealed' => null,
            'saved_reading_id' => null,
            'owner' => $owner,
            'expires_at' => now()->addMinutes(TarotCatalog::DRAFT_TTL_MINUTES)->getTimestamp(),
        ];

        session([self::KEY => $draft]);

        return $draft;
    }

    public function forget(): void
    {
        session()->forget(self::KEY);
    }

    public function rememberCategory(string $category): void
    {
        $draft = $this->baseWrite();
        $draft['step'] = 'category';
        $draft['category'] = $category;
        // Changing category invalidates later stages.
        $draft['question'] = null;
        $draft['spread_type'] = null;
        $draft['reversed_enabled'] = false;
        $this->clearDrawState($draft);
        $this->put($draft);
    }

    public function rememberQuestion(string $question): void
    {
        $draft = $this->baseWrite();
        if (! is_string($draft['category'] ?? null)) {
            throw new RuntimeException('Choose a category first.');
        }
        $draft['step'] = 'question';
        $draft['question'] = $question;
        $draft['spread_type'] = null;
        $draft['reversed_enabled'] = false;
        $this->clearDrawState($draft);
        $this->put($draft);
    }

    public function rememberSpread(string $spreadType, bool $reversedEnabled): void
    {
        $draft = $this->baseWrite();
        if (! is_string($draft['category'] ?? null) || ! is_string($draft['question'] ?? null)) {
            throw new RuntimeException('Complete category and question first.');
        }
        $draft['step'] = 'spread';
        $draft['spread_type'] = $spreadType;
        $draft['reversed_enabled'] = $reversedEnabled;
        $this->clearDrawState($draft);
        $this->put($draft);
    }

    /**
     * @param  list<string>  $deckOrder
     * @param  array<string, int>  $slotMap token => deck index
     */
    public function rememberDrawSetup(array $deckOrder, array $slotMap, string $drawNonce): void
    {
        $draft = $this->baseWrite();
        if (! is_string($draft['spread_type'] ?? null)) {
            throw new RuntimeException('Choose a spread first.');
        }
        $draft['step'] = 'draw';
        $draft['deck_order'] = array_values($deckOrder);
        $draft['slot_map'] = $slotMap;
        $draft['draw_nonce'] = $drawNonce;
        $draft['picks'] = [];
        $draft['revealed'] = null;
        $this->put($draft);
    }

    /**
     * @param  array{token: string, deck_index: int, card_id: string, position: string, position_order: int, is_reversed: bool}  $pick
     */
    public function rememberPick(array $pick): void
    {
        $draft = $this->baseWrite();
        if (($draft['step'] ?? null) !== 'draw') {
            throw new RuntimeException('Draw step is not active.');
        }
        $picks = is_array($draft['picks'] ?? null) ? $draft['picks'] : [];
        $picks[] = $pick;
        $draft['picks'] = $picks;
        // Rotate nonce after each accepted pick to reduce replay.
        $draft['draw_nonce'] = (string) Str::random(40);
        $this->put($draft);
    }

    /**
     * @param  array{cards: list<array<string, mixed>>, overall_reflection: ?string}  $revealed
     */
    public function rememberReveal(array $revealed): void
    {
        $draft = $this->baseWrite();
        $draft['step'] = 'reveal';
        $draft['revealed'] = $revealed;
        $draft['draw_nonce'] = null;
        $this->put($draft);
    }

    public function rememberFullReading(): void
    {
        $draft = $this->baseWrite();
        if (! is_array($draft['revealed'] ?? null)) {
            throw new RuntimeException('Reveal the cards before opening the full reading.');
        }
        $draft['step'] = 'reading';
        $this->put($draft);
    }

    public function markSaved(int $readingId): void
    {
        $draft = $this->baseWrite();
        $draft['saved_reading_id'] = $readingId;
        $draft['step'] = 'reading';
        $this->put($draft);
    }

    public function completionKey(): ?string
    {
        $draft = $this->readable();
        $key = is_array($draft) ? ($draft['completion_key'] ?? null) : null;

        return is_string($key) ? $key : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function revealed(): ?array
    {
        $draft = $this->readable();
        if (! is_array($draft) || ! is_array($draft['revealed'] ?? null)) {
            return null;
        }

        return $draft['revealed'];
    }

    public function savedReadingId(): ?int
    {
        $draft = $this->readable();
        $id = is_array($draft) ? ($draft['saved_reading_id'] ?? null) : null;

        return is_numeric($id) ? (int) $id : null;
    }

    public function category(): ?string
    {
        $draft = $this->readable();

        return is_string($draft['category'] ?? null) ? $draft['category'] : null;
    }

    public function question(): ?string
    {
        $draft = $this->readable();

        return is_string($draft['question'] ?? null) ? $draft['question'] : null;
    }

    public function spreadType(): ?string
    {
        $draft = $this->readable();

        return is_string($draft['spread_type'] ?? null) ? $draft['spread_type'] : null;
    }

    public function reversedEnabled(): bool
    {
        $draft = $this->readable();

        return is_array($draft) && ($draft['reversed_enabled'] ?? false) === true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function picks(): array
    {
        $draft = $this->readable();
        if (! is_array($draft) || ! is_array($draft['picks'] ?? null)) {
            return [];
        }

        return array_values($draft['picks']);
    }

    private function baseWrite(): array
    {
        $draft = $this->readable();
        if ($draft === null) {
            throw new RuntimeException('Tarot reading session expired or is unavailable.');
        }

        $draft['expires_at'] = now()->addMinutes(TarotCatalog::DRAFT_TTL_MINUTES)->getTimestamp();

        return $draft;
    }

    private function clearDrawState(array &$draft): void
    {
        $draft['deck_order'] = [];
        $draft['slot_map'] = [];
        $draft['draw_nonce'] = null;
        $draft['picks'] = [];
        $draft['revealed'] = null;
        $draft['saved_reading_id'] = null;
    }

    private function put(array $draft): void
    {
        session([self::KEY => $draft]);
    }

    private function requireUserOwner(): string
    {
        $owner = $this->ownerOrNull();
        if ($owner === null) {
            throw new RuntimeException('Authentication required for Tarot Reflection.');
        }

        return $owner;
    }

    private function ownerOrNull(): ?string
    {
        $id = Auth::guard('user')->id();

        return $id ? 'user:'.$id : null;
    }
}
