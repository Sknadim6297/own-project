<?php

namespace App\Services;

use App\Support\CareerInterest\CareerInterestCatalog;

class CareerInterestRecommender
{
    public const CATALOGUE_VERSION = 'career-map-v1';

    /**
     * Deterministic recommendations from RIASEC percentages.
     * Geography/education may only soft-filter when clearly relevant; they never invent eligibility claims.
     */
    public function recommend(array $scored, array $profile = []): array
    {
        $percentages = $scored['percentages'] ?? [];
        $top = $scored['top'] ?? [];
        $ranked = [];

        foreach (CareerInterestCatalog::careers() as $career) {
            $score = $this->matchScore($career, $percentages, $top);
            if ($score <= 0) {
                continue;
            }
            $ranked[] = [
                'id' => $career['id'],
                'title' => $career['title'],
                'summary' => $career['summary'],
                'why' => $this->why($career, $percentages, $top),
                'match_score' => $score,
            ];
        }

        usort($ranked, static function (array $a, array $b) {
            if ($a['match_score'] === $b['match_score']) {
                return strcmp($a['id'], $b['id']);
            }

            return $b['match_score'] <=> $a['match_score'];
        });

        $selected = array_slice($ranked, 0, 5);
        $selected = $this->applySoftProfileHints($selected, $profile);

        return [
            'catalogue_version' => self::CATALOGUE_VERSION,
            'disclaimer' => 'These suggestions are a starting point for exploration based on interests—not a measure of skill, salary outlook, job availability, or a final career decision.',
            'items' => array_values($selected),
        ];
    }

    private function matchScore(array $career, array $percentages, array $top): int
    {
        $score = 0;
        foreach ($career['primary'] as $category) {
            $score += (int) ($percentages[$category] ?? 0) * 3;
            if (in_array($category, $top, true)) {
                $score += 40;
            }
        }
        foreach ($career['secondary'] as $category) {
            $score += (int) ($percentages[$category] ?? 0);
            if (in_array($category, $top, true)) {
                $score += 10;
            }
        }

        // Require at least one primary interest at 47%+ to avoid weak matches.
        $primaryStrong = false;
        foreach ($career['primary'] as $category) {
            if (($percentages[$category] ?? 0) >= 47) {
                $primaryStrong = true;
                break;
            }
        }

        return $primaryStrong ? $score : 0;
    }

    private function why(array $career, array $percentages, array $top): string
    {
        $parts = [];
        foreach ($career['primary'] as $category) {
            $label = CareerInterestCatalog::categories()[$category]['label'] ?? $category;
            $pct = $percentages[$category] ?? 0;
            $parts[] = $label.' ('.$pct.'%)';
        }
        $joined = implode(' and ', $parts);
        $tieNote = count($top) > 1 ? ' Your top interests are tied, so related areas are shown together.' : '';

        return 'Suggested because your profile emphasises '.$joined.'.'.$tieNote;
    }

    private function applySoftProfileHints(array $items, array $profile): array
    {
        // Soft preference only: international geography lightly boosts consulting/entrepreneurship order already set.
        // No hard exclusions — avoid unsupported eligibility claims.
        $geo = $profile['geography'] ?? null;
        if ($geo === 'international') {
            usort($items, static function (array $a, array $b) {
                $boost = ['management-consulting' => 2, 'entrepreneurship' => 1, 'data-business-analytics' => 1];
                $as = $boost[$a['id']] ?? 0;
                $bs = $boost[$b['id']] ?? 0;
                if ($as === $bs) {
                    if ($a['match_score'] === $b['match_score']) {
                        return strcmp($a['id'], $b['id']);
                    }

                    return $b['match_score'] <=> $a['match_score'];
                }

                return $bs <=> $as;
            });
        }

        return array_slice($items, 0, 5);
    }
}
