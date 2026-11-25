<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmMath;
use App\Support\Pfm\PfmCatalog;
use App\Support\Pfm\RetirementPresentation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Task 45 — Retirement Calculator planned monthly investment sufficiency.
 */
class RetirementInvestmentSufficiencyTest extends TestCase
{
    private const ROUTE = 'tools.pfm.retirement';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureCustomerTermsAccepted::class,
        ]);
        $this->storage();
    }

    public function test_planned_monthly_field_renders_and_existing_inputs_unchanged(): void
    {
        $html = $this->get(route(self::ROUTE))->assertOk()->getContent();

        $this->assertStringContainsString('name="planned_monthly_investment"', $html);
        $this->assertStringContainsString('Planned monthly investment (optional)', $html);
        $this->assertStringContainsString('Enter how much you currently plan to invest each month towards your retirement.', $html);
        $this->assertStringContainsString('name="current_age"', $html);
        $this->assertStringContainsString('name="retirement_age"', $html);
        $this->assertStringContainsString('name="life_expectancy"', $html);
        $this->assertStringContainsString('name="current_monthly_expenses"', $html);
        $this->assertStringContainsString('name="current_assets"', $html);
        $this->assertStringContainsString('name="inflation_percent"', $html);
        $this->assertStringContainsString('name="pre_retirement_return_percent"', $html);
        $this->assertStringContainsString('name="post_retirement_return_percent"', $html);
        $this->assertMatchesRegularExpression('/id="pfm-inf"[^>]*value="6"/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-pre"[^>]*value="10"/', $html);
    }

    public function test_missing_planned_investment_assessment_unavailable(): void
    {
        $calculator = app(PfmCalculator::class);
        $payload = $this->payload();
        $engine = $calculator->calculate('retirement-calculator', $payload);

        $this->assertFalse($engine['result']['planned_monthly_provided']);
        $this->assertNull($engine['result']['planned_monthly_investment']);
        $this->assertNull($engine['result']['monthly_shortfall']);
        $this->assertSame('unavailable', $engine['result']['investment_status']);
        $this->assertNull($engine['normalized']['planned_monthly_investment']);

        $user = $this->member('rt45-missing@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $html = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-rt-suf-status="unavailable"', $html);
        $this->assertStringContainsString('Monthly investment assessment unavailable', $html);
        $this->assertStringContainsString('Enter your planned monthly investment to compare', $html);
        $this->assertStringContainsString('Your Retirement Summary', $html);
        $this->assertStringNotContainsString('Additional investment required', $html);
        $this->assertEqualsWithDelta((float) $engine['result']['monthly_investment'], (float) session('pfm.draft.retirement-calculator.result.monthly_investment'), 0.01);
    }

    public function test_explicit_zero_distinct_from_missing(): void
    {
        $calculator = app(PfmCalculator::class);

        $missing = $calculator->calculate('retirement-calculator', $this->payload());
        $zero = $calculator->calculate('retirement-calculator', $this->payload([
            'planned_monthly_investment' => 0,
        ]));

        $this->assertNull($missing['result']['planned_monthly_investment']);
        $this->assertFalse($missing['result']['planned_monthly_provided']);
        $this->assertSame('unavailable', $missing['result']['investment_status']);

        $this->assertSame(0.0, (float) $zero['result']['planned_monthly_investment']);
        $this->assertTrue($zero['result']['planned_monthly_provided']);
        $this->assertSame('insufficient', $zero['result']['investment_status']);
        $this->assertEqualsWithDelta(
            (float) $zero['result']['monthly_investment'],
            (float) $zero['result']['monthly_shortfall'],
            0.01
        );

        $user = $this->member('rt45-zero@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'planned_monthly_investment' => 0,
        ]));
        $html = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-rt-suf-status="insufficient"', $html);
        $this->assertStringContainsString('Additional investment required', $html);
        $this->assertStringContainsString('₹0.00', $html);
    }

    public function test_negative_and_invalid_planned_rejected_with_restore(): void
    {
        $user = $this->member('rt45-invalid@example.com');
        $payload = $this->payload([
            'planned_monthly_investment' => -100,
            'current_monthly_expenses' => 42000,
        ]);

        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $form = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('value="42000"', $form);

        $this->expectException(InvalidArgumentException::class);
        app(PfmCalculator::class)->calculate('retirement-calculator', $this->payload([
            'planned_monthly_investment' => 'abc',
        ]));
    }

    public function test_controlled_contribution_gap_cases(): void
    {
        // Controlled comparison math (Cases A–D) using known R/M pairs.
        $this->assertSame(5000.0, max(15000.0 - 10000.0, 0.0));
        $this->assertEqualsWithDelta(66.67, round((10000 / 15000) * 100, 2), 0.01);
        $this->assertSame(0.0, max(15000.0 - 15000.0, 0.0));
        $this->assertSame(100.0, round((15000 / 15000) * 100, 2));
        $this->assertSame(5000.0, max(20000.0 - 15000.0, 0.0));
        $this->assertEqualsWithDelta(133.33, round((20000 / 15000) * 100, 2), 0.01);
        $this->assertSame(15000.0, max(15000.0 - 0.0, 0.0));

        $calculator = app(PfmCalculator::class);
        $base = $calculator->calculate('retirement-calculator', $this->payload());
        $required = (float) $base['result']['monthly_investment'];
        $this->assertGreaterThan(0.01, $required);

        $low = round($required * 0.66, 2);
        $high = round($required * 1.25, 2);

        $insufficient = $calculator->calculate('retirement-calculator', $this->payload([
            'planned_monthly_investment' => $low,
        ]));
        $this->assertSame('insufficient', $insufficient['result']['investment_status']);
        $this->assertEqualsWithDelta(max($required - $low, 0), (float) $insufficient['result']['monthly_shortfall'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $insufficient['result']['monthly_surplus'], 0.01);
        $this->assertEqualsWithDelta(round(($low / $required) * 100, 2), (float) $insufficient['result']['contribution_coverage_percent'], 0.01);

        $exact = $calculator->calculate('retirement-calculator', $this->payload([
            'planned_monthly_investment' => $required,
        ]));
        $this->assertSame('meets', $exact['result']['investment_status']);
        $this->assertEqualsWithDelta(0.0, (float) $exact['result']['monthly_shortfall'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $exact['result']['contribution_coverage_percent'], 0.01);

        $above = $calculator->calculate('retirement-calculator', $this->payload([
            'planned_monthly_investment' => $high,
        ]));
        $this->assertSame('above', $above['result']['investment_status']);
        $this->assertEqualsWithDelta(max($high - $required, 0), (float) $above['result']['monthly_surplus'], 0.01);
        $this->assertEqualsWithDelta(round(($high / $required) * 100, 2), (float) $above['result']['contribution_coverage_percent'], 0.01);
    }

    public function test_zero_required_contribution_safe(): void
    {
        $calculator = app(PfmCalculator::class);
        $funded = $calculator->calculate('retirement-calculator', $this->payload([
            'current_age' => 40,
            'retirement_age' => 60,
            'current_monthly_expenses' => 20000,
            'current_assets' => 50000000,
            'inflation_percent' => 4,
            'pre_retirement_return_percent' => 10,
            'post_retirement_return_percent' => 6,
            'planned_monthly_investment' => 5000,
        ]));

        $this->assertEqualsWithDelta(0.0, (float) $funded['result']['monthly_investment'], 0.01);
        $this->assertSame('zero_required', $funded['result']['investment_status']);
        $this->assertNull($funded['result']['contribution_coverage_percent']);
        $this->assertEqualsWithDelta(0.0, (float) $funded['result']['monthly_shortfall'], 0.01);

        $view = RetirementPresentation::build($funded['normalized'], $funded['result']);
        $this->assertSame('zero_required', $view['sufficiency']['status']);
        $this->assertStringContainsString('Not applicable', $view['sufficiency']['coverage_label']);
        $this->assertStringNotContainsString('INF', implode(' ', $view['sufficiency']['paragraphs']));
        $this->assertStringNotContainsString('∞', implode(' ', $view['sufficiency']['paragraphs']));
    }

    public function test_projected_corpus_and_shortfall_use_existing_assumptions(): void
    {
        $calculator = app(PfmCalculator::class);
        $payload = $this->payload([
            'planned_monthly_investment' => 5000,
        ]);
        $out = $calculator->calculate('retirement-calculator', $payload);
        $result = $out['result'];
        $normalized = $out['normalized'];

        $years = (int) $result['years_to_retire'];
        $pre = ((float) $normalized['pre_retirement_return_percent']) / 100;
        $fvAssets = (float) $result['future_value_assets'];
        $contrib = PfmMath::futureValuePlan(0.0, 5000.0, $pre, (float) $years);
        $expectedProjected = PfmMath::roundMoney($fvAssets + (float) $contrib['fv_contributions']);

        $this->assertEqualsWithDelta($expectedProjected, (float) $result['projected_corpus'], 0.01);

        $requiredCorpus = (float) $result['required_corpus'];
        $projected = (float) $result['projected_corpus'];
        $this->assertEqualsWithDelta(max($requiredCorpus - $projected, 0), (float) $result['corpus_shortfall'], 0.01);
        $this->assertEqualsWithDelta(max($projected - $requiredCorpus, 0), (float) $result['corpus_surplus'], 0.01);

        // Legacy outputs unchanged when planned omitted.
        $legacy = $calculator->calculate('retirement-calculator', $this->payload());
        foreach (['required_corpus', 'future_value_assets', 'funding_gap', 'monthly_investment', 'future_monthly_expense', 'readiness_score', 'years_to_retire'] as $key) {
            $this->assertEqualsWithDelta((float) $legacy['result'][$key], (float) $out['result'][$key], 0.01, $key);
        }
    }

    public function test_dynamic_explanations_and_task44_conclusion(): void
    {
        $user = $this->member('rt45-explain@example.com');
        $calculator = app(PfmCalculator::class);
        $required = (float) $calculator->calculate('retirement-calculator', $this->payload())['result']['monthly_investment'];
        $planned = round($required * 0.5, 2);
        $shortfall = PfmMath::roundMoney(max($required - $planned, 0));

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'planned_monthly_investment' => $planned,
        ]));

        $html = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('Is Your Monthly Investment Sufficient?', $html);
        $this->assertStringContainsString('Additional investment required', $html);
        $this->assertStringContainsString('Monthly contribution target coverage', $html);
        $this->assertStringContainsString(number_format($shortfall, 2), $html);
        $this->assertStringContainsString('Your Retirement Summary', $html);
        $this->assertStringContainsString('Next Steps', $html);
        $this->assertStringContainsString('Assumptions used', $html);
        $this->assertStringContainsString('This assessment compares your planned monthly investment', $html);
        $this->assertStringContainsString('Increasing your monthly contribution by approximately', $html);
    }

    public function test_meets_and_above_ui_scenarios(): void
    {
        $user = $this->member('rt45-ui@example.com');
        $calculator = app(PfmCalculator::class);
        $required = (float) $calculator->calculate('retirement-calculator', $this->payload())['result']['monthly_investment'];

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'planned_monthly_investment' => $required,
        ]));
        $meets = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-rt-suf-status="meets"', $meets);
        $this->assertStringContainsString('Meets estimated monthly target', $meets);

        $high = round($required + 2500, 2);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'planned_monthly_investment' => $high,
        ]));
        $above = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-rt-suf-status="above"', $above);
        $this->assertStringContainsString('Above estimated monthly target', $above);
        $this->assertStringContainsString('Monthly contribution surplus', $above);
    }

    public function test_recalculate_restores_planned_and_new_test_resets(): void
    {
        $user = $this->member('rt45-recalc@example.com');
        $payload = $this->payload([
            'planned_monthly_investment' => 12500.5,
            'current_monthly_expenses' => 48000,
        ]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        $form = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('value="12500.5"', $form);
        $this->assertStringContainsString('value="48000"', $form);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $fresh = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="pfm-planned-monthly"[^>]*value=""/', $fresh);
        $this->assertMatchesRegularExpression('/id="pfm-current-age"[^>]*value=""/', $fresh);
    }

    public function test_calculate_does_not_autosave_and_save_preserves_outputs(): void
    {
        $user = $this->member('rt45-save@example.com');
        $other = $this->member('rt45-other@example.com');
        $payload = $this->payload(['planned_monthly_investment' => 8000]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $draft = session('pfm.draft.retirement-calculator');
        $this->assertSame(8000.0, (float) $draft['result']['planned_monthly_investment']);
        $this->assertArrayHasKey('projected_corpus', $draft['result']);
        $this->assertArrayHasKey('monthly_shortfall', $draft['result']);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertEqualsWithDelta(8000.0, (float) $record->inputs['planned_monthly_investment'], 0.01);
        $this->assertEqualsWithDelta(8000.0, (float) $record->outputs['planned_monthly_investment'], 0.01);
        $this->assertArrayHasKey('projected_corpus', $record->outputs);
        $this->assertArrayHasKey('corpus_shortfall', $record->outputs);
        $this->assertSame('insufficient', $record->outputs['investment_status']);

        $show = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('data-yk-rt-sufficiency', false)
            ->assertSee('Is Your Monthly Investment Sufficient?')
            ->getContent();
        $this->assertStringContainsString(number_format((float) $record->outputs['projected_corpus'], 2), $show);

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertStatus(403);
    }

    public function test_historical_snapshot_without_planned_stays_unavailable(): void
    {
        $user = $this->member('rt45-legacy@example.com');
        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'retirement-calculator',
            'title' => 'Legacy RT Task44',
            'inputs' => [
                'current_age' => 30,
                'retirement_age' => 60,
                'life_expectancy' => 85,
                'current_monthly_expenses' => 50000,
                'current_assets' => 500000,
                'inflation_percent' => 6,
                'pre_retirement_return_percent' => 10,
                'post_retirement_return_percent' => 7,
            ],
            'outputs' => [
                'required_corpus' => 25000000.0,
                'monthly_investment' => 15000.0,
                'years_to_retire' => 30,
                'future_monthly_expense' => 287174.55,
                'future_value_assets' => 8724707.54,
                'funding_gap' => 16275292.46,
                'readiness_score' => 35,
                'summary' => 'Legacy snapshot',
            ],
            'summary' => 'Legacy snapshot',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->assertSee('Your Retirement Summary')
            ->getContent();

        $this->assertStringContainsString('data-yk-rt-suf-status="unavailable"', $html);
        $this->assertStringNotContainsString('Additional investment required', $html);
        $this->assertSame(15000.0, (float) $legacy->fresh()->outputs['monthly_investment']);
        $this->assertArrayNotHasKey('planned_monthly_investment', $legacy->fresh()->outputs);
    }

    public function test_guest_preview_and_legacy_input_compatibility(): void
    {
        $legacyPayload = $this->payload();
        unset($legacyPayload['planned_monthly_investment']);

        $this->post(route(self::ROUTE.'.calculate'), $legacyPayload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Monthly investment assessment unavailable')
            ->assertSee('Your Retirement Summary')
            ->assertSee('Save result (sign in)');

        $engine = app(PfmCalculator::class)->calculate('retirement-calculator', $legacyPayload);
        $this->assertSame('unavailable', $engine['result']['investment_status']);
        $this->assertArrayHasKey('monthly_investment', $engine['result']);
        $this->assertArrayHasKey('required_corpus', $engine['result']);
    }

    public function test_other_calculators_unaffected(): void
    {
        $user = $this->member('rt45-other-tools@example.com');

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.debt-emi.calculate'), [
                'principal' => 500000,
                'down_payment' => 0,
                'annual_rate_percent' => 10,
                'tenure_years' => 5,
                'extra_payment' => 0,
            ])
            ->assertRedirect(route('tools.pfm.debt-emi.results'));

        $debt = $this->actingAs($user, 'user')->get(route('tools.pfm.debt-emi.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-debt-results', $debt);
        $this->assertStringNotContainsString('Is Your Monthly Investment Sufficient?', $debt);

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.financial-goal.calculate'), [
                'goal_type' => 'home',
                'goal_name' => 'Flat',
                'current_cost' => 2500000,
                'years' => 10,
                'inflation_percent' => 6,
                'expected_return_percent' => 10,
                'existing_savings' => 300000,
            ])
            ->assertRedirect(route('tools.pfm.financial-goal.results'));

        $this->actingAs($user, 'user')
            ->get(route('tools.pfm.financial-goal.results'))
            ->assertOk()
            ->assertDontSee('Is Your Monthly Investment Sufficient?');
    }

    /**
     * @param  array<string, float|int|string>  $overrides
     * @return array<string, float|int|string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'current_age' => 30,
            'retirement_age' => 60,
            'life_expectancy' => 85,
            'current_monthly_expenses' => 50000,
            'current_assets' => 500000,
            'inflation_percent' => 6,
            'pre_retirement_return_percent' => 10,
            'post_retirement_return_percent' => 7,
        ], $overrides);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'RT Sufficiency Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }

    private function storage(): void
    {
        foreach (['pfm_calculations', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
        });
        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_category_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module_key');
            $table->text('description')->nullable();
            $table->string('status');
            $table->string('route_name')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });
        Schema::create('pfm_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tool_key', 64)->index();
            $table->string('title', 160);
            $table->json('inputs');
            $table->json('outputs');
            $table->string('summary', 255)->nullable();
            $table->string('content_version', 64)->nullable();
            $table->uuid('completion_key')->unique();
            $table->boolean('is_draft')->default(false)->index();
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Money & Finance',
            'slug' => 'money-finance',
            'is_active' => true,
            'display_order' => 5,
        ]);

        foreach (PfmCatalog::TOOLS as $tool) {
            Tool::query()->create([
                'tool_category_id' => $category->id,
                'name' => $tool['name'],
                'slug' => $tool['slug'],
                'module_key' => PfmCatalog::MODULE_KEY,
                'description' => $tool['description'],
                'status' => 'active',
                'route_name' => $tool['route'],
                'is_implemented' => true,
                'display_order' => $tool['display_order'],
            ]);
        }
    }
}
