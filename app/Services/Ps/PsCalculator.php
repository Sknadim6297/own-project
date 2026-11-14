<?php

namespace App\Services\Ps;

use App\Support\ParentingSpirituality\PsCatalog;
use App\Support\ParentingSpirituality\PsContent;
use InvalidArgumentException;

class PsCalculator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    public function calculate(string $toolKey, array $input): array
    {
        return match ($toolKey) {
            'parenting-stress-check' => $this->stressCheck($input),
            'child-routine-planner' => $this->routinePlanner($input),
            'parent-child-conversation-guide' => $this->conversationGuide($input),
            'daily-mindfulness-calm' => $this->mindfulness($input),
            'purpose-values-finder' => $this->valuesFinder($input),
            default => throw new InvalidArgumentException('Unknown Parenting & Spirituality tool.'),
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function stressCheck(array $input): array
    {
        $questions = PsContent::stressQuestions();
        $answers = [];
        $adjusted = [];
        $byArea = [];

        foreach ($questions as $q) {
            $key = 'q'.$q['id'];
            $raw = isset($input[$key]) ? (int) $input[$key] : 0;
            if ($raw < 1 || $raw > 5) {
                throw new InvalidArgumentException('Please answer all eight questions on a 1–5 scale.');
            }
            $score = $q['reverse'] ? (6 - $raw) : $raw;
            $answers[$key] = $raw;
            $adjusted[$key] = $score;
            $byArea[$q['area']] = $score;
        }

        $total = array_sum($adjusted);
        $minimum = 8;
        $maximum = 40;
        $normalizedScore = (int) round((($total - $minimum) / ($maximum - $minimum)) * 100);
        $normalizedScore = max(0, min(100, $normalizedScore));

        $level = $normalizedScore <= 33 ? 'Low' : ($normalizedScore <= 66 ? 'Moderate' : 'High');

        $groups = PsContent::stressAreaGroups();
        $groupScores = [];
        foreach ($groups as $groupKey => $areas) {
            $vals = [];
            foreach ($areas as $area) {
                if (isset($byArea[$area])) {
                    $vals[] = $byArea[$area];
                }
            }
            $groupScores[$groupKey] = $vals === [] ? 0.0 : array_sum($vals) / count($vals);
        }
        arsort($groupScores);

        $areaLabelMap = [
            'overload' => 'Overload',
            'exhaustion' => 'Exhaustion',
            'guilt_patience' => 'Guilt / Patience',
            'support_recovery' => 'Support / Recovery',
        ];

        $pressureAreas = [];
        $actions = PsContent::stressActions();
        $count = 0;
        foreach ($groupScores as $groupKey => $avg) {
            if ($count >= 3) {
                break;
            }
            $representative = $groups[$groupKey][0];
            // Prefer the highest individual area inside the group for action copy.
            $bestArea = $representative;
            $bestScore = -1;
            foreach ($groups[$groupKey] as $area) {
                if (($byArea[$area] ?? 0) > $bestScore) {
                    $bestScore = $byArea[$area];
                    $bestArea = $area;
                }
            }
            $action = $actions[$bestArea] ?? null;
            $pressureAreas[] = [
                'group' => $groupKey,
                'label' => $areaLabelMap[$groupKey] ?? $bestArea,
                'area' => $bestArea,
                'average' => round($avg, 2),
                'action_title' => $action['title'] ?? '',
                'action_content' => $action['content'] ?? '',
            ];
            $count++;
        }

        $showSupport = $normalizedScore >= 67 || (($answers['q5'] ?? 5) <= 2 && ($answers['q8'] ?? 1) >= 4);

        $normalized = $answers;
        $result = [
            'tool_key' => 'parenting-stress-check',
            'content_version' => PsCatalog::CONTENT_VERSION,
            'score' => $normalizedScore,
            'raw_total' => $total,
            'level' => $level,
            'pressure_areas' => $pressureAreas,
            'show_support_option' => $showSupport,
            'disclaimer' => PsCatalog::DISCLAIMER,
            'summary' => sprintf('Stress level: %s (%d/100)', $level, $normalizedScore),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function routinePlanner(array $input): array
    {
        $age = (int) ($input['child_age'] ?? 0);
        if ($age < 3 || $age > 18) {
            throw new InvalidArgumentException('Please enter a child age between 3 and 18.');
        }

        $wake = $this->parseTime($input['wake_time'] ?? null, 'Wake time');
        $bed = $this->parseTime($input['bed_time'] ?? null, 'Bedtime');
        $schoolStart = $this->parseTime($input['school_start'] ?? null, 'School start');
        $schoolEnd = $this->parseTime($input['school_end'] ?? null, 'School end');

        if ($bed <= $wake) {
            throw new InvalidArgumentException('Bedtime must be after wake time.');
        }
        if ($schoolEnd <= $schoolStart) {
            throw new InvalidArgumentException('School end must be after school start.');
        }
        if ($schoolStart < $wake || $schoolEnd > $bed) {
            throw new InvalidArgumentException('School timing must sit between wake and bedtime.');
        }

        $study = max(0, (int) ($input['study_minutes'] ?? 0));
        $play = max(0, (int) ($input['play_minutes'] ?? 0));
        $screen = max(0, (int) ($input['screen_minutes'] ?? 0));
        $family = max(0, (int) ($input['family_minutes'] ?? 0));
        $commute = max(0, (int) ($input['commute_minutes'] ?? 0));
        $tuitionStart = trim((string) ($input['tuition_start'] ?? ''));
        $tuitionMinutes = max(0, (int) ($input['tuition_minutes'] ?? 0));
        $mealLunch = $this->parseOptionalTime($input['lunch_time'] ?? null);
        $preferences = trim((string) ($input['preferences'] ?? ''));
        if (mb_strlen($preferences) > 500) {
            throw new InvalidArgumentException('Preferences must be 500 characters or fewer.');
        }

        $blocks = [];
        $blocks[] = $this->block('Wake', $wake, $wake, 'anchor');
        $blocks[] = $this->block('Breakfast', $wake, $wake + 30, 'meal');

        $schoolWindowStart = $schoolStart;
        if ($commute > 0) {
            $blocks[] = $this->block('Commute to school', $schoolStart - $commute, $schoolStart, 'fixed');
            $schoolWindowStart = $schoolStart - $commute;
        }
        $blocks[] = $this->block('School / daycare', $schoolStart, $schoolEnd, 'fixed');
        $afterSchool = $schoolEnd;
        if ($commute > 0) {
            $blocks[] = $this->block('Commute home', $schoolEnd, $schoolEnd + $commute, 'fixed');
            $afterSchool = $schoolEnd + $commute;
        }

        if ($tuitionStart !== '' && $tuitionMinutes > 0) {
            $tStart = $this->parseTime($tuitionStart, 'Tuition start');
            $tEnd = $tStart + $tuitionMinutes;
            if ($this->overlapsFixed($blocks, $tStart, $tEnd)) {
                throw new InvalidArgumentException('Tuition overlaps another fixed commitment. Adjust the time.');
            }
            $blocks[] = $this->block('Tuition / activity', $tStart, $tEnd, 'fixed');
        }

        if ($mealLunch !== null) {
            $blocks[] = $this->block('Lunch', $mealLunch, $mealLunch + 30, 'meal');
        }

        $cursor = max($afterSchool, $schoolEnd);
        // Skip past any tuition that lands after school.
        foreach ($blocks as $b) {
            if ($b['type'] === 'fixed' && $b['end'] > $cursor && $b['start'] >= $afterSchool) {
                $cursor = max($cursor, $b['end']);
            }
        }

        $flexible = [
            ['label' => 'Study / homework', 'minutes' => $study, 'key' => 'study'],
            ['label' => 'Play / outdoor', 'minutes' => $play, 'key' => 'play'],
            ['label' => 'Family time', 'minutes' => $family, 'key' => 'family'],
            ['label' => 'Screen time', 'minutes' => $screen, 'key' => 'screen'],
        ];

        $available = max(0, ($bed - 45) - $cursor);
        $requested = array_sum(array_column($flexible, 'minutes'));
        $conflict = $requested > $available;
        $tips = [];

        if ($conflict) {
            $tips[] = 'Requested activities exceed available evening time. Shorten study, play or screen blocks.';
            $scale = $available > 0 ? ($available / $requested) : 0;
            foreach ($flexible as &$item) {
                $item['minutes'] = (int) floor($item['minutes'] * $scale);
            }
            unset($item);
        }

        $blocks[] = $this->block('Snack / settle', $cursor, $cursor + 15, 'buffer');
        $cursor += 15;

        foreach ($flexible as $item) {
            if ($item['minutes'] <= 0) {
                continue;
            }
            $end = $cursor + $item['minutes'];
            if ($end > $bed - 30) {
                $end = $bed - 30;
            }
            if ($end <= $cursor) {
                continue;
            }
            $blocks[] = $this->block($item['label'], $cursor, $end, 'flexible');
            $cursor = $end;
        }

        if ($cursor < $bed - 45) {
            $blocks[] = $this->block('Buffer / free time', $cursor, $bed - 45, 'buffer');
            $cursor = $bed - 45;
        }

        $blocks[] = $this->block('Dinner / wind-down', max($cursor, $bed - 45), $bed, 'meal');
        $blocks[] = $this->block('Bedtime', $bed, $bed, 'anchor');

        usort($blocks, fn ($a, $b) => $a['start'] <=> $b['start']);

        // Wake is morning, bed is evening: sleep spans midnight.
        $sleepMinutes = (24 * 60 - $bed) + $wake;

        $balance = [
            'sleep_hours' => round($sleepMinutes / 60, 1),
            'study_minutes' => $study,
            'play_minutes' => $play,
            'screen_minutes' => $screen,
            'family_minutes' => $family,
        ];

        if ($balance['sleep_hours'] < 8) {
            $tips[] = 'Sleep opportunity looks short for this age. Consider an earlier bedtime if practical.';
        }
        if ($screen > 120) {
            $tips[] = 'Screen time is high relative to a balanced day—consider swapping some minutes for play or family time.';
        }
        if ($play === 0 && $age <= 12) {
            $tips[] = 'Add some outdoor or play time to balance study and fixed commitments.';
        }
        if ($tips === []) {
            $tips[] = 'Keep wake, meals and bedtime stable; edit flexible blocks as needed.';
            $tips[] = 'Protect at least one short buffer so the day is not fully packed.';
        }

        $normalized = [
            'child_age' => $age,
            'wake_time' => $this->formatTime($wake),
            'bed_time' => $this->formatTime($bed),
            'school_start' => $this->formatTime($schoolStart),
            'school_end' => $this->formatTime($schoolEnd),
            'study_minutes' => $study,
            'play_minutes' => $play,
            'screen_minutes' => $screen,
            'family_minutes' => $family,
            'commute_minutes' => $commute,
            'tuition_start' => $tuitionStart,
            'tuition_minutes' => $tuitionMinutes,
            'lunch_time' => $mealLunch !== null ? $this->formatTime($mealLunch) : '',
            'preferences' => $preferences,
        ];

        $result = [
            'tool_key' => 'child-routine-planner',
            'content_version' => PsCatalog::CONTENT_VERSION,
            'blocks' => array_map(function (array $b) {
                return [
                    'label' => $b['label'],
                    'type' => $b['type'],
                    'start' => $this->formatTime($b['start']),
                    'end' => $this->formatTime($b['end']),
                    'start_minutes' => $b['start'],
                    'end_minutes' => $b['end'],
                ];
            }, $blocks),
            'balance' => $balance,
            'conflict' => $conflict,
            'tips' => array_slice($tips, 0, 4),
            'disclaimer' => PsCatalog::DISCLAIMER,
            'summary' => $conflict
                ? 'Routine generated with conflicts—review suggested adjustments.'
                : sprintf('Daily routine for age %d · %d blocks', $age, count($blocks)),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function conversationGuide(array $input): array
    {
        $situation = (string) ($input['situation'] ?? '');
        $ageBand = (string) ($input['age_band'] ?? '');
        $goal = (string) ($input['goal'] ?? '');
        $context = trim((string) ($input['context'] ?? ''));

        $allowedSituations = ['screen_time', 'studies', 'anger', 'friends', 'confidence', 'discipline'];
        $allowedAges = ['5-7', '8-12', '13-17'];
        $allowedGoals = ['understand', 'boundary', 'reassure', 'repair'];

        if (! in_array($situation, $allowedSituations, true)) {
            throw new InvalidArgumentException('Please choose a conversation situation.');
        }
        if (! in_array($ageBand, $allowedAges, true)) {
            throw new InvalidArgumentException('Please choose a child age group.');
        }
        if (! in_array($goal, $allowedGoals, true)) {
            throw new InvalidArgumentException('Please choose a conversation goal.');
        }
        if ($context === '' || mb_strlen($context) < 10) {
            throw new InvalidArgumentException('Please share a short context (at least 10 characters).');
        }
        if (mb_strlen($context) > 1000) {
            throw new InvalidArgumentException('Context must be 1000 characters or fewer.');
        }

        $urgent = $this->detectUrgentSupport($context);
        $guide = $this->matchConversationGuide($situation, $ageBand, $goal);

        $normalized = [
            'situation' => $situation,
            'age_band' => $ageBand,
            'goal' => $goal,
            'context' => $context,
        ];

        $result = [
            'tool_key' => 'parent-child-conversation-guide',
            'content_version' => PsCatalog::CONTENT_VERSION,
            'situation_label' => $guide['situation_label'],
            'goal_label' => $guide['goal_label'],
            'age_band' => $ageBand,
            'opening' => $guide['opening'],
            'say' => $guide['say'],
            'avoid' => $guide['avoid'],
            'shutdown' => $guide['shutdown'],
            'next' => $guide['next'],
            'urgent_support' => $urgent,
            'disclaimer' => PsCatalog::DISCLAIMER,
            'summary' => sprintf('%s · Age %s · %s', $guide['situation_label'], $ageBand, $guide['goal_label']),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function mindfulness(array $input): array
    {
        $state = (string) ($input['state'] ?? '');
        $duration = (int) ($input['duration'] ?? 0);
        $practicePref = trim((string) ($input['practice'] ?? ''));
        $completedSeconds = isset($input['completed_seconds']) ? (int) $input['completed_seconds'] : null;

        $allowedStates = ['stressed', 'restless', 'angry', 'distracted', 'overwhelmed', 'general_calm'];
        $allowedDurations = [3, 5, 10];
        if (! in_array($state, $allowedStates, true)) {
            throw new InvalidArgumentException('Please choose your current state.');
        }
        if (! in_array($duration, $allowedDurations, true)) {
            throw new InvalidArgumentException('Please choose a 3, 5 or 10-minute practice.');
        }

        $practices = PsContent::mindfulnessPractices();
        $match = null;
        foreach ($practices as $row) {
            if ($row['state'] === $state) {
                $match = $row;
                break;
            }
        }
        if ($match === null) {
            $match = $practices[5];
        }

        // Prefer workbook duration for the matched state; allow user override of duration for timer.
        $practiceName = $practicePref !== '' ? $practicePref : $match['practice'];
        $allowedPractices = ['Breathing', 'Body scan', 'Grounding', 'Quiet focus'];
        if (! in_array($practiceName, $allowedPractices, true)) {
            $practiceName = $match['practice'];
        }

        // If user picked a practice that matches another state's script and state is general, keep script from state.
        $script = $match['script'];
        $purpose = $match['purpose'];
        foreach ($practices as $row) {
            if ($row['practice'] === $practiceName && $row['duration'] === $duration) {
                // Prefer exact duration+practice when available without ignoring state mapping.
                if ($row['state'] === $state || $state === 'general_calm') {
                    $script = $row['script'];
                    $purpose = $row['purpose'];
                }
            }
        }

        $targetSeconds = $duration * 60;
        $completed = $completedSeconds !== null ? max(0, min($completedSeconds, $targetSeconds)) : $targetSeconds;
        $completionRatio = $targetSeconds > 0 ? ($completed / $targetSeconds) : 1.0;

        $normalized = [
            'state' => $state,
            'duration' => $duration,
            'practice' => $practiceName,
            'completed_seconds' => $completed,
        ];

        $result = [
            'tool_key' => 'daily-mindfulness-calm',
            'content_version' => PsCatalog::CONTENT_VERSION,
            'state' => $state,
            'state_label' => str_replace('_', ' ', ucfirst($state)),
            'duration' => $duration,
            'practice' => $practiceName,
            'purpose' => $purpose,
            'script' => $script,
            'target_seconds' => $targetSeconds,
            'completed_seconds' => $completed,
            'completed' => $completionRatio >= 0.9,
            'disclaimer' => PsCatalog::DISCLAIMER,
            'summary' => sprintf('%d-minute %s · %s', $duration, $practiceName, str_replace('_', ' ', $state)),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function valuesFinder(array $input): array
    {
        $pairs = PsContent::valuePairs();
        $scores = [];
        $choices = [];

        foreach ($pairs as $pair) {
            $key = 'q'.$pair['q'];
            $choice = (string) ($input[$key] ?? '');
            if ($choice !== $pair['a'] && $choice !== $pair['b']) {
                throw new InvalidArgumentException('Please complete all sixteen value comparisons.');
            }
            $choices[$key] = $choice;
            $scores[$choice] = ($scores[$choice] ?? 0) + 1;
        }

        $ranking = [];
        if (! empty($input['ranking']) && is_array($input['ranking'])) {
            foreach ($input['ranking'] as $value) {
                $value = (string) $value;
                if ($value !== '' && ! in_array($value, $ranking, true)) {
                    $ranking[] = $value;
                }
            }
        }

        uksort($scores, function (string $a, string $b) use ($scores, $ranking) {
            if ($scores[$a] !== $scores[$b]) {
                return $scores[$b] <=> $scores[$a];
            }
            $posA = array_search($a, $ranking, true);
            $posB = array_search($b, $ranking, true);
            if ($posA !== false || $posB !== false) {
                if ($posA === false) {
                    return 1;
                }
                if ($posB === false) {
                    return -1;
                }

                return $posA <=> $posB;
            }

            return strcmp($a, $b);
        });

        $top = array_slice(array_keys($scores), 0, 5);
        $mappings = PsContent::valueMappings();
        $themes = [];
        $actions = [];
        foreach ($top as $value) {
            $map = $mappings[$value] ?? null;
            if (! $map) {
                continue;
            }
            $theme = $map['theme'];
            $themes[$theme] = ($themes[$theme] ?? 0) + 1;
            if (count($actions) < 3) {
                $actions[] = [
                    'value' => $value,
                    'action' => $map['action'],
                ];
            }
        }
        arsort($themes);
        $themeList = array_slice(array_keys($themes), 0, 3);

        $reflection = 'Looking at your top values, what is one small change you could make this week that would feel more aligned?';
        $wantMore = trim((string) ($input['want_more'] ?? ''));
        if (mb_strlen($wantMore) > 500) {
            throw new InvalidArgumentException('Reflection text must be 500 characters or fewer.');
        }

        $normalized = array_merge($choices, [
            'ranking' => $ranking,
            'want_more' => $wantMore,
        ]);

        $result = [
            'tool_key' => 'purpose-values-finder',
            'content_version' => PsCatalog::CONTENT_VERSION,
            'scores' => $scores,
            'top_values' => $top,
            'themes' => $themeList,
            'actions' => $actions,
            'reflection' => $reflection,
            'disclaimer' => PsCatalog::DISCLAIMER,
            'summary' => 'Top values: '.implode(', ', $top),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @return array{label:string,start:int,end:int,type:string}
     */
    private function block(string $label, int $start, int $end, string $type): array
    {
        return compact('label', 'start', 'end', 'type');
    }

    /**
     * @param  list<array{start:int,end:int,type:string}>  $blocks
     */
    private function overlapsFixed(array $blocks, int $start, int $end): bool
    {
        foreach ($blocks as $block) {
            if (! in_array($block['type'], ['fixed', 'meal', 'anchor'], true)) {
                continue;
            }
            if ($start < $block['end'] && $end > $block['start']) {
                return true;
            }
        }

        return false;
    }

    private function parseTime(mixed $value, string $label): int
    {
        $raw = trim((string) $value);
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $raw, $m)) {
            throw new InvalidArgumentException($label.' must use HH:MM format.');
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        if ($h > 23 || $min > 59) {
            throw new InvalidArgumentException($label.' is not a valid time.');
        }

        return ($h * 60) + $min;
    }

    private function parseOptionalTime(mixed $value): ?int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        return $this->parseTime($raw, 'Meal time');
    }

    private function formatTime(int $minutes): string
    {
        $minutes = $minutes % (24 * 60);
        if ($minutes < 0) {
            $minutes += 24 * 60;
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * @return array<string, string>
     */
    private function matchConversationGuide(string $situation, string $ageBand, string $goal): array
    {
        $rows = PsContent::conversationGuides();
        $candidates = array_values(array_filter($rows, fn ($r) => $r['situation'] === $situation));
        if ($candidates === []) {
            $candidates = $rows;
        }

        usort($candidates, function (array $a, array $b) use ($ageBand, $goal) {
            $scoreA = $this->guideFit($a, $ageBand, $goal);
            $scoreB = $this->guideFit($b, $ageBand, $goal);

            return $scoreB <=> $scoreA;
        });

        return $candidates[0];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function guideFit(array $row, string $ageBand, string $goal): int
    {
        $score = 0;
        if ($row['goal'] === $goal) {
            $score += 3;
        }
        if ($row['age_band'] === $ageBand || $row['age_band'] === '8-17') {
            $score += 2;
        } elseif ($this->ageOverlaps($row['age_band'], $ageBand)) {
            $score += 1;
        }

        return $score;
    }

    private function ageOverlaps(string $rowBand, string $selected): bool
    {
        $parse = function (string $band): array {
            if (! preg_match('/^(\d+)-(\d+)$/', $band, $m)) {
                return [0, 0];
            }

            return [(int) $m[1], (int) $m[2]];
        };
        [$a1, $a2] = $parse($rowBand);
        [$b1, $b2] = $parse($selected);

        return $a1 <= $b2 && $b1 <= $a2;
    }

    private function detectUrgentSupport(string $context): bool
    {
        $lower = mb_strtolower($context);
        $needles = ['abuse', 'self-harm', 'suicide', 'kill myself', 'hurt myself', 'unsafe', 'violence', 'molest'];
        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }
}
