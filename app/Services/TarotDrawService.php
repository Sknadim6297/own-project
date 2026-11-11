<?php

namespace App\Services;

use App\Models\TarotCard;
use App\Support\Tarot\TarotCatalog;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class TarotDrawService
{
    public function __construct(private readonly TarotDraft $draft)
    {
    }

    /**
     * Prepare a secure facedown fan. Client only receives opaque slot tokens.
     *
     * @return array{tokens: list<string>, needed: int, picked: int, positions: list<array{key: string, label: string}>, nonce: string}
     */
    public function ensureFan(): array
    {
        $draft = $this->draft->requireOwned();
        $spreadType = $draft['spread_type'] ?? null;
        if (! is_string($spreadType) || ! isset(TarotCatalog::spreads()[$spreadType])) {
            throw new RuntimeException('Choose a spread before drawing.');
        }

        $needed = TarotCatalog::spreads()[$spreadType]['count'];
        $picks = is_array($draft['picks'] ?? null) ? $draft['picks'] : [];

        if (count($picks) >= $needed && is_array($draft['revealed'] ?? null)) {
            return $this->present($draft);
        }

        $deck = is_array($draft['deck_order'] ?? null) ? $draft['deck_order'] : [];
        $slotMap = is_array($draft['slot_map'] ?? null) ? $draft['slot_map'] : [];
        $nonce = is_string($draft['draw_nonce'] ?? null) ? $draft['draw_nonce'] : null;

        if ($deck === [] || $slotMap === [] || $nonce === null) {
            $deck = $this->shuffleDeckIds();
            $slotMap = $this->buildSlotMap($deck, $picks);
            $nonce = (string) Str::random(40);
            $this->draft->rememberDrawSetup($deck, $slotMap, $nonce);
            $draft = $this->draft->requireOwned();
        }

        return $this->present($draft);
    }

    /**
     * Accept one opaque slot token. Card identity is resolved only server-side.
     */
    public function pick(string $token, string $nonce): array
    {
        $draft = $this->draft->requireOwned();
        if (($draft['step'] ?? null) !== 'draw' && ($draft['step'] ?? null) !== 'spread') {
            // allow first pick right after spread setup created draw state
        }

        $spreadType = $draft['spread_type'] ?? null;
        if (! is_string($spreadType) || ! isset(TarotCatalog::spreads()[$spreadType])) {
            throw new InvalidArgumentException('Invalid spread.');
        }

        $expectedNonce = $draft['draw_nonce'] ?? null;
        if (! is_string($expectedNonce) || ! hash_equals($expectedNonce, $nonce)) {
            throw new InvalidArgumentException('This draw action is no longer valid. Refresh and try again.');
        }

        $slotMap = is_array($draft['slot_map'] ?? null) ? $draft['slot_map'] : [];
        if (! array_key_exists($token, $slotMap)) {
            throw new InvalidArgumentException('That card is not available.');
        }

        $deckIndex = (int) $slotMap[$token];
        $deck = is_array($draft['deck_order'] ?? null) ? array_values($draft['deck_order']) : [];
        if (! isset($deck[$deckIndex]) || ! is_string($deck[$deckIndex])) {
            throw new InvalidArgumentException('Draw state is invalid.');
        }

        $picks = is_array($draft['picks'] ?? null) ? array_values($draft['picks']) : [];
        $needed = TarotCatalog::spreads()[$spreadType]['count'];
        if (count($picks) >= $needed) {
            throw new InvalidArgumentException('All cards for this spread are already drawn.');
        }

        foreach ($picks as $existing) {
            if (($existing['token'] ?? null) === $token) {
                throw new InvalidArgumentException('That card was already selected.');
            }
            if ((int) ($existing['deck_index'] ?? -1) === $deckIndex) {
                throw new InvalidArgumentException('That card was already selected.');
            }
            if (($existing['card_id'] ?? null) === $deck[$deckIndex]) {
                throw new InvalidArgumentException('Duplicate cards are not allowed in one reading.');
            }
        }

        $positionMeta = TarotCatalog::spreads()[$spreadType]['positions'][count($picks)];
        $isReversed = false;
        if (($draft['reversed_enabled'] ?? false) === true) {
            $isReversed = random_int(0, 1) === 1;
        }

        $pick = [
            'token' => $token,
            'deck_index' => $deckIndex,
            'card_id' => $deck[$deckIndex],
            'position' => $positionMeta['key'],
            'position_order' => count($picks) + 1,
            'is_reversed' => $isReversed,
        ];

        $this->draft->rememberPick($pick);

        return $this->draft->requireOwned();
    }

    public function requiredCount(): int
    {
        $spreadType = $this->draft->spreadType();
        if ($spreadType === null) {
            return 0;
        }

        return TarotCatalog::spreads()[$spreadType]['count'];
    }

    /**
     * @return list<string>
     */
    private function shuffleDeckIds(): array
    {
        $ids = TarotCard::query()->orderBy('sort_order')->pluck('card_id')->all();
        if (count($ids) !== 78) {
            throw new RuntimeException('Tarot deck is not ready. Expected 78 cards.');
        }

        // Fisher–Yates with CSPRNG.
        for ($i = count($ids) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        }

        return array_values($ids);
    }

    /**
     * @param  list<string>  $deck
     * @param  list<array<string, mixed>>  $picks
     * @return array<string, int>
     */
    private function buildSlotMap(array $deck, array $picks): array
    {
        $usedIndexes = [];
        foreach ($picks as $pick) {
            if (isset($pick['deck_index'])) {
                $usedIndexes[(int) $pick['deck_index']] = true;
            }
        }

        $available = [];
        foreach ($deck as $index => $_cardId) {
            if (! isset($usedIndexes[$index])) {
                $available[] = $index;
            }
        }

        // Present a facedown fan from the start of the remaining shuffled order.
        $fanIndexes = array_slice($available, 0, TarotCatalog::FAN_SIZE);
        $map = [];
        foreach ($fanIndexes as $index) {
            $map[(string) Str::random(24)] = $index;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{tokens: list<string>, needed: int, picked: int, positions: list<array{key: string, label: string}>, nonce: string, selected_tokens: list<string>}
     */
    private function present(array $draft): array
    {
        $spreadType = $draft['spread_type'];
        $spread = TarotCatalog::spreads()[$spreadType];
        $slotMap = is_array($draft['slot_map'] ?? null) ? $draft['slot_map'] : [];
        $picks = is_array($draft['picks'] ?? null) ? $draft['picks'] : [];
        $selected = [];
        foreach ($picks as $pick) {
            if (is_string($pick['token'] ?? null)) {
                $selected[] = $pick['token'];
            }
        }

        return [
            'tokens' => array_keys($slotMap),
            'needed' => $spread['count'],
            'picked' => count($picks),
            'positions' => array_map(
                static fn (array $p) => ['key' => $p['key'], 'label' => $p['label']],
                $spread['positions']
            ),
            'nonce' => (string) ($draft['draw_nonce'] ?? ''),
            'selected_tokens' => $selected,
        ];
    }
}
