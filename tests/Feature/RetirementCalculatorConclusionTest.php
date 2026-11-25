<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use App\Support\Pfm\RetirementPresentation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 44 — Retirement Calculator personalized result conclusion.
 */
class RetirementCalculatorConclusionTest extends TestCase
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

    public function test_result_route_renders_meaningful_conclusion(): void
    {
        $user = $this->member('rt44@example.com');
        $payload = $this->payload();
        $expected = app(PfmCalculator::class)->calculate('retirement-calculator', $payload)['result'];

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('data-yk-rt-results', false)
            ->assertSee('data-yk-rt-conclusion', false)
            ->assertSee('Your Retirement Summary')
            ->assertSee('Next Steps')
            ->assertSee('Assumptions used')
            ->assertSee('Understanding the numbers')
            ->assertSee('Save result')
            ->getContent();

        $this->assertStringContainsString(number_format((float) $expected['required_corpus'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['monthly_investment'], 2), $html);
        $this->assertStringContainsString((string) (int) $expected['years_to_retire'], $html);
        $this->assertStringContainsString('estimated retirement corpus requirement', $html);
        $this->assertStringContainsString('These retirement projections are estimates', $html);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_conclusion_changes_when_inputs_change(): void
    {
        $user = $this->member('rt44-change@example.com');

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'current_age' => 30,
            'retirement_age' => 60,
            'current_assets' => 0,
        ]));
        $young = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('without an existing retirement savings balance', $young);
        $this->assertStringContainsString('30 years remaining', $young);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $this->payload([
            'current_age' => 55,
            'retirement_age' => 60,
            'current_assets' => 2500000,
        ]));
        $near = $this->actingAs($user, 'user')->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString('limited accumulation period', $near);
        $this->assertStringContainsString('existing retirement savings', $near);
        $this->assertStringNotContainsString('without an existing retirement savings balance', $near);
    }

    public function test_financial_outputs_unchanged_from_engine(): void
    {
        $payload = $this->payload();
        $before = app(PfmCalculator::class)->calculate('retirement-calculator', $payload);

        $user = $this->member('rt44-engine@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $draft = session('pfm.draft.retirement-calculator');
        $this->assertEqualsWithDelta((float) $before['result']['required_corpus'], (float) $draft['result']['required_corpus'], 0.01);
        $this->assertEqualsWithDelta((float) $before['result']['monthly_investment'], (float) $draft['result']['monthly_investment'], 0.01);
        $this->assertEqualsWithDelta((float) $before['result']['funding_gap'], (float) $draft['result']['funding_gap'], 0.01);
        $this->assertEqualsWithDelta((float) $before['result']['future_monthly_expense'], (float) $draft['result']['future_monthly_expense'], 0.01);
        $this->assertSame((int) $before['result']['readiness_score'], (int) $draft['result']['readiness_score']);
        $this->assertSame(6.0, (float) $draft['normalized']['inflation_percent']);
        $this->assertSame(10.0, (float) $draft['normalized']['pre_retirement_return_percent']);
        $this->assertSame(7.0, (float) $draft['normalized']['post_retirement_return_percent']);
    }

    public function test_zero_savings_and_zero_monthly_scenarios(): void
    {
        $calculator = app(PfmCalculator::class);

        $noSavings = $calculator->calculate('retirement-calculator', $this->payload([
            'current_assets' => 0,
        ]));
        $viewNo = RetirementPresentation::build($noSavings['normalized'], $noSavings['result']);
        $this->assertTrue($viewNo['no_savings']);
        $this->assertStringContainsString('without an existing retirement savings balance', implode(' ', $viewNo['paragraphs']));

        // Large assets to drive monthly investment to ~0 where engine supports it.
        $funded = $calculator->calculate('retirement-calculator', $this->payload([
            'current_age' => 40,
            'retirement_age' => 60,
            'current_monthly_expenses' => 20000,
            'current_assets' => 50000000,
            'inflation_percent' => 4,
            'pre_retirement_return_percent' => 10,
            'post_retirement_return_percent' => 6,
        ]));
        $this->assertEqualsWithDelta(0.0, (float) $funded['result']['monthly_investment'], 0.01);

        $user = $this->member('rt44-zero@example.com');
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), [
            'current_age' => 40,
            'retirement_age' => 60,
            'life_expectancy' => 85,
            'current_monthly_expenses' => 20000,
            'current_assets' => 50000000,
            'inflation_percent' => 4,
            'pre_retirement_return_percent' => 10,
            'post_retirement_return_percent' => 6,
        ]);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('does not guarantee retirement outcomes', $html);
        $this->assertStringContainsString('data-yk-rt-scenario="zero_monthly"', $html);
    }

    public function test_recalculate_restores_inputs_and_new_test_resets(): void
    {
        $user = $this->member('rt44-recalc@example.com');
        $payload = $this->payload([
            'current_age' => 35,
            'retirement_age' => 62,
            'life_expectancy' => 88,
            'current_monthly_expenses' => 45000,
            'current_assets' => 750000,
            'inflation_percent' => 5.5,
            'pre_retirement_return_percent' => 9.25,
            'post_retirement_return_percent' => 6.5,
        ]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHas('status');

        $form = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('value="35"', $form);
        $this->assertStringContainsString('value="62"', $form);
        $this->assertStringContainsString('value="45000"', $form);
        $this->assertStringContainsString('value="750000"', $form);
        $this->assertMatchesRegularExpression('/name="inflation_percent"[^>]*value="5\.5"|id="pfm-inf"[^>]*value="5\.5"/', $form);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $fresh = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="pfm-current-age"[^>]*value=""/', $fresh);
    }

    public function test_save_history_ownership_and_historical_fallback(): void
    {
        $user = $this->member('rt44-save@example.com');
        $other = $this->member('rt44-other@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());

        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $show = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('data-yk-rt-saved', false)
            ->assertSee('Your Retirement Summary')
            ->assertSee('read-only')
            ->getContent();
        $this->assertStringContainsString(number_format((float) $record->outputs['required_corpus'], 2), $show);

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertStatus(403);

        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'retirement-calculator',
            'title' => 'Legacy RT',
            'inputs' => [
                'current_age' => 30,
                'retirement_age' => 60,
            ],
            'outputs' => [
                'required_corpus' => 1234567.0,
                'summary' => 'Sparse snapshot',
            ],
            'summary' => 'Sparse snapshot',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $legacyHtml = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('1,234,567.00', $legacyHtml);
        $this->assertStringContainsString('Unavailable', $legacyHtml);

        $view = RetirementPresentation::build($legacy->inputs, $legacy->outputs);
        $this->assertSame('Unavailable', $view['money']['monthly']);
        $this->assertSame('₹1,234,567.00', $view['money']['corpus']);
    }

    public function test_guest_preview_does_not_auto_save(): void
    {
        $payload = $this->payload(['current_age' => 28]);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Retirement Summary')
            ->assertSee('Save result (sign in)');

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route('login'));

        $this->assertSame(0, PfmCalculation::query()->count());
    }

    public function test_other_calculators_unaffected(): void
    {
        $user = $this->member('rt44-other-tools@example.com');

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.debt-emi.calculate'), [
                'principal' => 500000,
                'down_payment' => 0,
                'annual_rate_percent' => 10,
                'tenure_years' => 5,
                'extra_payment' => 0,
            ])
            ->assertRedirect(route('tools.pfm.debt-emi.results'));

        $debt = $this->actingAs($user, 'user')
            ->get(route('tools.pfm.debt-emi.results'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-yk-debt-results', $debt);
        $this->assertStringNotContainsString('Your Retirement Summary', $debt);

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
            ->assertSee('data-yk-goal-results', false)
            ->assertDontSee('Your Retirement Summary');
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
            'name' => 'RT Conclusion Member',
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
