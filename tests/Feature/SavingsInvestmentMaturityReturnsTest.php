<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use App\Support\Pfm\SavingsInvestmentPresentation;
use App\Support\Pfm\SavingsInvestmentProjection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 51 — Savings & Investment maturity amount & returns presentation.
 */
class SavingsInvestmentMaturityReturnsTest extends TestCase
{
    private const ROUTE = 'tools.pfm.savings-investment';

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

    public function test_results_render_maturity_kpi_breakdown_and_charts(): void
    {
        $payload = $this->payload();
        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $html = $this->get(route(self::ROUTE.'.results'))->assertOk()->getContent();

        $this->assertStringContainsString('Your Investment Results', $html);
        $this->assertStringContainsString('Estimated Maturity Amount', $html);
        $this->assertStringContainsString('1,294,929.04', $html);
        $this->assertStringContainsString('700,000.00', $html);
        $this->assertStringContainsString('594,929.04', $html);
        $this->assertStringContainsString('Investment breakdown', $html);
        $this->assertStringContainsString('Grown value of initial investment', $html);
        $this->assertStringContainsString('Invested vs gains', $html);
        $this->assertStringContainsString('Investment growth over time', $html);
        $this->assertStringContainsString('Year-wise investment projection', $html);
        $this->assertStringContainsString('Investment plan details', $html);
        $this->assertStringContainsString('Estimated Total Gain (%)', $html);
        $this->assertStringContainsString('Your Investment Conclusion', $html);
        $this->assertStringContainsString('Assumptions used', $html);
        $this->assertStringContainsString('data-yk-si-growth-chart', $html);
        $this->assertStringContainsString('data-yk-si-projection-table', $html);
        $this->assertStringContainsString('is not an annual return, CAGR, or XIRR', $html);
    }

    public function test_case_a_monthly_baseline_reconciles_across_presentation(): void
    {
        $payload = $this->payload();
        $calc = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload);
        $view = SavingsInvestmentPresentation::build($calc['normalized'], $calc['result']);
        $projection = $view['projection'];

        $this->assertEqualsWithDelta(1294929.04, (float) $view['corpus'], 0.001);
        $this->assertEqualsWithDelta(700000.0, (float) $view['invested'], 0.001);
        $this->assertEqualsWithDelta(594929.04, (float) $view['gains'], 0.001);
        $this->assertSame(120, (int) $view['contribution_count']);
        $this->assertEqualsWithDelta(84.99, (float) $view['total_gain_percent'], 0.02);
        $this->assertTrue($view['donut']['available']);
        $this->assertTrue($projection['available']);
        $this->assertEqualsWithDelta(700000.0, (float) $projection['final_invested'], 0.001);
        $this->assertEqualsWithDelta(1294929.04, (float) $projection['final_value'], 0.001);

        $last = end($projection['year_rows']);
        $this->assertEqualsWithDelta(1294929.04, (float) $last['value'], 0.001);
        $this->assertEqualsWithDelta(700000.0, (float) $last['invested'], 0.001);

        $this->assertStringContainsString('With a total investment of ₹700,000.00 over 10 years', $view['paragraphs'][1] ?? implode(' ', $view['paragraphs']));
    }

    public function test_case_b_quarterly_presentation_and_projection(): void
    {
        $payload = $this->payload([
            'investment_frequency' => 'quarterly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
        ]);
        $calc = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload);
        $view = SavingsInvestmentPresentation::build($calc['normalized'], $calc['result']);

        $this->assertSame(40, (int) $view['contribution_count']);
        $this->assertEqualsWithDelta(300000.0, (float) $view['invested'], 0.001);
        $this->assertEqualsWithDelta(609283.12, (float) $view['corpus'], 0.001);
        $this->assertEqualsWithDelta(309283.12, (float) $view['gains'], 0.001);
        $this->assertEqualsWithDelta(200000.0, (float) $view['recurring_total'], 0.001);
        $this->assertEqualsWithDelta(609283.12, (float) $view['projection']['final_value'], 0.001);

        $this->post(route(self::ROUTE.'.calculate'), $payload)->assertRedirect(route(self::ROUTE.'.results'));
        $html = $this->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('609,283.12', $html);
        $this->assertStringContainsString('every three months', $html);
        $this->assertStringContainsString('Quarterly', $html);
    }

    public function test_case_c_and_d_half_yearly_and_yearly(): void
    {
        $half = app(PfmCalculator::class)->calculate('savings-investment-calculator', $this->payload([
            'investment_frequency' => 'half_yearly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
        ]));
        $year = app(PfmCalculator::class)->calculate('savings-investment-calculator', $this->payload([
            'investment_frequency' => 'yearly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
        ]));

        $this->assertSame(20, (int) $half['result']['contribution_count']);
        $this->assertEqualsWithDelta(200000.0, (float) $half['result']['invested'], 0.001);
        $this->assertEqualsWithDelta(437886.39, (float) $half['result']['corpus'], 0.001);

        $this->assertSame(10, (int) $year['result']['contribution_count']);
        $this->assertEqualsWithDelta(150000.0, (float) $year['result']['invested'], 0.001);
        $this->assertEqualsWithDelta(352214.58, (float) $year['result']['corpus'], 0.001);

        $halfView = SavingsInvestmentPresentation::build($half['normalized'], $half['result']);
        $yearView = SavingsInvestmentPresentation::build($year['normalized'], $year['result']);
        $this->assertEqualsWithDelta((float) $half['result']['corpus'], (float) $halfView['projection']['final_value'], 0.001);
        $this->assertEqualsWithDelta((float) $year['result']['corpus'], (float) $yearView['projection']['final_value'], 0.001);
    }

    public function test_case_e_lump_sum_excludes_recurring_details(): void
    {
        $payload = $this->payload([
            'initial_amount' => 100000,
            'contribution_amount' => 0,
            'monthly_contribution' => 0,
            'investment_frequency' => 'yearly',
        ]);
        $calc = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload);
        $view = SavingsInvestmentPresentation::build($calc['normalized'], $calc['result']);

        $this->assertSame('lump_sum', $view['base_scenario']);
        $this->assertFalse($view['has_contribution']);
        $this->assertSame(0, (int) $view['contribution_count']);
        $this->assertEqualsWithDelta(100000.0, (float) $view['invested'], 0.001);

        $this->post(route(self::ROUTE.'.calculate'), $payload)->assertRedirect(route(self::ROUTE.'.results'));
        $html = $this->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('Lump Sum', $html);
        $this->assertStringNotContainsString('Amount per contribution', $html);
        $this->assertStringNotContainsString('Scheduled contribution count', $html);
    }

    public function test_case_f_zero_return(): void
    {
        $payload = $this->payload(['expected_return_percent' => 0]);
        $calc = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload);
        $view = SavingsInvestmentPresentation::build($calc['normalized'], $calc['result']);

        $this->assertEqualsWithDelta((float) $view['invested'], (float) $view['corpus'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $view['gains'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $view['total_gain_percent'], 0.001);
        $this->assertTrue($view['donut']['available']);
        $this->assertEqualsWithDelta(100.0, (float) $view['donut']['invested_percent'], 0.1);
        $this->assertEqualsWithDelta(0.0, (float) $view['donut']['gains_percent'], 0.1);
    }

    public function test_case_g_and_h_partial_and_short_duration(): void
    {
        $partial = app(PfmCalculator::class)->calculate('savings-investment-calculator', $this->payload([
            'years' => 1.5,
            'investment_frequency' => 'yearly',
            'contribution_amount' => 12000,
            'monthly_contribution' => 12000,
        ]));
        $this->assertSame(1, (int) $partial['result']['contribution_count']);
        $partialView = SavingsInvestmentPresentation::build($partial['normalized'], $partial['result']);
        $this->assertTrue($partialView['projection']['available']);
        $labels = array_column($partialView['projection']['year_rows'], 'label');
        $this->assertTrue(collect($labels)->contains(fn ($l) => str_contains((string) $l, '1.5')));

        $short = app(PfmCalculator::class)->calculate('savings-investment-calculator', $this->payload([
            'years' => 0.5,
            'investment_frequency' => 'yearly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
            'initial_amount' => 50000,
        ]));
        $this->assertSame(0, (int) $short['result']['contribution_count']);
        $this->assertEqualsWithDelta(50000.0, (float) $short['result']['invested'], 0.001);
    }

    public function test_case_i_historical_sparse_and_legacy_monthly(): void
    {
        $user = $this->member('si51-hist@example.com');
        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'savings-investment-calculator',
            'title' => 'Legacy SI',
            'inputs' => [
                'initial_amount' => 100000,
                'monthly_contribution' => 5000,
                'expected_return_percent' => 10,
                'years' => 10,
            ],
            'outputs' => [
                'corpus' => 1294929.04,
                'invested' => 700000.0,
                'gains' => 594929.04,
                'fv_initial' => 270704.15,
                'fv_contributions' => 1024224.89,
                'months' => 120,
            ],
            'summary' => 'Legacy',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $view = SavingsInvestmentPresentation::build($legacy->inputs, $legacy->outputs);
        $this->assertSame('monthly', $view['investment_frequency']);
        $this->assertTrue($view['projection']['available']);
        $this->assertEqualsWithDelta(1294929.04, (float) $view['projection']['final_value'], 0.001);

        $sparse = SavingsInvestmentPresentation::build(['years' => 5], ['summary' => 'only summary']);
        $this->assertTrue($sparse['sparse']);
        $this->assertFalse($sparse['donut']['available']);
        $this->assertFalse($sparse['projection']['available']);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('1,294,929.04', false);
    }

    public function test_save_ownership_and_other_calculators_unaffected(): void
    {
        $user = $this->member('si51-save@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($saved);
        $this->assertEqualsWithDelta(1294929.04, (float) ($saved->outputs['corpus'] ?? 0), 0.001);

        $other = $this->member('si51-other@example.com');
        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $saved))
            ->assertForbidden();

        $inf = $this->actingAs($user, 'user')
            ->post(route('tools.pfm.inflation.calculate'), [
                'current_amount' => 100000,
                'inflation_percent' => 6,
                'years' => 10,
            ])
            ->assertRedirect(route('tools.pfm.inflation.results'));
        $html = $this->actingAs($user, 'user')->get(route('tools.pfm.inflation.results'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-yk-si-results', $html);
        $this->assertStringNotContainsString('Year-wise investment projection', $html);
    }

    public function test_projection_helper_samples_without_changing_finals(): void
    {
        $calc = app(PfmCalculator::class)->calculate('savings-investment-calculator', $this->payload([
            'years' => 30,
        ]));
        $projection = SavingsInvestmentProjection::build($calc['normalized'], $calc['result'], 24);
        $this->assertLessThanOrEqual(25, count($projection['chart_points']));
        $this->assertEqualsWithDelta((float) $calc['result']['corpus'], (float) $projection['final_value'], 0.001);
        $this->assertEqualsWithDelta((float) $calc['result']['invested'], (float) $projection['final_invested'], 0.001);
    }

    /**
     * @param  array<string, float|int|string>  $overrides
     * @return array<string, float|int|string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'initial_amount' => 100000,
            'monthly_contribution' => 5000,
            'contribution_amount' => 5000,
            'expected_return_percent' => 10,
            'years' => 10,
            'investment_frequency' => 'monthly',
        ], $overrides);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'SI Maturity Member',
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
