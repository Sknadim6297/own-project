<?php

namespace App\Services;

use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use InvalidArgumentException;

class SkillsStrengthsScorer
{
    /**
     * Implementation convention (not a validated ability claim):
     * percent = round(((sum_of_3_answers - 3) / 12) * 100)
     * → 0% when all Strongly Disagree; 100% when all Strongly Agree.
     * PDF sample percentages are illustrative only.
     */
    public function score(array $answers): array
    {
        $normalized = $this->normalizeAnswers($answers);
        $totals = [];
        $percentages = [];

        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $sum = 0;
            foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
                $sum += $normalized[$id];
            }
            $totals[$category] = $sum;
            $percentages[$category] = (int) round(
                (($sum - SkillsStrengthsCatalog::MIN_CATEGORY_SCORE) / 12) * 100
            );
        }

        $ranked = $this->rank($percentages);
        $strengths = $this->pickEnds($ranked, true, 3);
        $develop = $this->pickEnds($ranked, false, 2);

        return [
            'scoring_version' => SkillsStrengthsCatalog::SCORING_VERSION,
            'assessment_version' => SkillsStrengthsCatalog::ASSESSMENT_VERSION,
            'answers' => $normalized,
            'totals' => $totals,
            'percentages' => $percentages,
            'ranked' => $ranked,
            'strengths' => $strengths,
            'develop' => $develop,
            'summary' => $this->summary($strengths, $develop),
            'meaning' => $this->meaning($strengths, $develop),
        ];
    }

    private function normalizeAnswers(array $answers): array
    {
        $allowed = SkillsStrengthsCatalog::questionIds();
        $scale = SkillsStrengthsCatalog::scaleValues();
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
        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $index => $category) {
            $meta = SkillsStrengthsCatalog::categories()[$category];
            $rows[] = [
                'key' => $category,
                'label' => $meta['label'],
                'short' => $meta['short'],
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

    /**
     * @param  array<int, array<string, mixed>>  $ranked
     * @return array<int, array<string, mixed>>
     */
    private function pickEnds(array $ranked, bool $highest, int $count): array
    {
        if ($ranked === []) {
            return [];
        }

        $ordered = $highest ? $ranked : array_reverse($ranked);
        $picked = [];
        $threshold = null;

        foreach ($ordered as $row) {
            if (count($picked) < $count) {
                $picked[] = $row;
                $threshold = $row['percent'];
                continue;
            }
            if ($threshold !== null && $row['percent'] === $threshold) {
                $picked[] = $row;
            } else {
                break;
            }
        }

        return $picked;
    }

    /**
     * @param  array<int, array<string, mixed>>  $strengths
     * @param  array<int, array<string, mixed>>  $develop
     */
    private function summary(array $strengths, array $develop): string
    {
        $labels = array_map(
            static fn (array $row) => strtolower($row['label']),
            array_slice($strengths, 0, 3)
        );

        if ($labels === []) {
            return 'Your self-ratings form a personal skill snapshot to explore—not a measure of proven ability.';
        }

        if (count($labels) === 1) {
            $phrase = $labels[0];
        } elseif (count($labels) === 2) {
            $phrase = $labels[0].' and '.$labels[1];
        } else {
            $phrase = $labels[0].', '.$labels[1].' and '.$labels[2];
        }

        return 'A profile shaped by your strongest self-ratings in '.$phrase.'.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $strengths
     * @param  array<int, array<string, mixed>>  $develop
     */
    private function meaning(array $strengths, array $develop): string
    {
        $strong = array_map(static fn (array $row) => strtolower($row['label']), array_slice($strengths, 0, 3));
        $grow = array_map(static fn (array $row) => strtolower($row['label']), array_slice($develop, 0, 2));

        $strongPhrase = match (count($strong)) {
            0 => 'several areas',
            1 => $strong[0],
            2 => $strong[0].' and '.$strong[1],
            default => $strong[0].', '.$strong[1].' and '.$strong[2],
        };
        $growPhrase = match (count($grow)) {
            0 => 'other skill areas',
            1 => $grow[0],
            default => $grow[0].' and '.$grow[1],
        };

        return 'What this means: You rate yourself more highly in '.$strongPhrase
            .'. Consider growing '.$growPhrase
            .' through small projects, practice and practical learning. These are self-ratings—not hiring or competence claims.';
    }
}
