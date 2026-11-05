<?php

namespace App\Support\CareerInterest;

class CareerInterestCatalog
{
    public const ASSESSMENT_VERSION = 'cit-2026.11';

    public const SCORING_VERSION = 'riasec-sum15-pct-v1';

    public const MAX_CATEGORY_SCORE = 15;

    /** Stable order used for ties and progress. */
    public const CATEGORY_ORDER = [
        'realistic',
        'investigative',
        'artistic',
        'social',
        'enterprising',
        'conventional',
    ];

    public const SCALE = [
        1 => 'Not at all',
        2 => 'A little',
        3 => 'Somewhat',
        4 => 'Very much',
        5 => 'Love it',
    ];

    /**
     * Presentation-only emoticons for the existing interest-intensity scale.
     * Numeric values and labels in SCALE must not change.
     */
    public const SCALE_EMOJI = [
        1 => '😣',
        2 => '🙁',
        3 => '😐',
        4 => '😊',
        5 => '🤩',
    ];

    public const AGE_GROUPS = [
        'below-15' => 'Below 15',
        '15-18' => '15–18',
        '19-24' => '19–24',
        '25-34' => '25–34',
        '35-44' => '35–44',
        '45-plus' => '45+',
    ];

    public const EDUCATION = [
        'class-8-10' => 'Class 8–10',
        'class-11-12' => 'Class 11–12',
        'diploma' => 'Diploma',
        'undergraduate' => 'Undergraduate',
        'graduate' => 'Graduate',
        'postgraduate' => 'Postgraduate',
        'other' => 'Other',
    ];

    public const SITUATIONS = [
        'student' => 'Student',
        'working' => 'Working',
        'self-employed' => 'Self-employed',
        'career-break' => 'Career break',
        'exploring' => 'Exploring',
    ];

    public const GEOGRAPHIES = [
        'india' => 'India',
        'international' => 'International',
        'open-to-both' => 'Open to both',
    ];

    public static function categories(): array
    {
        return [
            'realistic' => [
                'key' => 'realistic',
                'label' => 'Realistic',
                'kicker' => 'Practical interests',
                'description' => 'Hands-on work with tools, machines, technology, or tangible results.',
                'step' => 1,
            ],
            'investigative' => [
                'key' => 'investigative',
                'label' => 'Investigative',
                'kicker' => 'Analytical interests',
                'description' => 'Analysis, research, problem-solving, and understanding how things work.',
                'step' => 2,
            ],
            'artistic' => [
                'key' => 'artistic',
                'label' => 'Artistic',
                'kicker' => 'Creative interests',
                'description' => 'Creating ideas, designs, and self-expression through creative activities.',
                'step' => 3,
            ],
            'social' => [
                'key' => 'social',
                'label' => 'Social',
                'kicker' => 'People & helping interests',
                'description' => 'Helping, teaching, guiding, and supporting other people’s growth.',
                'step' => 4,
            ],
            'enterprising' => [
                'key' => 'enterprising',
                'label' => 'Enterprising',
                'kicker' => 'Business & leadership interests',
                'description' => 'Leading, persuading, organising people, and pursuing opportunities.',
                'step' => 5,
            ],
            'conventional' => [
                'key' => 'conventional',
                'label' => 'Conventional',
                'kicker' => 'Organisation & structured interests',
                'description' => 'Organising information, accuracy, procedures, and structured systems.',
                'step' => 6,
            ],
        ];
    }

    /**
     * Exact PDF wording. Stable IDs are keys — never store translated text as keys.
     */
    public static function questions(): array
    {
        return [
            'r1' => [
                'id' => 'r1',
                'category' => 'realistic',
                'text' => 'I enjoy building, assembling or creating things using my hands.',
            ],
            'r2' => [
                'id' => 'r2',
                'category' => 'realistic',
                'text' => 'I enjoy working with machines, equipment, tools or technology devices.',
            ],
            'r3' => [
                'id' => 'r3',
                'category' => 'realistic',
                'text' => 'I prefer practical activities where I can see or physically experience the results of my work.',
            ],
            'i1' => [
                'id' => 'i1',
                'category' => 'investigative',
                'text' => 'I enjoy analysing information, numbers or data to find patterns and insights.',
            ],
            'i2' => [
                'id' => 'i2',
                'category' => 'investigative',
                'text' => 'I like investigating why something happens and understanding how things work.',
            ],
            'i3' => [
                'id' => 'i3',
                'category' => 'investigative',
                'text' => 'I enjoy solving complex problems that require logical thinking and research.',
            ],
            'a1' => [
                'id' => 'a1',
                'category' => 'artistic',
                'text' => 'I enjoy creating original ideas, concepts or designs.',
            ],
            'a2' => [
                'id' => 'a2',
                'category' => 'artistic',
                'text' => 'I like expressing myself through writing, art, music, photography or other creative activities.',
            ],
            'a3' => [
                'id' => 'a3',
                'category' => 'artistic',
                'text' => 'I prefer activities that allow me to experiment, imagine and find my own way of doing things.',
            ],
            's1' => [
                'id' => 's1',
                'category' => 'social',
                'text' => 'I enjoy helping people understand and overcome their problems.',
            ],
            's2' => [
                'id' => 's2',
                'category' => 'social',
                'text' => 'I like teaching, guiding or sharing knowledge with others.',
            ],
            's3' => [
                'id' => 's3',
                'category' => 'social',
                'text' => 'I feel motivated when I work with people and contribute to their development.',
            ],
            'e1' => [
                'id' => 'e1',
                'category' => 'enterprising',
                'text' => 'I enjoy taking the lead and organising people to achieve a goal.',
            ],
            'e2' => [
                'id' => 'e2',
                'category' => 'enterprising',
                'text' => 'I like convincing others, negotiating and presenting ideas.',
            ],
            'e3' => [
                'id' => 'e3',
                'category' => 'enterprising',
                'text' => 'I am interested in starting a business, managing projects or identifying new opportunities.',
            ],
            'c1' => [
                'id' => 'c1',
                'category' => 'conventional',
                'text' => 'I enjoy organising information, maintaining records and keeping things systematic.',
            ],
            'c2' => [
                'id' => 'c2',
                'category' => 'conventional',
                'text' => 'I like working with numbers, financial records, spreadsheets or structured information.',
            ],
            'c3' => [
                'id' => 'c3',
                'category' => 'conventional',
                'text' => 'I prefer activities that involve planning, accuracy, clear procedures and attention to detail.',
            ],
        ];
    }

    public static function questionIds(): array
    {
        return array_keys(self::questions());
    }

    public static function questionIdsFor(string $category): array
    {
        return array_values(array_map(
            static fn (array $q) => $q['id'],
            array_filter(self::questions(), static fn (array $q) => $q['category'] === $category)
        ));
    }

    /**
     * @return array<int, array{value:int,label:string,emoji:string}>
     */
    public static function scaleOptions(): array
    {
        $options = [];
        foreach (self::SCALE as $value => $label) {
            $options[(int) $value] = [
                'value' => (int) $value,
                'label' => $label,
                'emoji' => self::SCALE_EMOJI[$value] ?? '',
            ];
        }

        return $options;
    }

    public static function progressPercent(int $step): int
    {
        $total = count(self::CATEGORY_ORDER);

        return $total > 0 ? (int) round(($step / $total) * 100) : 0;
    }

    public static function questionCount(): int
    {
        return count(self::questions());
    }

    public static function scaleValues(): array
    {
        return array_map('intval', array_keys(self::SCALE));
    }

    /**
     * Starting catalogue from the PDF, mapped to RIASEC interests.
     * Recommendations are exploratory, not guarantees.
     */
    public static function careers(): array
    {
        return [
            'financial-analysis' => [
                'id' => 'financial-analysis',
                'title' => 'Financial Analysis',
                'summary' => 'Combines numerical analysis, research and business decision-making.',
                'primary' => ['investigative', 'conventional'],
                'secondary' => ['enterprising'],
            ],
            'business-management' => [
                'id' => 'business-management',
                'title' => 'Business Management',
                'summary' => 'Involves problem-solving, leadership and organisational decisions.',
                'primary' => ['enterprising', 'conventional'],
                'secondary' => ['social', 'investigative'],
            ],
            'data-business-analytics' => [
                'id' => 'data-business-analytics',
                'title' => 'Data & Business Analytics',
                'summary' => 'Combines data interpretation, analysis and business insights.',
                'primary' => ['investigative', 'conventional'],
                'secondary' => ['enterprising'],
            ],
            'entrepreneurship' => [
                'id' => 'entrepreneurship',
                'title' => 'Entrepreneurship',
                'summary' => 'Involves identifying opportunities, planning and starting new ventures.',
                'primary' => ['enterprising'],
                'secondary' => ['artistic', 'realistic', 'investigative'],
            ],
            'management-consulting' => [
                'id' => 'management-consulting',
                'title' => 'Management Consulting',
                'summary' => 'Combines structured analysis, problem-solving and client-focused work.',
                'primary' => ['investigative', 'enterprising'],
                'secondary' => ['social', 'conventional'],
            ],
            'engineering-technology' => [
                'id' => 'engineering-technology',
                'title' => 'Engineering & Applied Technology',
                'summary' => 'Applies practical problem-solving with tools, systems and tangible outcomes.',
                'primary' => ['realistic', 'investigative'],
                'secondary' => ['conventional'],
            ],
            'creative-media-design' => [
                'id' => 'creative-media-design',
                'title' => 'Creative Media & Design',
                'summary' => 'Centres on original ideas, visual or written expression, and imaginative work.',
                'primary' => ['artistic'],
                'secondary' => ['enterprising', 'social'],
            ],
            'education-counselling' => [
                'id' => 'education-counselling',
                'title' => 'Education & Counselling',
                'summary' => 'Focuses on teaching, guiding and helping people develop and solve problems.',
                'primary' => ['social'],
                'secondary' => ['artistic', 'investigative'],
            ],
        ];
    }
}
