<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmMath;
use App\Support\Pfm\InflationChart;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 46 — Inflation Calculator graph values and year-wise projection.
 */
class InflationCalculatorGraphValuesTest extends TestCase
{
    private const ROUTE = 'tools.pfm.inflation';

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

    public function test_route_and_graph_render_with_financial_values(): void
    {
        $user = $this->member('inf46@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('data-yk-inf-chart', false)
            ->assertSee('Inflation impact over time')
            ->assertSee('Year-wise cost projection')
            ->assertSee('Estimated future cost')
            ->assertSee('₹100,000.00')
            ->assertSee('₹179,084.77')
            ->getContent();

        $this->assertStringContainsString('Today', $html);
        $this->assertStringContainsString('Year 10', $html);
        $this->assertStringContainsString('133,822.56', $html);
        $this->assertStringContainsString('yk-infchart__value', $html);
        $this->assertStringContainsString('yk-infchart__tick', $html);
        $this->assertStringContainsString('At an annual inflation rate of 6%', $html);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_chart_series_matches_approved_compound_model(): void
    {
        $calculator = app(PfmCalculator::class);
        $out = $calculator->calculate('inflation-calculator', $this->payload());
        $chart = InflationChart::build($out['normalized'], $out['result']);

        $this->assertTrue($chart['available']);
        $this->assertEqualsWithDelta(100000.0, (float) $chart['points'][0]['value'], 0.01);
        $this->assertSame('Today', $chart['points'][0]['label']);

        $byYear = [];
        foreach ($chart['points'] as $point) {
            $byYear[(string) $point['year']] = (float) $point['value'];
        }

        $this->assertEqualsWithDelta(106000.0, $byYear['1'], 0.01);
        $this->assertEqualsWithDelta(112360.0, $byYear['2'], 0.01);
        $this->assertEqualsWithDelta(119101.6, $byYear['3'], 0.01);
        $this->assertEqualsWithDelta(133822.56, $byYear['5'], 0.01);
        $this->assertEqualsWithDelta(179084.77, $byYear['10'], 0.01);
        $this->assertEqualsWithDelta((float) $out['result']['future_cost'], $byYear['10'], 0.01);
        $this->assertEqualsWithDelta((float) $out['result']['increase'], (float) $chart['increase'], 0.01);

        $this->assertNotEmpty($chart['y_ticks']);
        $this->assertStringContainsString('₹', $chart['y_ticks'][0]['label']);
        $this->assertCount(count($chart['points']), $chart['table']);
        $this->assertSame($chart['points'][0]['value_full'], $chart['table'][0]['amount']);
    }

    public function test_existing_financial_outputs_unchanged(): void
    {
        $payload = $this->payload();
        $before = app(PfmCalculator::class)->calculate('inflation-calculator', $payload);

        $user = $this->member('inf46-engine@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $draft = session('pfm.draft.inflation-calculator');
        foreach (['future_cost', 'purchasing_power', 'increase'] as $key) {
            $this->assertEqualsWithDelta((float) $before['result'][$key], (float) $draft['result'][$key], 0.01, $key);
        }
        $this->assertArrayNotHasKey('projection_series', $draft['result']);
    }

    public function test_zero_inflation_and_decimal_rate(): void
    {
        $calculator = app(PfmCalculator::class);

        $zero = $calculator->calculate('inflation-calculator', $this->payload([
            'inflation_percent' => 0,
            'years' => 8,
        ]));
        $zeroChart = InflationChart::build($zero['normalized'], $zero['result']);
        $this->assertTrue($zeroChart['available']);
        foreach ($zeroChart['points'] as $point) {
            $this->assertEqualsWithDelta(100000.0, (float) $point['value'], 0.01);
        }
        $this->assertEqualsWithDelta(100000.0, (float) $zero['result']['future_cost'], 0.01);

        $decimal = $calculator->calculate('inflation-calculator', $this->payload([
            'inflation_percent' => 5.5,
            'years' => 3,
        ]));
        $expected = PfmMath::roundMoney(PfmMath::compound(100000, 0.055, 3));
        $this->assertEqualsWithDelta($expected, (float) $decimal['result']['future_cost'], 0.01);
        $decChart = InflationChart::build($decimal['normalized'], $decimal['result']);
        $this->assertEqualsWithDelta($expected, (float) end($decChart['points'])['value'], 0.01);
    }

    public function test_long_period_and_large_amounts_remain_valid(): void
    {
        $out = app(PfmCalculator::class)->calculate('inflation-calculator', $this->payload([
            'current_amount' => 5000000,
            'inflation_percent' => 7.25,
            'years' => 40,
        ]));
        $chart = InflationChart::build($out['normalized'], $out['result']);

        $this->assertTrue($chart['available']);
        $this->assertGreaterThan(2, count($chart['points']));
        $this->assertLessThanOrEqual(12, count($chart['points']));
        $this->assertEqualsWithDelta((float) $out['result']['future_cost'], (float) end($chart['points'])['value'], 0.01);
        foreach ($chart['points'] as $point) {
            $this->assertTrue(is_finite((float) $point['value']));
            $this->assertGreaterThanOrEqual(0, (float) $point['value']);
        }
        foreach ($chart['plot_points'] as $pt) {
            $this->assertTrue(is_finite((float) $pt['cx']));
            $this->assertTrue(is_finite((float) $pt['cy']));
        }
    }

    public function test_table_matches_chart_and_tooltips_present(): void
    {
        $user = $this->member('inf46-table@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'years' => 5,
        ]));

        $html = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('<title>Today — Estimated future cost: ₹100,000.00</title>', $html);
        $this->assertStringContainsString('Year 5 — Estimated future cost:', $html);
        $this->assertStringContainsString('<th scope="row">Today</th>', $html);
        $this->assertStringContainsString('<th scope="row">Year 5</th>', $html);
        $this->assertMatchesRegularExpression('/data-yk-inf-point="0"[^>]*tabindex="0"|tabindex="0"[^>]*data-yk-inf-point="0"/', $html);
    }

    public function test_historical_missing_inputs_show_unavailable_without_rewrite(): void
    {
        $user = $this->member('inf46-legacy@example.com');
        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'inflation-calculator',
            'title' => 'Legacy inflation',
            'inputs' => [
                'current_amount' => 50000,
            ],
            'outputs' => [
                'future_cost' => 89542.38,
                'summary' => 'Sparse snapshot',
            ],
            'summary' => 'Sparse snapshot',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Chart values are unavailable', $html);
        $this->assertStringContainsString('89,542.38', $html);
        $this->assertSame(50000.0, (float) $legacy->fresh()->inputs['current_amount']);
        $this->assertArrayNotHasKey('inflation_percent', $legacy->fresh()->inputs);
    }

    public function test_historical_complete_snapshot_reconstructs_graph(): void
    {
        $user = $this->member('inf46-hist@example.com');
        $engine = app(PfmCalculator::class)->calculate('inflation-calculator', $this->payload());
        $record = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'inflation-calculator',
            'title' => 'Saved inflation',
            'inputs' => $engine['normalized'],
            'outputs' => $engine['result'],
            'summary' => $engine['result']['summary'],
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('data-yk-inf-chart', false)
            ->assertSee('Year-wise cost projection')
            ->getContent();

        $this->assertStringContainsString('179,084.77', $html);
        $this->assertStringContainsString('133,822.56', $html);
    }

    public function test_save_recalculate_new_test_and_ownership(): void
    {
        $user = $this->member('inf46-flow@example.com');
        $other = $this->member('inf46-other@example.com');
        $payload = $this->payload(['years' => 7]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertEqualsWithDelta(100000.0, (float) $record->inputs['current_amount'], 0.01);
        $this->assertEqualsWithDelta((float) $record->outputs['future_cost'], (float) end(InflationChart::build($record->inputs, $record->outputs)['points'])['value'], 0.01);

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertStatus(403);

        // Existing Inflation recalculate clears the draft (unlike EF/FG/Debt/RT clearResult restore).
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));
        $this->assertNull(session('pfm.draft.inflation-calculator'));

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $fresh = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="pfm-amount"[^>]*value=""/', $fresh);
        $this->assertNull(session('pfm.draft.inflation-calculator'));
    }

    public function test_guest_preview_and_other_calculators_unaffected(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), $this->payload())
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Inflation impact over time')
            ->assertSee('Save result (sign in)');
        $this->assertSame(0, PfmCalculation::query()->count());

        $user = $this->member('inf46-other-tools@example.com');
        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.retirement.calculate'), [
                'current_age' => 30,
                'retirement_age' => 60,
                'life_expectancy' => 85,
                'current_monthly_expenses' => 50000,
                'current_assets' => 500000,
                'inflation_percent' => 6,
                'pre_retirement_return_percent' => 10,
                'post_retirement_return_percent' => 7,
            ])
            ->assertRedirect(route('tools.pfm.retirement.results'));

        $rt = $this->actingAs($user, 'user')->get(route('tools.pfm.retirement.results'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Year-wise cost projection', $rt);
        $this->assertStringNotContainsString('data-yk-inf-chart', $rt);
    }

    /**
     * @param  array<string, float|int|string>  $overrides
     * @return array<string, float|int|string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'current_amount' => 100000,
            'inflation_percent' => 6,
            'years' => 10,
        ], $overrides);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Inflation Graph Member',
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
