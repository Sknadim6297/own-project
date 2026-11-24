<?php

namespace App\Support\Tools;

/**
 * Task 39 — Page-level short tool leads for individual tool entry screens.
 *
 * Intentionally separate from ToolsCatalog / PfmCatalog / PsCatalog descriptions
 * so Explore Tools cards, hubs, and SEO meta stay unchanged.
 */
class ToolPageLeads
{
    public const PFM_DISCLAIMER = 'Educational estimates only—not financial advice.';

    /**
     * @var array<string, string>
     */
    private const LEADS = [
        // Mental Wellness
        'daily-mood-check-in' => 'Reflect on your current mood, understand your emotions, and build greater self-awareness.',
        'digital-journal' => 'Capture your thoughts and reflect on your experiences in a personal digital journal.',
        'gratitude-journal' => 'Reflect on meaningful moments and build a regular gratitude practice.',
        'daily-mindfulness-calm' => 'Take a moment to relax, focus, and practise guided mindfulness.',
        'purpose-values-finder' => 'Explore your personal values and discover what matters most to you.',
        'parenting-stress-check' => 'Reflect on parenting challenges and identify areas where additional support may help.',
        'child-routine-planner' => 'Organize your child\'s daily activities and build a consistent routine.',
        'parent-child-conversation-guide' => 'Explore practical prompts for meaningful conversations with your child.',

        // Career
        'career-interest-test' => 'Explore your interests and discover career paths aligned with your preferences.',
        'skills-strengths-assessment' => 'Identify your strengths and explore opportunities for personal and professional growth.',

        // Nutrition & Fitness
        'water-intake-tracker' => 'Estimate your daily water needs and track your hydration progress.',
        'calorie-nutrition-tracker' => 'Explore nutritional information for available foods in the catalogue.',
        'bmi-bmr-calculator' => 'Estimate body mass index, metabolic rate, and daily energy requirements.',
        'weight-journey' => 'Record your weight and monitor changes over time.',

        // Tarot
        'tarot-reflection' => 'Explore symbolic card meanings through guided personal reflection.',

        // Investments / PFM (page lead only — disclaimer appended at render for calculators)
        'monthly-money-planner' => 'Plan your monthly income, expenses, and savings in one place.',
        'net-worth-calculator' => 'Understand your financial position by comparing your assets and liabilities.',
        'emergency-fund-calculator' => 'Estimate how much you need to save for unexpected financial emergencies.',
        'financial-goal-calculator' => 'Estimate the monthly investment needed to achieve your financial goal.',
        'debt-emi-calculator' => 'Estimate your monthly loan repayments and total borrowing costs.',
        'retirement-calculator' => 'Estimate the savings and investments needed to prepare for retirement.',
        'inflation-calculator' => 'Understand how inflation may affect the future cost of your expenses.',
        'savings-investment-calculator' => 'Estimate how your savings and investments could grow over time.',
    ];

    public static function for(string $slugOrToolKey): ?string
    {
        return self::LEADS[$slugOrToolKey] ?? null;
    }

    /**
     * PFM calculator entry lead with educational disclaimer (page-level only).
     */
    public static function forPfm(string $toolKey): string
    {
        $lead = self::for($toolKey) ?? '';

        if ($lead === '') {
            return self::PFM_DISCLAIMER;
        }

        return rtrim($lead, '.').'. '.self::PFM_DISCLAIMER;
    }

    /**
     * BMI page lead includes medical limitation in one short block.
     */
    public static function forBmi(): string
    {
        return self::for('bmi-bmr-calculator').' Educational estimates only—not medical advice.';
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::LEADS;
    }
}
