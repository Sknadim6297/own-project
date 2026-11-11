<?php

namespace App\Support\Tarot;

/**
 * Canonical 78-card developer deck from YuktiOne Tarot Reflection PDF (pages 5–7).
 * Keywords, reflection prompts, and artwork are intentionally omitted when not supplied.
 */
final class TarotDeckCatalog
{
    /**
     * @return list<array{
     *     card_id: string,
     *     card_name: string,
     *     arcana: string,
     *     suit: ?string,
     *     number: ?string,
     *     upright_meaning: string,
     *     reversed_meaning: string,
     *     sort_order: int
     * }>
     */
    public static function cards(): array
    {
        $cards = [];
        $order = 0;

        foreach (self::majors() as $card) {
            $cards[] = array_merge($card, [
                'arcana' => 'major',
                'suit' => null,
                'sort_order' => $order++,
            ]);
        }

        foreach (['wands', 'cups', 'swords', 'pentacles'] as $suit) {
            foreach (self::minors($suit) as $card) {
                $cards[] = array_merge($card, [
                    'arcana' => 'minor',
                    'suit' => $suit,
                    'sort_order' => $order++,
                ]);
            }
        }

        return $cards;
    }

    /**
     * @return list<array{card_id: string, card_name: string, number: string, upright_meaning: string, reversed_meaning: string}>
     */
    private static function majors(): array
    {
        return [
            ['card_id' => 'major_00', 'card_name' => 'The Fool', 'number' => '0', 'upright_meaning' => 'New beginnings, freedom, possibility', 'reversed_meaning' => 'Hesitation, recklessness, fear of the unknown'],
            ['card_id' => 'major_01', 'card_name' => 'The Magician', 'number' => 'I', 'upright_meaning' => 'Initiative, capability, action, using available resources', 'reversed_meaning' => 'Scattered energy, self-doubt, untapped ability'],
            ['card_id' => 'major_02', 'card_name' => 'The High Priestess', 'number' => 'II', 'upright_meaning' => 'Intuition, inner knowledge, patience, mystery', 'reversed_meaning' => 'Ignoring intuition, confusion, withholding'],
            ['card_id' => 'major_03', 'card_name' => 'The Empress', 'number' => 'III', 'upright_meaning' => 'Growth, nurturing, creativity, abundance', 'reversed_meaning' => 'Neglect, overgiving, creative block'],
            ['card_id' => 'major_04', 'card_name' => 'The Emperor', 'number' => 'IV', 'upright_meaning' => 'Structure, stability, authority, boundaries', 'reversed_meaning' => 'Rigidity, control, insecurity around authority'],
            ['card_id' => 'major_05', 'card_name' => 'The Hierophant', 'number' => 'V', 'upright_meaning' => 'Learning, tradition, guidance, shared values', 'reversed_meaning' => 'Questioning convention, restriction, stale beliefs'],
            ['card_id' => 'major_06', 'card_name' => 'The Lovers', 'number' => 'VI', 'upright_meaning' => 'Connection, values, alignment, meaningful choice', 'reversed_meaning' => 'Misalignment, difficult choice, values conflict'],
            ['card_id' => 'major_07', 'card_name' => 'The Chariot', 'number' => 'VII', 'upright_meaning' => 'Determination, direction, momentum, self-control', 'reversed_meaning' => 'Loss of direction, force without control, delay'],
            ['card_id' => 'major_08', 'card_name' => 'Strength', 'number' => 'VIII', 'upright_meaning' => 'Courage, patience, compassion, inner power', 'reversed_meaning' => 'Self-doubt, impatience, depleted confidence'],
            ['card_id' => 'major_09', 'card_name' => 'The Hermit', 'number' => 'IX', 'upright_meaning' => 'Reflection, solitude, deeper understanding, inner guidance', 'reversed_meaning' => 'Isolation, avoidance, overthinking'],
            ['card_id' => 'major_10', 'card_name' => 'Wheel of Fortune', 'number' => 'X', 'upright_meaning' => 'Change, cycles, turning point, shifting circumstances', 'reversed_meaning' => 'Resistance to change, repeating patterns, unstable timing'],
            ['card_id' => 'major_11', 'card_name' => 'Justice', 'number' => 'XI', 'upright_meaning' => 'Balance, truth, accountability, fair assessment', 'reversed_meaning' => 'Avoiding accountability, bias, imbalance'],
            ['card_id' => 'major_12', 'card_name' => 'The Hanged Man', 'number' => 'XII', 'upright_meaning' => 'Pause, surrender, new perspective, letting go', 'reversed_meaning' => 'Stagnation, forced waiting, resistance to perspective'],
            ['card_id' => 'major_13', 'card_name' => 'Death', 'number' => 'XIII', 'upright_meaning' => 'Transformation, ending, transition, renewal', 'reversed_meaning' => 'Clinging, delayed transition, fear of change'],
            ['card_id' => 'major_14', 'card_name' => 'Temperance', 'number' => 'XIV', 'upright_meaning' => 'Balance, integration, moderation, patience', 'reversed_meaning' => 'Imbalance, excess, impatience'],
            ['card_id' => 'major_15', 'card_name' => 'The Devil', 'number' => 'XV', 'upright_meaning' => 'Attachment, temptation, limiting patterns, dependency', 'reversed_meaning' => 'Breaking patterns, reclaiming agency, release'],
            ['card_id' => 'major_16', 'card_name' => 'The Tower', 'number' => 'XVI', 'upright_meaning' => 'Disruption, revelation, restructuring, truth exposed', 'reversed_meaning' => 'Avoiding necessary change, prolonged instability, fear'],
            ['card_id' => 'major_17', 'card_name' => 'The Star', 'number' => 'XVII', 'upright_meaning' => 'Hope, renewal, inspiration, healing, faith', 'reversed_meaning' => 'Discouragement, doubt, loss of direction'],
            ['card_id' => 'major_18', 'card_name' => 'The Moon', 'number' => 'XVIII', 'upright_meaning' => 'Uncertainty, intuition, hidden emotions, ambiguity', 'reversed_meaning' => 'Confusion clearing, fear losing power, clearer perception'],
            ['card_id' => 'major_19', 'card_name' => 'The Sun', 'number' => 'XIX', 'upright_meaning' => 'Clarity, vitality, optimism, confidence', 'reversed_meaning' => 'Temporary low energy, forced positivity, delayed clarity'],
            ['card_id' => 'major_20', 'card_name' => 'Judgement', 'number' => 'XX', 'upright_meaning' => 'Reflection, awakening, evaluation, decision', 'reversed_meaning' => 'Self-criticism, avoidance, difficulty accepting lessons'],
            ['card_id' => 'major_21', 'card_name' => 'The World', 'number' => 'XXI', 'upright_meaning' => 'Completion, achievement, integration, new cycle', 'reversed_meaning' => 'Incomplete closure, unfinished business, reluctance to move on'],
        ];
    }

    /**
     * @return list<array{card_id: string, card_name: string, number: string, upright_meaning: string, reversed_meaning: string}>
     */
    private static function minors(string $suit): array
    {
        $label = ucfirst($suit);
        $rows = match ($suit) {
            'wands' => [
                ['ace', 'Ace', 'New spark, initiative, creative potential', 'False start, low momentum, blocked inspiration'],
                ['02', 'Two', 'Planning, choosing a direction, future vision', 'Indecision, limited vision, fear of commitment'],
                ['03', 'Three', 'Expansion, progress, looking ahead', 'Delays, frustration with progress, narrow outlook'],
                ['04', 'Four', 'Celebration, stability, belonging, milestone', 'Unsettled foundation, tension at home, delayed celebration'],
                ['05', 'Five', 'Competition, friction, testing ideas', 'Avoidable conflict, scattered effort, unresolved tension'],
                ['06', 'Six', 'Recognition, progress, confidence', 'Need for validation, delayed recognition, pride'],
                ['07', 'Seven', 'Standing your ground, courage, persistence', 'Defensiveness, exhaustion, giving up too soon'],
                ['08', 'Eight', 'Momentum, communication, rapid movement', 'Delays, rushed communication, scattered pace'],
                ['09', 'Nine', 'Resilience, preparation, boundaries', 'Fatigue, guardedness, difficulty trusting'],
                ['10', 'Ten', 'Responsibility, workload, carrying too much', 'Overload, inability to delegate, unnecessary burden'],
                ['page', 'Page', 'Curiosity, creative message, exploration', 'Immaturity, inconsistent effort, delayed news'],
                ['knight', 'Knight', 'Bold action, adventure, pursuit', 'Impulsiveness, impatience, stop-start energy'],
                ['queen', 'Queen', 'Confidence, warmth, creativity, independence', 'Insecurity, burnout, seeking approval'],
                ['king', 'King', 'Leadership, vision, entrepreneurial drive', 'Domination, arrogance, poor direction'],
            ],
            'cups' => [
                ['ace', 'Ace', 'Emotional opening, compassion, new feeling', 'Emotional blockage, guardedness, drained connection'],
                ['02', 'Two', 'Mutual connection, partnership, understanding', 'Mismatch, imbalance, communication strain'],
                ['03', 'Three', 'Friendship, celebration, community', 'Overindulgence, exclusion, social tension'],
                ['04', 'Four', 'Reflection, emotional pause, reassessment', 'Re-engagement, renewed interest, noticing an opportunity'],
                ['05', 'Five', 'Disappointment, grief, focusing on loss', 'Acceptance, recovery, seeing what remains'],
                ['06', 'Six', 'Nostalgia, memories, kindness, familiarity', 'Living in the past, idealisation, difficulty moving forward'],
                ['07', 'Seven', 'Many possibilities, imagination, choices', 'Confusion clearing, unrealistic options narrowing'],
                ['08', 'Eight', 'Walking away, seeking deeper meaning', 'Avoidance, returning to an old pattern, fear of leaving'],
                ['09', 'Nine', 'Contentment, satisfaction, emotional reward', 'Overreliance on external validation, dissatisfaction'],
                ['10', 'Ten', 'Emotional fulfilment, harmony, belonging', 'Tension beneath the surface, ideal versus reality'],
                ['page', 'Page', 'Emotional curiosity, gentle message, openness', 'Moodiness, mixed signals, emotional immaturity'],
                ['knight', 'Knight', 'Romance, invitation, emotional movement', 'Idealisation, inconsistency, unrealistic expectations'],
                ['queen', 'Queen', 'Empathy, emotional wisdom, compassion', 'Emotional overwhelm, porous boundaries'],
                ['king', 'King', 'Emotional maturity, steadiness, diplomacy', 'Emotional suppression, detachment, mood control'],
            ],
            'swords' => [
                ['ace', 'Ace', 'Clarity, truth, new idea, decisive thinking', 'Confusion, misinformation, unclear decision'],
                ['02', 'Two', 'Pause, difficult choice, balancing perspectives', 'Avoidance, indecision breaking, uncomfortable truth'],
                ['03', 'Three', 'Heartbreak, difficult truth, grief, separation', 'Healing, processing pain, release'],
                ['04', 'Four', 'Rest, recovery, mental pause', 'Restlessness, premature return, difficulty switching off'],
                ['05', 'Five', 'Conflict, competing agendas, learning from tension', 'De-escalation, remorse, unresolved resentment'],
                ['06', 'Six', 'Transition, moving forward, calmer waters', 'Difficulty moving on, baggage, delayed transition'],
                ['07', 'Seven', 'Strategy, discretion, independent approach', 'Exposure, reconsidering strategy, accountability'],
                ['08', 'Eight', 'Feeling restricted, mental loops, perceived limitation', 'New perspective, release, taking back agency'],
                ['09', 'Nine', 'Worry, overthinking, mental strain', 'Relief, confronting fears, recovery'],
                ['10', 'Ten', 'Ending of a difficult cycle, finality, release', 'Recovery, resisting an ending, lingering pain'],
                ['page', 'Page', 'Curiosity, observation, new information', 'Gossip, impulsive communication, scattered focus'],
                ['knight', 'Knight', 'Fast thinking, direct communication, decisive action', 'Aggression, haste, conflict without reflection'],
                ['queen', 'Queen', 'Discernment, honesty, boundaries, clear thinking', 'Harshness, cynicism, overly critical judgment'],
                ['king', 'King', 'Logic, strategy, authority, rational decision', 'Coldness, misuse of power, rigid thinking'],
            ],
            'pentacles' => [
                ['ace', 'Ace', 'Opportunity, practical beginning, material potential', 'Missed opportunity, unstable start, poor planning'],
                ['02', 'Two', 'Balance, adaptability, managing priorities', 'Overcommitment, imbalance, dropped priorities'],
                ['03', 'Three', 'Skill, collaboration, craftsmanship, recognition', 'Poor teamwork, lack of appreciation, uneven quality'],
                ['04', 'Four', 'Security, saving, boundaries, holding resources', 'Fear of loss, excessive control, rigidity'],
                ['05', 'Five', 'Financial strain, insecurity, feeling left out', 'Support, recovery, rebuilding stability'],
                ['06', 'Six', 'Giving and receiving, support, fair exchange', 'Unequal exchange, dependency, strings attached'],
                ['07', 'Seven', 'Patience, investment, evaluating progress', 'Impatience, poor return, questioning effort'],
                ['08', 'Eight', 'Practice, skill-building, disciplined work', 'Perfectionism, repetitive work, lack of focus'],
                ['09', 'Nine', 'Independence, comfort, earned results', 'Overreliance on status, delayed reward, insecurity'],
                ['10', 'Ten', 'Long-term security, legacy, family resources', 'Instability, family pressure, short-term thinking'],
                ['page', 'Page', 'Learning, practical opportunity, study, new skill', 'Poor follow-through, unrealistic plans, delayed opportunity'],
                ['knight', 'Knight', 'Steady progress, reliability, disciplined effort', 'Stagnation, stubborn routine, slow movement'],
                ['queen', 'Queen', 'Practical care, resourcefulness, grounded abundance', 'Overwork, self-neglect, material anxiety'],
                ['king', 'King', 'Financial maturity, stewardship, stable leadership', 'Materialism, control, risky certainty'],
            ],
            default => [],
        };

        $cards = [];
        foreach ($rows as [$key, $rank, $upright, $reversed]) {
            $cards[] = [
                'card_id' => $suit.'_'.$key,
                'card_name' => $rank.' of '.$label,
                'number' => $rank,
                'upright_meaning' => $upright,
                'reversed_meaning' => $reversed,
            ];
        }

        return $cards;
    }
}
