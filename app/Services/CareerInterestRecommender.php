<?php

namespace App\Services;

use App\Support\CareerInterest\CareerInterestCatalog;

class CareerInterestRecommender
{
    public const CATALOGUE_VERSION = 'career-map-v2';

    /**
     * Focus window: careers must align with the user's top-ranked interests.
     * Soft geography hints may only break near-ties; they never invent eligibility.
     */
    private const FOCUS_COUNT = 2;

    /** Second focus interest is included only when within this many points of #1. */
    private const FOCUS_BAND = 13;

    private const NEAR_TIE_SCORE_GAP = 40;

    /**
     * Deterministic recommendations from RIASEC percentages.
     *
     * Match formula (career-map-v2):
     * - focusKeys = top ranked interest, plus the next interest only when within FOCUS_BAND points.
     * - For each primary category in focusKeys: score += percent × 8.
     * - Non-focus primary categories do not add score (they also cannot unlock a career alone).
     * - For each secondary category: score += percent × 1; if in focusKeys, score += 20.
     * - Gate: at least one primary category must be in focusKeys and at ≥47%; otherwise excluded.
     * - Relative demotion: if the career's strongest primary percent is more than 27 points
     *   below the user's highest interest percent, multiply score by 0.35 (rounded).
     * - Sort by match_score desc, then career id asc; take up to 5 unique careers.
     * - fit_percent = round(average of the career's primary category percentages).
     */
    public function recommend(array $scored, array $profile = []): array
    {
        $percentages = $scored['percentages'] ?? [];
        $ranked = is_array($scored['ranked'] ?? null) ? $scored['ranked'] : [];
        $top = is_array($scored['top'] ?? null) ? $scored['top'] : [];
        $focusKeys = $this->focusKeys($ranked);

        $rankedCareers = [];
        foreach (CareerInterestCatalog::careers() as $career) {
            $score = $this->matchScore($career, $percentages, $focusKeys);
            if ($score <= 0) {
                continue;
            }
            $rankedCareers[] = [
                'id' => $career['id'],
                'title' => $career['title'],
                'summary' => $career['summary'],
                'why' => $this->why($career, $percentages, $focusKeys, $top),
                'fit_percent' => $this->fitPercent($career, $percentages),
                'match_score' => $score,
            ];
        }

        usort($rankedCareers, static function (array $a, array $b) {
            if ($a['match_score'] === $b['match_score']) {
                return strcmp($a['id'], $b['id']);
            }

            return $b['match_score'] <=> $a['match_score'];
        });

        $rankedCareers = $this->applySoftProfileHints($rankedCareers, $profile);
        $selected = array_slice($rankedCareers, 0, 5);

        return [
            'catalogue_version' => self::CATALOGUE_VERSION,
            'disclaimer' => 'These suggestions are a starting point for exploration based on interests—not a measure of skill, salary outlook, job availability, or a final career decision.',
            'items' => array_values($selected),
        ];
    }

    /**
     * @param  list<array{key:string,percent:int}>  $ranked
     * @return list<string>
     */
    private function focusKeys(array $ranked): array
    {
        if ($ranked === []) {
            return [];
        }

        $first = $ranked[0];
        if (! is_array($first) || ! isset($first['key'])) {
            return [];
        }

        $keys = [(string) $first['key']];
        $best = (int) ($first['percent'] ?? 0);

        for ($i = 1; $i < count($ranked) && count($keys) < self::FOCUS_COUNT; $i++) {
            $row = $ranked[$i];
            if (! is_array($row) || ! isset($row['key'])) {
                continue;
            }
            $pct = (int) ($row['percent'] ?? 0);
            if (($best - $pct) > self::FOCUS_BAND) {
                break;
            }
            $keys[] = (string) $row['key'];
        }

        return $keys;
    }

    /**
     * @param  list<string>  $focusKeys
     */
    private function matchScore(array $career, array $percentages, array $focusKeys): int
    {
        $primary = is_array($career['primary'] ?? null) ? $career['primary'] : [];
        $secondary = is_array($career['secondary'] ?? null) ? $career['secondary'] : [];

        if ($primary === []) {
            return 0;
        }

        $score = 0;
        $maxPrimaryPct = 0;
        $maxFocusPrimaryPct = 0;
        $primaryInFocus = false;

        foreach ($primary as $category) {
            $pct = (int) ($percentages[$category] ?? 0);
            $maxPrimaryPct = max($maxPrimaryPct, $pct);
            if (! in_array($category, $focusKeys, true)) {
                continue;
            }
            $primaryInFocus = true;
            $maxFocusPrimaryPct = max($maxFocusPrimaryPct, $pct);
            $score += $pct * 8;
        }

        if (! $primaryInFocus || $maxFocusPrimaryPct < 47) {
            return 0;
        }

        foreach ($secondary as $category) {
            $pct = (int) ($percentages[$category] ?? 0);
            $score += $pct;
            if (in_array($category, $focusKeys, true)) {
                $score += 20;
            }
        }

        $userMax = 0;
        foreach ($percentages as $pct) {
            $userMax = max($userMax, (int) $pct);
        }

        if ($userMax > 0 && ($userMax - $maxPrimaryPct) > 27) {
            $score = (int) round($score * 0.35);
        }

        return max(0, $score);
    }

    private function fitPercent(array $career, array $percentages): int
    {
        $primary = is_array($career['primary'] ?? null) ? $career['primary'] : [];
        if ($primary === []) {
            return 0;
        }

        $sum = 0;
        foreach ($primary as $category) {
            $sum += (int) ($percentages[$category] ?? 0);
        }

        return (int) round($sum / count($primary));
    }

    /**
     * @param  list<string>  $focusKeys
     * @param  list<string>  $top
     */
    private function why(array $career, array $percentages, array $focusKeys, array $top): string
    {
        $aligned = [];
        foreach (($career['primary'] ?? []) as $category) {
            if (! in_array($category, $focusKeys, true)) {
                continue;
            }
            $label = CareerInterestCatalog::categories()[$category]['label'] ?? $category;
            $pct = (int) ($percentages[$category] ?? 0);
            $aligned[] = $label.' ('.$pct.'%)';
        }

        if ($aligned === []) {
            foreach (($career['primary'] ?? []) as $category) {
                $label = CareerInterestCatalog::categories()[$category]['label'] ?? $category;
                $pct = (int) ($percentages[$category] ?? 0);
                $aligned[] = $label.' ('.$pct.'%)';
            }
        }

        $joined = match (count($aligned)) {
            0 => 'your interest profile',
            1 => $aligned[0],
            2 => $aligned[0].' and '.$aligned[1],
            default => implode(', ', array_slice($aligned, 0, -1)).', and '.$aligned[array_key_last($aligned)],
        };

        $tieNote = count($top) > 1
            ? ' Your strongest interest scores are tied, so related areas are shown together.'
            : '';

        $noun = count($aligned) === 1 ? 'interest suggests' : 'interests suggest';

        return 'Your stronger '.$joined.' '.$noun
            .' that you may enjoy exploring '.$career['title']
            .' as a reflective option—not a guaranteed fit.'.$tieNote;
    }

    /**
     * Soft preference only: international geography may break near-ties toward
     * consulting / entrepreneurship / analytics without overriding clear score gaps.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function applySoftProfileHints(array $items, array $profile): array
    {
        $geo = $profile['geography'] ?? null;
        if ($geo !== 'international' || count($items) < 2) {
            return $items;
        }

        $boost = [
            'management-consulting' => 2,
            'entrepreneurship' => 1,
            'data-business-analytics' => 1,
            'marketing-communications' => 1,
        ];

        usort($items, static function (array $a, array $b) use ($boost) {
            $scoreGap = abs(($a['match_score'] ?? 0) - ($b['match_score'] ?? 0));
            if ($scoreGap > self::NEAR_TIE_SCORE_GAP) {
                return ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0);
            }

            $as = $boost[$a['id']] ?? 0;
            $bs = $boost[$b['id']] ?? 0;
            if ($as !== $bs) {
                return $bs <=> $as;
            }
            if (($a['match_score'] ?? 0) === ($b['match_score'] ?? 0)) {
                return strcmp((string) $a['id'], (string) $b['id']);
            }

            return ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0);
        });

        return $items;
    }
}
