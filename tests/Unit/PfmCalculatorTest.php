<?php

namespace Tests\Unit;

use App\Services\Pfm\PfmCalculator;
use InvalidArgumentException;
use Tests\TestCase;

class PfmCalculatorTest extends TestCase
{
    private PfmCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PfmCalculator;
    }

    public function test_mp01_free_cash_and_savings_rate(): void
    {
        $payload = $this->calculator->calculate('monthly-money-planner', [
            'monthly_income' => 80000,
            'essential_expenses' => 50000,
            'lifestyle_expenses' => 0,
            'emis' => 0,
            'other_recurring' => 0,
            'investments' => 15000,
        ]);

        $this->assertSame(15000.0, $payload['result']['free_cash']);
        $this->assertSame(37.5, $payload['result']['savings_rate_percent']);
    }

    public function test_mp02_income_zero_is_blocked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculate('monthly-money-planner', [
            'monthly_income' => 0,
            'essential_expenses' => 1000,
            'lifestyle_expenses' => 0,
            'investments' => 0,
        ]);
    }

    public function test_nw01_net_worth(): void
    {
        $payload = $this->calculator->calculate('net-worth-calculator', [
            'cash' => 1000000,
            'loans' => 400000,
        ]);

        $this->assertSame(600000.0, $payload['result']['net_worth']);
        $this->assertSame(0.4, $payload['result']['debt_asset_ratio']);
    }

    public function test_nw02_zero_assets_skips_ratio(): void
    {
        $payload = $this->calculator->calculate('net-worth-calculator', [
            'cash' => 0,
            'loans' => 1000,
        ]);

        $this->assertNull($payload['result']['debt_asset_ratio']);
        $this->assertSame(-1000.0, $payload['result']['net_worth']);
    }

    public function test_ef01_and_ef02_target_and_shortfall(): void
    {
        $payload = $this->calculator->calculate('emergency-fund-calculator', [
            'monthly_expenses' => 45000,
            'coverage_months' => 6,
            'current_savings' => 150000,
            'monthly_saving_capacity' => 10000,
        ]);

        $this->assertSame(270000.0, $payload['result']['target_amount']);
        $this->assertSame(120000.0, $payload['result']['shortfall']);
        $this->assertSame(12.0, $payload['result']['months_to_target']);
    }

    public function test_fg01_zero_inflation_keeps_current_cost(): void
    {
        $payload = $this->calculator->calculate('financial-goal-calculator', [
            'goal_type' => 'custom',
            'current_cost' => 500000,
            'years' => 5,
            'inflation_percent' => 0,
            'expected_return_percent' => 10,
            'existing_savings' => 0,
        ]);

        $this->assertSame(500000.0, $payload['result']['required_corpus']);
    }

    public function test_fg02_zero_return_uses_corpus_over_months(): void
    {
        $payload = $this->calculator->calculate('financial-goal-calculator', [
            'goal_type' => 'home',
            'current_cost' => 120000,
            'years' => 1,
            'inflation_percent' => 0,
            'expected_return_percent' => 0,
            'existing_savings' => 0,
        ]);

        $this->assertSame(10000.0, $payload['result']['monthly_investment']);
    }

    public function test_de01_zero_rate_emi(): void
    {
        $payload = $this->calculator->calculate('debt-emi-calculator', [
            'principal' => 120000,
            'annual_rate_percent' => 0,
            'tenure_years' => 1,
            'extra_payment' => 0,
        ]);

        $this->assertSame(10000.0, $payload['result']['emi']);
    }

    public function test_de02_extra_payment_saves_interest(): void
    {
        $payload = $this->calculator->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'annual_rate_percent' => 12,
            'tenure_years' => 5,
            'extra_payment' => 5000,
        ]);

        $this->assertGreaterThan(0, $payload['result']['interest_saved']);
        $this->assertGreaterThan(0, $payload['result']['months_saved']);
    }

    public function test_rt01_invalid_retirement_age(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculate('retirement-calculator', [
            'current_age' => 40,
            'retirement_age' => 40,
            'life_expectancy' => 85,
            'current_monthly_expenses' => 50000,
            'inflation_percent' => 6,
            'pre_retirement_return_percent' => 10,
            'post_retirement_return_percent' => 6,
            'current_assets' => 0,
        ]);
    }

    public function test_in01_zero_inflation(): void
    {
        $payload = $this->calculator->calculate('inflation-calculator', [
            'current_amount' => 10000,
            'inflation_percent' => 0,
            'years' => 10,
        ]);

        $this->assertSame(10000.0, $payload['result']['future_cost']);
    }

    public function test_si01_zero_return_corpus(): void
    {
        $payload = $this->calculator->calculate('savings-investment-calculator', [
            'initial_amount' => 10000,
            'monthly_contribution' => 1000,
            'expected_return_percent' => 0,
            'years' => 1,
        ]);

        $this->assertSame(22000.0, $payload['result']['corpus']);
    }

    public function test_si02_monthly_zero_grows_initial_only(): void
    {
        $payload = $this->calculator->calculate('savings-investment-calculator', [
            'initial_amount' => 10000,
            'monthly_contribution' => 0,
            'expected_return_percent' => 12,
            'years' => 1,
        ]);

        $this->assertSame(0.0, $payload['result']['fv_contributions']);
        $this->assertGreaterThan(10000.0, $payload['result']['corpus']);
    }
}
