<?php

namespace App\Support\Pfm;

class PfmCatalog
{
    public const MODULE_KEY = 'money-finance';

    public const CONTENT_VERSION = 'pfm-logic-v2';

    public const TOOLS = [
        'monthly-money-planner' => [
            'name' => 'Monthly Money Planner',
            'slug' => 'monthly-money-planner',
            'route' => 'tools.pfm.money-planner',
            'description' => 'See free cash, savings rate and a simple monthly money picture.',
            'icon' => 'ri-wallet-3-line',
            'display_order' => 1,
        ],
        'net-worth-calculator' => [
            'name' => 'Net Worth Calculator',
            'slug' => 'net-worth-calculator',
            'route' => 'tools.pfm.net-worth',
            'description' => 'Add assets and liabilities to estimate your net worth.',
            'icon' => 'ri-pie-chart-2-line',
            'display_order' => 2,
        ],
        'emergency-fund-calculator' => [
            'name' => 'Emergency Fund Calculator',
            'slug' => 'emergency-fund-calculator',
            'route' => 'tools.pfm.emergency-fund',
            'description' => 'Estimate your emergency fund target, coverage and shortfall.',
            'icon' => 'ri-shield-check-line',
            'display_order' => 3,
        ],
        'financial-goal-calculator' => [
            'name' => 'Financial Goal Calculator',
            'slug' => 'financial-goal-calculator',
            'route' => 'tools.pfm.financial-goal',
            'description' => 'Plan the monthly investment needed for a future goal.',
            'icon' => 'ri-flag-2-line',
            'display_order' => 4,
        ],
        'debt-emi-calculator' => [
            'name' => 'Debt & EMI Calculator',
            'slug' => 'debt-emi-calculator',
            'route' => 'tools.pfm.debt-emi',
            'description' => 'Calculate EMI and compare accelerated payoff options.',
            'icon' => 'ri-bank-card-line',
            'display_order' => 5,
        ],
        'retirement-calculator' => [
            'name' => 'Retirement Calculator',
            'slug' => 'retirement-calculator',
            'route' => 'tools.pfm.retirement',
            'description' => 'Estimate retirement corpus and the monthly investment gap.',
            'icon' => 'ri-user-heart-line',
            'display_order' => 6,
        ],
        'inflation-calculator' => [
            'name' => 'Inflation Calculator',
            'slug' => 'inflation-calculator',
            'route' => 'tools.pfm.inflation',
            'description' => 'See how inflation can change future costs over time.',
            'icon' => 'ri-line-chart-line',
            'display_order' => 7,
        ],
        'savings-investment-calculator' => [
            'name' => 'Savings & Investment Calculator',
            'slug' => 'savings-investment-calculator',
            'route' => 'tools.pfm.savings-investment',
            'description' => 'Estimate future corpus from initial and monthly contributions.',
            'icon' => 'ri-funds-line',
            'display_order' => 8,
        ],
    ];

    public const GOAL_TYPES = [
        'home' => 'Home',
        'education' => 'Education',
        'car' => 'Car',
        'marriage' => 'Marriage',
        'vacation' => 'Vacation',
        'child' => 'Child',
        'custom' => 'Custom',
    ];

    public const HEALTH_THRESHOLDS = [
        'healthy' => 0.20,
        'fair' => 0.10,
    ];

    public static function toolKeys(): array
    {
        return array_keys(self::TOOLS);
    }

    public static function tool(string $key): ?array
    {
        return self::TOOLS[$key] ?? null;
    }
}
