<?php

namespace App\Services;

use App\Models\TarotCard;
use App\Support\Tarot\TarotCatalog;
use InvalidArgumentException;

class TarotInterpretationService
{
    public function __construct(private readonly TarotDraft $draft)
    {
    }

    /**
     * Build reveal payload from secured picks. Never trusts client card IDs.
     *
     * @return array{cards: list<array<string, mixed>>, overall_reflection: ?string, category: string, question: string, spread_type: string, reversed_enabled: bool}
     */
    public function revealFromDraft(): array
    {
        $draft = $this->draft->requireOwned();
        $spreadType = $draft['spread_type'] ?? null;
        $category = $draft['category'] ?? null;
        $question = $draft['question'] ?? null;
        $picks = is_array($draft['picks'] ?? null) ? array_values($draft['picks']) : [];

        if (! is_string($spreadType) || ! isset(TarotCatalog::spreads()[$spreadType])) {
            throw new InvalidArgumentException('Spread is missing.');
        }
        if (! is_string($category) || ! isset(TarotCatalog::categories()[$category])) {
            throw new InvalidArgumentException('Category is missing.');
        }
        if (! is_string($question) || trim($question) === '') {
            throw new InvalidArgumentException('Question is missing.');
        }

        $needed = TarotCatalog::spreads()[$spreadType]['count'];
        if (count($picks) !== $needed) {
            throw new InvalidArgumentException('Draw is incomplete.');
        }

        $cardIds = array_map(static fn (array $p) => $p['card_id'] ?? null, $picks);
        if (count($cardIds) !== count(array_unique($cardIds))) {
            throw new InvalidArgumentException('Duplicate cards are not allowed.');
        }

        $cardsById = TarotCard::query()
            ->whereIn('card_id', $cardIds)
            ->get()
            ->keyBy('card_id');

        if ($cardsById->count() !== count($cardIds)) {
            throw new InvalidArgumentException('One or more cards could not be found.');
        }

        $categoryMeta = TarotCatalog::categories()[$category];
        $spreadMeta = TarotCatalog::spreads()[$spreadType];
        $reversedEnabled = ($draft['reversed_enabled'] ?? false) === true;

        $revealedCards = [];
        foreach ($picks as $index => $pick) {
            /** @var TarotCard $card */
            $card = $cardsById[$pick['card_id']];
            $isReversed = $reversedEnabled ? (bool) ($pick['is_reversed'] ?? false) : false;
            $position = $spreadMeta['positions'][$index];
            $meaning = $isReversed ? $card->reversed_meaning : $card->upright_meaning;

            $insight = $this->composeInsight(
                categoryLabel: $categoryMeta['label'],
                categoryLens: $categoryMeta['lens'],
                question: $question,
                cardName: $card->card_name,
                orientation: $isReversed ? 'reversed' : 'upright',
                positionLabel: $position['label'],
                positionPrompt: $position['prompt'],
                meaning: $meaning,
                spreadType: $spreadType,
            );

            $revealedCards[] = [
                'card_id' => $card->card_id,
                'card_name' => $card->card_name,
                'arcana' => $card->arcana,
                'suit' => $card->suit,
                'number' => $card->number,
                'position' => $position['key'],
                'position_label' => $position['label'],
                'position_order' => (int) ($pick['position_order'] ?? ($index + 1)),
                'is_reversed' => $isReversed,
                'orientation_label' => $isReversed ? 'Reversed' : 'Upright',
                'core_meaning' => $meaning,
                'insight' => $insight,
                'image_path' => $card->image_path,
            ];
        }

        $overall = null;
        if ($spreadType === TarotCatalog::SPREAD_THREE) {
            $overall = $this->composeSynthesis($categoryMeta['label'], $question, $revealedCards);
        } elseif ($spreadType === TarotCatalog::SPREAD_ONE) {
            $overall = $this->composeOneCardReflection($revealedCards[0], $categoryMeta['label'], $question);
        }

        $payload = [
            'cards' => $revealedCards,
            'overall_reflection' => $overall,
            'category' => $category,
            'category_label' => $categoryMeta['label'],
            'question' => $question,
            'spread_type' => $spreadType,
            'reversed_enabled' => $reversedEnabled,
        ];

        $this->draft->rememberReveal($payload);

        return $payload;
    }

    private function composeInsight(
        string $categoryLabel,
        string $categoryLens,
        string $question,
        string $cardName,
        string $orientation,
        string $positionLabel,
        string $positionPrompt,
        string $meaning,
        string $spreadType,
    ): string {
        $safeQuestion = $this->clip($question, 180);
        $orientationPhrase = $orientation === 'reversed' ? 'in a reversed orientation' : 'upright';

        $sentences = [];
        $sentences[] = sprintf(
            'As %s in your %s reading, %s %s may offer a reflective lens on “%s”.',
            $positionLabel,
            $categoryLabel,
            $cardName,
            $orientationPhrase,
            $safeQuestion
        );
        $sentences[] = sprintf(
            'In this position — %s — the card’s core theme (%s) can suggest noticing themes around %s.',
            lcfirst($positionPrompt),
            rtrim($meaning, '.'),
            $categoryLens
        );
        $sentences[] = 'This is an invitation to reflect, not a prediction of what will happen or what another person thinks.';

        if ($spreadType === TarotCatalog::SPREAD_ONE) {
            $sentences[] = 'Sit with what feels most relevant, and let the image of this card guide a gentle next question for yourself.';
        }

        return implode(' ', $sentences);
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     */
    private function composeSynthesis(string $categoryLabel, string $question, array $cards): string
    {
        $byPosition = [];
        foreach ($cards as $card) {
            $byPosition[$card['position']] = $card;
        }

        $situation = $byPosition['situation']['card_name'] ?? 'the first card';
        $challenge = $byPosition['challenge']['card_name'] ?? 'the second card';
        $guidance = $byPosition['guidance']['card_name'] ?? 'the third card';

        return sprintf(
            'Overall, this %s reading around “%s” may weave three reflective threads: %s can point to what is present in the situation, %s may highlight what feels difficult or unclear, and %s can suggest a perspective that may help you move forward. Taken together, the spread invites curiosity and self-honesty rather than certainty.',
            $categoryLabel,
            $this->clip($question, 160),
            $situation,
            $challenge,
            $guidance
        );
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function composeOneCardReflection(array $card, string $categoryLabel, string $question): string
    {
        return sprintf(
            'A concise reflection for this %s reading: %s may be asking you to notice “%s” through the theme of %s — gently, and without needing a fixed answer today.',
            $categoryLabel,
            $card['card_name'],
            $this->clip($question, 140),
            rtrim((string) $card['core_meaning'], '.')
        );
    }

    private function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }
}
