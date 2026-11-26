<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmMath;
use App\Support\Pfm\PfmCatalog;
use App\Support\Pfm\SavingsInvestmentPresentation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 50 — Savings & Investment Calculator investment frequency.
 */
class SavingsInvestmentFrequencyTest extends TestCase
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

    public function test_form_renders_frequency_selector_and_amount_integration(): void
    {
        $html = $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Investment frequency')
            ->assertSee('data-yk-si-frequency-group', false)
            ->assertSee('name="investment_frequency"', false)
            ->assertSee('name="contribution_amount"', false)
            ->assertSee('name="monthly_contribution"', false)
            ->assertSee('Recurring Investment')
            ->assertSee('Quarterly')
            ->assertSee('Half-Yearly')
            ->assertSee('Yearly')
            ->assertSee('Every 3 months', false)
            ->assertSee('Investment Time Period')
            ->assertSee('Contribution preview')
            ->getContent();

        $this->assertStringContainsString('value="monthly"', $html);
        $this->assertStringContainsString('value="quarterly"', $html);
        $this->assertStringContainsString('value="half_yearly"', $html);
        $this->assertStringContainsString('value="yearly"', $html);
    }

    public function test_case_a_monthly_legacy_baseline_preserved(): void
    {
        $legacy = $this->payload([
            'initial_amount' => 100000,
            'monthly_contribution' => 5000,
            'years' => 10,
            'expected_return_percent' => 10,
        ]);
        unset($legacy['investment_frequency'], $legacy['contribution_amount']);

        $explicit = $this->payload([
            'initial_amount' => 100000,
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
            'investment_frequency' => 'monthly',
            'years' => 10,
            'expected_return_percent' => 10,
        ]);

        $calc = app(PfmCalculator::class);
        $a = $calc->calculate('savings-investment-calculator', $legacy)['result'];
        $b = $calc->calculate('savings-investment-calculator', $explicit)['result'];

        $this->assertSame(120, (int) $a['months']);
        $this->assertSame(120, (int) $a['contribution_count']);
        $this->assertEqualsWithDelta(700000.0, (float) $a['invested'], 0.001);
        $this->assertEqualsWithDelta(1294929.04, (float) $a['corpus'], 0.001);
        $this->assertSame($a['corpus'], $b['corpus']);
        $this->assertSame($a['invested'], $b['invested']);
        $this->assertSame($a['gains'], $b['gains']);

        $this->post(route(self::ROUTE.'.calculate'), $legacy)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('1,294,929.04', false)
            ->assertSee('700,000.00', false)
            ->assertSee('Your Investment Results')
            ->assertSee('every month', false);
    }

    public function test_case_b_quarterly_counts_and_maturity(): void
    {
        $payload = $this->payload([
            'initial_amount' => 100000,
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
            'investment_frequency' => 'quarterly',
            'years' => 10,
            'expected_return_percent' => 10,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];
        $ref = PfmMath::futureValuePlanPeriodic(100000.0, 5000.0, 0.10, 10.0, 3);

        $this->assertSame(40, (int) $result['contribution_count']);
        $this->assertEqualsWithDelta(200000.0, 5000.0 * 40, 0.001);
        $this->assertEqualsWithDelta(300000.0, (float) $result['invested'], 0.001);
        $this->assertEqualsWithDelta(609283.12, (float) $result['corpus'], 0.001);
        $this->assertSame($ref['corpus'], $result['corpus']);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $html = $this->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('every three months', $html);
        $this->assertStringContainsString('Quarterly', $html);
        $this->assertStringNotContainsString('every month for 10 years', $html);
    }

    public function test_case_c_half_yearly(): void
    {
        $payload = $this->payload([
            'investment_frequency' => 'half_yearly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertSame(20, (int) $result['contribution_count']);
        $this->assertEqualsWithDelta(100000.0, 5000.0 * 20, 0.001);
        $this->assertEqualsWithDelta(200000.0, (float) $result['invested'], 0.001);
        $this->assertEqualsWithDelta(437886.39, (float) $result['corpus'], 0.001);
    }

    public function test_case_d_yearly(): void
    {
        $payload = $this->payload([
            'investment_frequency' => 'yearly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertSame(10, (int) $result['contribution_count']);
        $this->assertEqualsWithDelta(50000.0, 5000.0 * 10, 0.001);
        $this->assertEqualsWithDelta(150000.0, (float) $result['invested'], 0.001);
        $this->assertEqualsWithDelta(352214.58, (float) $result['corpus'], 0.001);
    }

    public function test_case_e_zero_return_all_frequencies(): void
    {
        foreach (['monthly', 'quarterly', 'half_yearly', 'yearly'] as $frequency) {
            $payload = $this->payload([
                'investment_frequency' => $frequency,
                'contribution_amount' => 5000,
                'monthly_contribution' => 5000,
                'expected_return_percent' => 0,
            ]);
            $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];
            $this->assertEqualsWithDelta((float) $result['invested'], (float) $result['corpus'], 0.001, $frequency);
            $this->assertEqualsWithDelta(0.0, (float) $result['gains'], 0.001, $frequency);
        }
    }

    public function test_case_f_lump_sum_ignores_frequency(): void
    {
        $base = [
            'initial_amount' => 100000,
            'contribution_amount' => 0,
            'monthly_contribution' => 0,
            'years' => 10,
            'expected_return_percent' => 10,
        ];
        $calc = app(PfmCalculator::class);
        $monthly = $calc->calculate('savings-investment-calculator', $base + ['investment_frequency' => 'monthly'])['result'];
        $yearly = $calc->calculate('savings-investment-calculator', $base + ['investment_frequency' => 'yearly'])['result'];

        $this->assertSame($monthly['corpus'], $yearly['corpus']);
        $this->assertSame($monthly['invested'], $yearly['invested']);
        $this->assertEqualsWithDelta(100000.0, (float) $monthly['invested'], 0.001);
        $this->assertSame(0, (int) $yearly['contribution_count']);
    }

    public function test_case_g_partial_yearly_duration(): void
    {
        $payload = $this->payload([
            'initial_amount' => 100000,
            'contribution_amount' => 12000,
            'monthly_contribution' => 12000,
            'investment_frequency' => 'yearly',
            'years' => 1.5,
            'expected_return_percent' => 10,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertSame(18, (int) $result['months']);
        $this->assertSame(1, (int) $result['contribution_count']);
        $this->assertEqualsWithDelta(112000.0, (float) $result['invested'], 0.001);
    }

    public function test_case_h_short_duration_no_invented_contribution(): void
    {
        $payload = $this->payload([
            'initial_amount' => 50000,
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
            'investment_frequency' => 'yearly',
            'years' => 0.5,
            'expected_return_percent' => 10,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertSame(6, (int) $result['months']);
        $this->assertSame(0, (int) $result['contribution_count']);
        $this->assertEqualsWithDelta(50000.0, (float) $result['invested'], 0.001);
    }

    public function test_case_i_invalid_inputs_rejected(): void
    {
        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload(['investment_frequency' => 'weekly']))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'contribution_amount' => 5000,
                'monthly_contribution' => 4000,
                'investment_frequency' => 'monthly',
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'contribution_amount' => -100,
                'monthly_contribution' => -100,
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload(['years' => 0]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload(['expected_return_percent' => 51]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');
    }

    public function test_draft_restoration_preserves_frequency(): void
    {
        $user = $this->member('si50-draft@example.com');

        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'investment_frequency' => 'quarterly',
                'contribution_amount' => 5000,
                'monthly_contribution' => 5000,
                'years' => -1,
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $html = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-frequency="quarterly"', $html);
        $this->assertStringContainsString('value="quarterly"', $html);
        $this->assertStringContainsString('Quarterly investment', $html);
    }

    public function test_historical_monthly_snapshot_without_frequency_defaults_monthly(): void
    {
        $user = $this->member('si50-hist@example.com');
        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'savings-investment-calculator',
            'title' => 'Legacy monthly SI',
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
                'summary' => 'Corpus ₹1,294,929.04 · Invested ₹700,000.00 · Gains ₹594,929.04',
            ],
            'summary' => 'Legacy monthly SI',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) \Illuminate\Support\Str::uuid(),
            'is_draft' => false,
        ]);

        $view = SavingsInvestmentPresentation::build($legacy->inputs, $legacy->outputs);
        $this->assertSame('monthly', $view['investment_frequency']);
        $this->assertSame('Monthly', $view['frequency_label']);
        $this->assertStringContainsString('every month', $view['paragraphs'][0]);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->assertSee('1,294,929.04', false)
            ->assertSee('Your Investment Results');
    }

    public function test_save_stores_frequency_metadata_and_other_calculators_unaffected(): void
    {
        $user = $this->member('si50-save@example.com');
        $payload = $this->payload([
            'investment_frequency' => 'half_yearly',
            'contribution_amount' => 5000,
            'monthly_contribution' => 5000,
        ]);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.save'))
            ->assertRedirect();

        $saved = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($saved);
        $this->assertSame('half_yearly', $saved->inputs['investment_frequency'] ?? null);
        $this->assertEquals(5000, (float) ($saved->inputs['contribution_amount'] ?? 0));
        $this->assertEqualsWithDelta(200000.0, (float) ($saved->outputs['invested'] ?? 0), 0.001);

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.inflation.calculate'), [
                'current_amount' => 100000,
                'inflation_percent' => 6,
                'years' => 10,
            ])
            ->assertRedirect(route('tools.pfm.inflation.results'));

        $inf = $this->actingAs($user, 'user')->get(route('tools.pfm.inflation.results'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-yk-si-frequency', $inf);
        $this->assertStringNotContainsString('Half-Yearly', $inf);

        $fg = $this->actingAs($user, 'user')->get(route('tools.pfm.financial-goal'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-yk-si-frequency-option', $fg);
    }

    public function test_unit_periodic_helper_matches_monthly_baseline(): void
    {
        $closed = PfmMath::futureValuePlan(100000.0, 5000.0, 0.10, 10.0);
        $periodic = PfmMath::futureValuePlanPeriodic(100000.0, 5000.0, 0.10, 10.0, 1);
        $this->assertSame($closed, $periodic);
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
            'name' => 'SI Frequency Member',
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
