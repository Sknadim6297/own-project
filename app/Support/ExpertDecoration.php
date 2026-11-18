<?php

namespace App\Support;

/**
 * Presentation-only mapping: expert specialization / service text -> decorative
 * card illustration (public/assets/experts/decorations/{asset}.svg) + pastel tone.
 *
 * Rules are evaluated in priority order. The expert's specialization (role) is
 * tried first; if nothing matches, the combined service/topic text is tried;
 * finally the catalogue category icon is used, then a neutral fallback.
 */
class ExpertDecoration
{
    public const FALLBACK = 'default-spark';

    /** [needles (substring, lowercase), asset, tone] */
    private const RULES = [
        [['tarot'], 'tarot-cards', 'lavender'],
        [['astro', 'vastu', 'horoscope', 'numerolog', 'palm'], 'astrology-moon', 'lavender'],
        [['resume', 'interview', 'cv writ'], 'career-target', 'peach'],
        [['transition', 'career switch'], 'career-rocket', 'peach'],
        [['productiv', 'time manage'], 'productivity-bars', 'mint'],
        [['relation', 'couple', 'marriage', 'dating', 'breakup'], 'relationship-hearts', 'rose'],
        [['parent', 'child', 'teen', 'family'], 'parenting-family', 'rose'],
        [['psycholog', 'psychiatr', 'mindset'], 'psychology-brain', 'pink'],
        [['mental', 'therap', 'anxiety', 'depress', 'stress', 'emotion'], 'mental-health-heart', 'pink'],
        [['meditat', 'mindful', 'zen'], 'meditation-stones', 'blush'],
        [['spirit', 'energy', 'reiki', 'chakra'], 'spirituality-lotus', 'blush'],
        [['nutrition', 'diet', 'food'], 'nutrition-apple', 'mint'],
        [['fitness', 'trainer', 'workout', 'gym', 'sport'], 'fitness-dumbbell', 'mint'],
        [['wellness', 'lifestyle', 'yoga', 'healing', 'health', 'holistic', 'life coach', 'personal growth', 'influencer', 'fashion', 'image', 'style'], 'wellness-sprout', 'mint'],
        [['finance', 'financial', 'money', 'tax', 'account', 'invest', 'estate', 'wealth'], 'finance-coins', 'cream'],
        [['educat', 'academic', 'teacher', 'tutor', 'school', 'college', 'student', 'study'], 'education-cap', 'lavender'],
        [['tech', 'software', 'developer', 'digital', 'coding', 'data '], 'technology-laptop', 'lavender'],
        [['business', 'entrepreneur', 'startup', 'marketing', 'brand', 'consult'], 'business-briefcase', 'peach'],
        [['career', 'job', 'coach', 'counsel'], 'career-growth', 'peach'],
    ];

    /** Thematically close illustrations, tried (in order) when the preferred one is already used. */
    private const RELATED = [
        'astrology-moon' => ['tarot-cards', 'spirituality-lotus', 'meditation-stones'],
        'tarot-cards' => ['astrology-moon', 'spirituality-lotus', 'meditation-stones'],
        'psychology-brain' => ['mental-health-heart', 'meditation-stones', 'spirituality-lotus'],
        'mental-health-heart' => ['psychology-brain', 'meditation-stones', 'wellness-sprout'],
        'wellness-sprout' => ['meditation-stones', 'nutrition-apple', 'spirituality-lotus', 'mental-health-heart'],
        'nutrition-apple' => ['wellness-sprout', 'fitness-dumbbell', 'meditation-stones'],
        'fitness-dumbbell' => ['nutrition-apple', 'wellness-sprout', 'productivity-bars'],
        'career-growth' => ['career-rocket', 'career-target', 'business-briefcase', 'productivity-bars'],
        'career-rocket' => ['career-growth', 'career-target', 'productivity-bars', 'business-briefcase'],
        'career-target' => ['career-growth', 'career-rocket', 'business-briefcase', 'productivity-bars'],
        'business-briefcase' => ['career-growth', 'finance-coins', 'career-target', 'career-rocket'],
        'finance-coins' => ['business-briefcase', 'career-growth', 'productivity-bars'],
        'relationship-hearts' => ['mental-health-heart', 'parenting-family', 'spirituality-lotus'],
        'parenting-family' => ['relationship-hearts', 'education-cap', 'mental-health-heart'],
        'education-cap' => ['career-growth', 'technology-laptop', 'productivity-bars'],
        'technology-laptop' => ['education-cap', 'productivity-bars', 'business-briefcase'],
        'spirituality-lotus' => ['meditation-stones', 'astrology-moon', 'wellness-sprout'],
        'meditation-stones' => ['spirituality-lotus', 'wellness-sprout', 'astrology-moon'],
        'productivity-bars' => ['career-growth', 'career-rocket', 'business-briefcase'],
    ];
    /** Catalogue category icon (ExploreCatalog::CATEGORIES) -> fallback asset. */
    private const ICON_FALLBACK = [
        'lotus' => ['spirituality-lotus', 'blush'],
        'moon' => ['astrology-moon', 'lavender'],
        'heart' => ['relationship-hearts', 'rose'],
        'chart' => ['career-growth', 'peach'],
        'coins' => ['finance-coins', 'cream'],
        'leaf' => ['wellness-sprout', 'mint'],
        'family' => ['parenting-family', 'rose'],
        'cup' => ['wellness-sprout', 'mint'],
        'fitness' => ['fitness-dumbbell', 'mint'],
        'bulb' => ['default-spark', 'pink'],
        'spark' => ['default-spark', 'pink'],
    ];

    /**
     * @param  iterable<string|null>  $texts  extra text (services, topics) used when the role has no match
     * @return array{asset: string, tone: string}
     */
    public static function resolve(?string $role, iterable $texts = [], ?string $categoryIcon = null): array
    {
        $match = self::match((string) $role);
        if ($match === null) {
            $joined = collect($texts)->filter(fn ($t) => is_string($t) && $t !== '')->implode(' ');
            $match = self::match($joined);
        }
        if ($match === null && $categoryIcon !== null && isset(self::ICON_FALLBACK[$categoryIcon])) {
            $match = self::ICON_FALLBACK[$categoryIcon];
        }

        $match ??= [self::FALLBACK, 'pink'];

        return ['asset' => $match[0], 'tone' => $match[1]];
    }

    /**
     * Give every card in a result set its own illustration (no repeats while
     * unused illustrations remain). Deterministic: cards are processed in
     * display order; a taken illustration is swapped for the next-best match
     * (other keyword matches, then the same tone, then any unused one).
     *
     * @param  iterable<array>  $cards  expert card arrays (role, topics, chips, icon, id)
     * @return array<int|string, array{asset: string, tone: string}>  keyed by card id
     */
    public static function assign(iterable $cards): array
    {
        $tones = [self::FALLBACK => 'pink'];
        foreach (self::RULES as [, $asset, $tone]) {
            $tones[$asset] ??= $tone;
        }

        $used = [];
        $result = [];

        foreach ($cards as $card) {
            $role = (string) ($card['role'] ?? '');
            $texts = array_merge((array) ($card['topics'] ?? []), (array) ($card['chips'] ?? []));
            $icon = $card['icon'] ?? null;

            $preferred = self::resolve($role, $texts, $icon)['asset'];
            $queue = [$preferred];

            foreach (array_merge([$role], [implode(' ', array_filter($texts, 'is_string'))]) as $text) {
                foreach (self::matchAll($text) as $asset) {
                    $queue[] = $asset;
                }
            }
            if ($icon !== null && isset(self::ICON_FALLBACK[$icon])) {
                $queue[] = self::ICON_FALLBACK[$icon][0];
            }

            foreach (self::RELATED[$preferred] ?? [] as $asset) {
                $queue[] = $asset;
            }

            // Same-tone siblings, then everything else.
            $tone = $tones[$preferred];
            foreach ($tones as $asset => $t) {
                if ($t === $tone) {
                    $queue[] = $asset;
                }
            }
            foreach ($tones as $asset => $t) {
                $queue[] = $asset;
            }

            $pick = null;
            foreach ($queue as $asset) {
                if ($asset !== self::FALLBACK && ! isset($used[$asset])) {
                    $pick = $asset;
                    break;
                }
            }
            $pick ??= $preferred; // everything used (more cards than illustrations)

            $used[$pick] = true;
            $result[$card['id'] ?? count($result)] = ['asset' => $pick, 'tone' => $tones[$pick]];
        }

        return $result;
    }

    /** @return list<string> every illustration whose keywords appear in $text, in priority order */
    private static function matchAll(string $text): array
    {
        $haystack = mb_strtolower(trim($text)).' ';
        $found = [];
        if (trim($haystack) === '') {
            return $found;
        }
        foreach (self::RULES as [$needles, $asset]) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    $found[] = $asset;
                    break;
                }
            }
        }

        return $found;
    }
    private static function match(string $text): ?array
    {
        $haystack = mb_strtolower(trim($text)).' ';
        if (trim($haystack) === '') {
            return null;
        }

        foreach (self::RULES as [$needles, $asset, $tone]) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return [$asset, $tone];
                }
            }
        }

        return null;
    }
}
