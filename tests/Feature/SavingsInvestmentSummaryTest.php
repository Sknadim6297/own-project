<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use App\Support\Pfm\SavingsInvestmentPresentation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 47 — Savings & Investment Calculator improved result summary.
 */
class SavingsInvestmentSummaryTest extends TestCase
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

    public function test_summary_renders_with_actual_financial_values(): void
    {
        $user = $this->member('si47@example.com');
        $payload = $this->payload();
        $expected = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('data-yk-si-results', false)
            ->assertSee('data-yk-si-conclusion', false)
            ->assertSee('Your Investment Results')
            ->assertSee('Your Investment Conclusion')
            ->assertSee('Estimated maturity amount')
            ->assertSee('Assumptions used')
            ->assertSee('Next Steps')
            ->assertSee('These investment projections are estimates')
            ->getContent();

        $this->assertStringContainsString(number_format((float) $expected['corpus'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['invested'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['gains'], 2), $html);
        $this->assertStringContainsString('10%', $html);
        $this->assertStringContainsString('Monthly', $html);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_summary_changes_when_inputs_change(): void
    {
        $user = $this->member('si47-change@example.com');

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'initial_amount' => 200000,
            'monthly_contribution' => 0,
        ]));
        $lump = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-scenario="lump_sum"', $lump);
        $this->assertStringContainsString('lump sum', $lump);
        $this->assertStringContainsString('no recurring contributions', $lump);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'initial_amount' => 0,
            'monthly_contribution' => 8000,
        ]));
        $recurring = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-scenario="recurring"', $recurring);
        $this->assertStringContainsString('every month', $recurring);
        $this->assertStringNotContainsString('lump sum', $recurring);
    }

    public function test_financial_outputs_unchanged_from_engine(): void
    {
        $payload = $this->payload();
        $before = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload);

        $user = $this->member('si47-engine@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $draft = session('pfm.draft.savings-investment-calculator');
        foreach (['corpus', 'invested', 'gains', 'fv_initial', 'fv_contributions', 'months'] as $key) {
            $this->assertEqualsWithDelta((float) $before['result'][$key], (float) $draft['result'][$key], 0.01, $key);
        }
    }

    public function test_zero_return_and_combined_scenarios(): void
    {
        $calculator = app(PfmCalculator::class);

        $zero = $calculator->calculate('savings-investment-calculator', $this->payload([
            'expected_return_percent' => 0,
            'years' => 5,
            'initial_amount' => 50000,
            'monthly_contribution' => 2000,
        ]));
        $this->assertEqualsWithDelta((float) $zero['result']['invested'], (float) $zero['result']['corpus'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $zero['result']['gains'], 0.01);
        $viewZero = SavingsInvestmentPresentation::build($zero['normalized'], $zero['result']);
        $this->assertTrue($viewZero['zero_return']);
        $this->assertStringContainsString('without additional investment growth', implode(' ', $viewZero['paragraphs']));

        $combined = $calculator->calculate('savings-investment-calculator', $this->payload());
        $viewCombined = SavingsInvestmentPresentation::build($combined['normalized'], $combined['result']);
        $this->assertSame('combined', $viewCombined['base_scenario']);
        $this->assertStringContainsString('initial amount', implode(' ', $viewCombined['paragraphs']));
        $this->assertStringContainsString('counts the initial sum once', implode(' ', $viewCombined['paragraphs']));
    }

    public function test_recalculate_new_test_and_save_ownership(): void
    {
        $user = $this->member('si47-flow@example.com');
        $other = $this->member('si47-other@example.com');
        $payload = $this->payload(['years' => 8, 'monthly_contribution' => 4500]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        // Existing Savings recalculate clears draft (forget), same as Inflation.
        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));
        $this->assertNull(session('pfm.draft.savings-investment-calculator'));

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertEqualsWithDelta(100000.0, (float) $record->inputs['initial_amount'], 0.01);

        $show = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('data-yk-si-saved', false)
            ->assertSee('Your Investment Results')
            ->getContent();
        $this->assertStringContainsString(number_format((float) $record->outputs['corpus'], 2), $show);

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertStatus(403);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->assertNull(session('pfm.draft.savings-investment-calculator'));
    }

    public function test_historical_sparse_snapshot_safe_fallback(): void
    {
        $user = $this->member('si47-legacy@example.com');
        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'savings-investment-calculator',
            'title' => 'Legacy SI',
            'inputs' => [
                'initial_amount' => 25000,
            ],
            'outputs' => [
                'corpus' => 40000.0,
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

        $this->assertStringContainsString('40,000.00', $html);
        $this->assertStringContainsString('Unavailable', $html);
        $this->assertStringNotContainsString('₹NaN', $html);
        $view = SavingsInvestmentPresentation::build($legacy->inputs, $legacy->outputs);
        $this->assertSame('₹40,000.00', $view['money']['corpus']);
        $this->assertSame('Unavailable', $view['money']['invested']);
    }

    public function test_guest_preview_and_other_calculators_unaffected(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), $this->payload())
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('Save result (sign in)');
        $this->assertSame(0, PfmCalculation::query()->count());

        $user = $this->member('si47-other-tools@example.com');
        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.inflation.calculate'), [
                'current_amount' => 100000,
                'inflation_percent' => 6,
                'years' => 10,
            ])
            ->assertRedirect(route('tools.pfm.inflation.results'));

        $inf = $this->actingAs($user, 'user')->get(route('tools.pfm.inflation.results'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Your Investment Results', $inf);
        $this->assertStringNotContainsString('data-yk-si-conclusion', $inf);
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
            'name' => 'SI Summary Member',
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
