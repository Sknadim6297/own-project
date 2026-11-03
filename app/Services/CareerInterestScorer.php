<?php

namespace App\Services;

use App\Support\CareerInterest\CareerInterestCatalog;
use InvalidArgumentException;

class CareerInterestScorer
{
    /**
     * Implementation convention (not a validated psychometric claim):
     * each category sums three 1–5 answers (max 15), percentage = round((sum/15)*100).
     * Displayed scores therefore range from 20% to 100%.
     */
    public function score(array $answers): array
    {
        $normalized = $this->normalizeAnswers($answers);
        $totals = [];
        $percentages = [];

        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            $ids = CareerInterestCatalog::questionIdsFor($category);
            $sum = 0;
            foreach ($ids as $id) {
                $sum += $normalized[$id];
            }
            $totals[$category] = $sum;
            $percentages[$category] = (int) round(($sum / CareerInterestCatalog::MAX_CATEGORY_SCORE) * 100);
        }

        $ranked = $this->rank($percentages);

        return [
            'scoring_version' => CareerInterestCatalog::SCORING_VERSION,
            'assessment_version' => CareerInterestCatalog::ASSESSMENT_VERSION,
            'answers' => $normalized,
            'totals' => $totals,
            'percentages' => $percentages,
            'ranked' => $ranked,
            'top' => $this->topKeys($ranked),
            'interpretation' => $this->interpretation($ranked),
        ];
    }

    private function normalizeAnswers(array $answers): array
    {
        $allowed = CareerInterestCatalog::questionIds();
        $scale = CareerInterestCatalog::scaleValues();
        $normalized = [];

        foreach ($allowed as $id) {
            if (! array_key_exists($id, $answers)) {
                throw new InvalidArgumentException('Missing answer for '.$id);
            }
            $value = (int) $answers[$id];
            if (! in_array($value, $scale, true)) {
                throw new InvalidArgumentException('Invalid answer value for '.$id);
            }
            $normalized[$id] = $value;
        }

        foreach (array_keys($answers) as $id) {
            if (! in_array($id, $allowed, true)) {
                throw new InvalidArgumentException('Unknown question id '.$id);
            }
        }

        return $normalized;
    }

    private function rank(array $percentages): array
    {
        $rows = [];
        foreach (CareerInterestCatalog::CATEGORY_ORDER as $index => $category) {
            $rows[] = [
                'key' => $category,
                'label' => CareerInterestCatalog::categories()[$category]['label'],
                'description' => CareerInterestCatalog::categories()[$category]['description'],
                'percent' => $percentages[$category],
                'order' => $index,
            ];
        }

        usort($rows, static function (array $a, array $b) {
            if ($a['percent'] === $b['percent']) {
                return $a['order'] <=> $b['order'];
            }

            return $b['percent'] <=> $a['percent'];
        });

        return $rows;
    }

    private function topKeys(array $ranked): array
    {
        if ($ranked === []) {
            return [];
        }
        $best = $ranked[0]['percent'];
        $tops = [];
        foreach ($ranked as $row) {
            if ($row['percent'] === $best) {
                $tops[] = $row['key'];
            }
        }

        return $tops;
    }

    private function interpretation(array $ranked): string
    {
        $top = array_slice($ranked, 0, min(2, count($ranked)));
        $labels = array_map(static fn (array $row) => strtolower($row['label']), $top);
        $phrase = match (count($labels)) {
            0 => 'your interests',
            1 => $labels[0].' activities',
            default => $labels[0].' and '.$labels[1].' activities',
        };

        return 'Your strongest combination suggests an interest in '.$phrase.'. These are interests to explore—not measures of aptitude or guaranteed career fit.';
    }
}
