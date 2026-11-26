<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmMath;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 49 — Savings & Investment Calculator investment time period UI.
 */
class SavingsInvestmentTimePeriodTest extends TestCase
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

    public function test_form_renders_investment_time_period_section(): void
    {
        $html = $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Investment Time Period')
            ->assertSee('Choose how long you want to stay invested.')
            ->assertSee('data-yk-si-duration', false)
            ->assertSee('data-yk-si-years', false)
            ->assertSee('data-yk-si-years-slider', false)
            ->assertSee('data-yk-si-years-dec', false)
            ->assertSee('data-yk-si-years-inc', false)
            ->assertSee('data-yk-si-years-preset="1"', false)
            ->assertSee('data-yk-si-years-preset="30"', false)
            ->assertSee('data-yk-si-months-label', false)
            ->assertSee('data-yk-si-timeline', false)
            ->assertSee('name="years"', false)
            ->assertSee('min="0.01"', false)
            ->assertSee('max="100"', false)
            ->assertSee('step="0.01"', false)
            ->assertSee('Decrease investment duration by 1 year')
            ->assertSee('Increase investment duration by 1 year')
            ->assertSee('Contribution preview')
            ->assertDontSee('Your Investment Results')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'name="years"'));
        $this->assertStringContainsString('type="range"', $html);
        $this->assertStringContainsString('data-yk-icon="minus"', $html);
        $this->assertStringContainsString('data-yk-icon="plus"', $html);
        $this->assertStringContainsString('data-yk-si-amount', $html);
        $this->assertMatchesRegularExpression('/id="pfm-si-years"[^>]*value=""/', $html);
        $this->assertStringContainsString('1 year', $html);
        $this->assertStringContainsString('3 years', $html);
        $this->assertStringContainsString('5 years', $html);
        $this->assertStringContainsString('10 years', $html);
        $this->assertStringContainsString('15 years', $html);
        $this->assertStringContainsString('20 years', $html);
        $this->assertStringContainsString('30 years', $html);
        $this->assertStringContainsString('Investment begins', $html);
        $this->assertStringContainsString('Midpoint', $html);
        $this->assertStringContainsString('Completion', $html);
    }

    public function test_duration_boundaries_and_fractional_compatibility(): void
    {
        $calc = app(PfmCalculator::class);

        foreach ([0.01, 0.5, 1.5, 2.5, 5, 10, 100] as $years) {
            $payload = $this->payload([
                'initial_amount' => 100000,
                'monthly_contribution' => 0,
                'years' => $years,
            ]);
            $result = $calc->calculate('savings-investment-calculator', $payload);
            $this->assertSame(round((float) $years, 2), (float) $result['normalized']['years']);
            $this->assertSame((int) round($years * 12), (int) $result['result']['months']);

            $this->post(route(self::ROUTE.'.calculate'), $payload)
                ->assertRedirect(route(self::ROUTE.'.results'));
        }
    }

    public function test_case_a_five_year_monthly_contributions(): void
    {
        $payload = $this->payload([
            'initial_amount' => 0,
            'monthly_contribution' => 5000,
            'years' => 5,
            'expected_return_percent' => 10,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertSame(60, (int) $result['months']);
        $this->assertEqualsWithDelta(300000.0, (float) $result['invested'], 0.001);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('300,000.00', false)
            ->assertSee('Your Investment Results');
    }

    public function test_case_b_ten_year_combined_baseline(): void
    {
        $payload = $this->payload([
            'initial_amount' => 100000,
            'monthly_contribution' => 5000,
            'years' => 10,
            'expected_return_percent' => 10,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertEqualsWithDelta(700000.0, (float) $result['invested'], 0.001);
        $this->assertEqualsWithDelta(1294929.04, (float) $result['corpus'], 0.001);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('1,294,929.04', false)
            ->assertSee('700,000.00', false);
    }

    public function test_case_c_twenty_year_monthly_contributions(): void
    {
        $payload = $this->payload([
            'initial_amount' => 0,
            'monthly_contribution' => 5000,
            'years' => 20,
            'expected_return_percent' => 10,
        ]);
        $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->assertSame(240, (int) $result['months']);
        $this->assertEqualsWithDelta(1200000.0, (float) $result['invested'], 0.001);
    }

    public function test_case_d_lump_sum_duration_change_does_not_add_contributions(): void
    {
        foreach ([5, 10, 20] as $years) {
            $payload = $this->payload([
                'initial_amount' => 100000,
                'monthly_contribution' => 0,
                'years' => $years,
            ]);
            $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];
            $this->assertEqualsWithDelta(100000.0, (float) $result['invested'], 0.001);
            $this->assertSame((int) round($years * 12), (int) $result['months']);
        }
    }

    public function test_case_e_fractional_effective_months_match_engine(): void
    {
        foreach ([0.01, 0.5, 1.5, 2.5] as $years) {
            $plan = PfmMath::futureValuePlan(0.0, 5000.0, 0.10, $years);
            $this->assertSame((int) round($years * 12), (int) $plan['months']);

            $payload = $this->payload([
                'initial_amount' => 0,
                'monthly_contribution' => 5000,
                'years' => $years,
            ]);
            $result = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];
            $this->assertSame((int) $plan['months'], (int) $result['months']);
            $this->assertEqualsWithDelta(
                5000.0 * (int) $plan['months'],
                (float) $result['invested'],
                0.001
            );
        }
    }

    public function test_case_f_invalid_duration_values_are_rejected(): void
    {
        foreach ([0, -1, 100.01, 101, '', 'abc'] as $years) {
            $this->from(route(self::ROUTE))
                ->post(route(self::ROUTE.'.calculate'), $this->payload(['years' => $years]))
                ->assertRedirect(route(self::ROUTE))
                ->assertSessionHasErrors('calculate');
        }

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload(['years' => 0]))
            ->assertRedirect(route(self::ROUTE));

        $html = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-duration', $html);
        $this->assertMatchesRegularExpression('/id="pfm-si-years"[^>]*value="0"/', $html);
    }

    public function test_draft_restoration_preserves_duration_controls(): void
    {
        $user = $this->member('si49-draft@example.com');

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $this->payload(['years' => 15]))
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        // Existing SI recalculate forgets draft (Task 47 documented limitation).
        $blank = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-duration', $blank);
        $this->assertMatchesRegularExpression('/id="pfm-si-years"[^>]*value=""/', $blank);

        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'years' => 7.5,
                'expected_return_percent' => -1,
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $restored = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('data-yk-si-duration', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/id="pfm-si-years"[^>]*value="7\.5"/', $restored);
        $this->assertStringContainsString('90 months', $restored);
        $this->assertStringContainsString('3.75 yr', $restored);
    }

    public function test_calculate_preview_explicit_save_and_historical_snapshot(): void
    {
        $user = $this->member('si49-save@example.com');
        $payload = $this->payload(['years' => 10]);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('1,294,929.04', false);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.save'))
            ->assertRedirect();

        $saved = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($saved);
        $this->assertSame('savings-investment-calculator', $saved->tool_key);
        $this->assertEquals(10, (float) ($saved->inputs['years'] ?? 0));
        $this->assertEqualsWithDelta(1294929.04, (float) ($saved->outputs['corpus'] ?? 0), 0.001);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee((string) $saved->id);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $saved))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('1,294,929.04', false);
    }

    public function test_task_47_and_48_compatibility_and_other_calculators_unaffected(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), $this->payload())
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('Your Investment Conclusion')
            ->assertSee('Assumptions used');

        $form = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-amount', $form);
        $this->assertStringContainsString('Investment type', $form);
        $this->assertStringContainsString('Contribution preview', $form);
        $this->assertStringContainsString('data-yk-si-duration', $form);

        $user = $this->member('si49-other@example.com');
        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.inflation.calculate'), [
                'current_amount' => 100000,
                'inflation_percent' => 6,
                'years' => 10,
            ])
            ->assertRedirect(route('tools.pfm.inflation.results'));

        $inf = $this->actingAs($user, 'user')->get(route('tools.pfm.inflation.results'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-yk-si-duration', $inf);
        $this->assertStringNotContainsString('Investment Time Period', $inf);

        $fg = $this->actingAs($user, 'user')->get(route('tools.pfm.financial-goal'))->assertOk()->getContent();
        $this->assertStringContainsString('data-goal-years-slider', $fg);
        $this->assertStringNotContainsString('data-yk-si-years-slider', $fg);
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
            'expected_return_percent' => 10,
            'years' => 10,
        ], $overrides);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'SI Time Period Member',
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
