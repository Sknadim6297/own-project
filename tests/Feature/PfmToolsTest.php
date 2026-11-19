<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PfmToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureCustomerTermsAccepted::class,
        ]);
    }

    public function test_guest_can_open_and_calculate_money_planner(): void
    {
        $this->storage();

        $this->get(route('tools.pfm.money-planner'))
            ->assertOk()
            ->assertSee('Monthly Money Planner');

        $this->post(route('tools.pfm.money-planner.calculate'), [
            'monthly_income' => 80000,
            'essential_expenses' => 50000,
            'lifestyle_expenses' => 0,
            'emis' => 0,
            'other_recurring' => 0,
            'investments' => 15000,
        ])->assertRedirect(route('tools.pfm.money-planner.results'));

        $this->get(route('tools.pfm.money-planner.results'))
            ->assertOk()
            ->assertSee('15,000.00')
            ->assertSee('37.5');
    }

    public function test_guest_save_requires_login(): void
    {
        $this->storage();

        $this->post(route('tools.pfm.inflation.calculate'), [
            'current_amount' => 10000,
            'inflation_percent' => 0,
            'years' => 10,
        ])->assertRedirect(route('tools.pfm.inflation.results'));

        $this->post(route('tools.pfm.inflation.save'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_save_and_delete_private_history(): void
    {
        $this->storage();
        $user = $this->member('pfm-user@example.com');

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.emergency-fund.calculate'), [
                'monthly_expenses' => 45000,
                'coverage_months' => 6,
                'current_savings' => 150000,
                'monthly_saving_capacity' => 10000,
            ])->assertRedirect(route('tools.pfm.emergency-fund.results'));

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.emergency-fund.save'))
            ->assertRedirect();

        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('emergency-fund-calculator', $record->tool_key);
        $this->assertSame(270000.0, (float) $record->outputs['target_amount']);

        $other = $this->member('pfm-other@example.com');
        $this->actingAs($other, 'user')
            ->get(route('tools.pfm.emergency-fund.history.show', $record))
            ->assertForbidden();

        $this->actingAs($user, 'user')
            ->delete(route('tools.pfm.emergency-fund.history.destroy', $record))
            ->assertRedirect(route('tools.pfm.emergency-fund.history'));

        $this->assertDatabaseMissing('pfm_calculations', ['id' => $record->id]);
    }

    public function test_pfm_hub_lists_tools(): void
    {
        $this->storage();

        $this->get(route('tools.pfm'))
            ->assertOk()
            ->assertSee('Monthly Money Planner')
            ->assertSee('Retirement Calculator');
    }

    public function test_pfm_hub_groups_every_catalog_tool_with_a_link(): void
    {
        $this->storage();

        $response = $this->get(route('tools.pfm'))
            ->assertOk()
            ->assertSee('Everyday Money')
            ->assertSee('Goals &amp; Growth', false)
            ->assertSee('Debt &amp; Prices', false)
            ->assertSee('<b>8</b> calculators', false);

        foreach (PfmCatalog::TOOLS as $tool) {
            $response->assertSee($tool['name']);
            $response->assertSee('href="'.route($tool['route']).'"', false);
        }
    }

    public function test_pfm_hub_shows_closed_tools_as_unavailable_without_a_link(): void
    {
        $this->storage();
        Tool::query()->where('slug', 'inflation-calculator')->update(['status' => 'draft']);

        $response = $this->get(route('tools.pfm'))->assertOk();

        $response->assertSee('Unavailable');
        $response->assertDontSee('href="'.route('tools.pfm.inflation').'"', false);
        $response->assertSee('href="'.route('tools.pfm.money-planner').'"', false);
        $response->assertSee('<b>7</b> calculators', false);
    }

    public function test_pfm_hub_offers_a_shortcut_to_a_recent_result(): void
    {
        $this->storage();

        $this->get(route('tools.pfm'))->assertDontSee('View Result');

        $this->post(route('tools.pfm.inflation.calculate'), [
            'current_amount' => 10000,
            'inflation_percent' => 6,
            'years' => 10,
        ])->assertRedirect(route('tools.pfm.inflation.results'));

        $this->get(route('tools.pfm'))
            ->assertOk()
            ->assertSee('View Result')
            ->assertSee('href="'.route('tools.pfm.inflation.results').'"', false);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, int|string>}>
     */
    public static function everyToolProvider(): array
    {
        return [
            'money planner' => ['monthly-money-planner', 'tools.pfm.money-planner', ['monthly_income' => 80000, 'essential_expenses' => 50000, 'lifestyle_expenses' => 10000, 'emis' => 5000, 'other_recurring' => 0, 'investments' => 15000]],
            'net worth' => ['net-worth-calculator', 'tools.pfm.net-worth', ['cash' => 50000, 'bank' => 200000, 'fd' => 100000, 'mf' => 150000, 'stocks' => 80000, 'gold' => 60000, 'property' => 3000000, 'epf_nps' => 400000, 'loans' => 1500000, 'credit_cards' => 20000, 'other_debt' => 0]],
            'emergency fund' => ['emergency-fund-calculator', 'tools.pfm.emergency-fund', ['monthly_expenses' => 45000, 'coverage_months' => 6, 'current_savings' => 150000, 'monthly_saving_capacity' => 10000]],
            'financial goal' => ['financial-goal-calculator', 'tools.pfm.financial-goal', ['goal_type' => 'home', 'goal_name' => 'Dream flat', 'current_cost' => 2500000, 'years' => 10, 'inflation_percent' => 6, 'expected_return_percent' => 10, 'existing_savings' => 300000]],
            'debt emi' => ['debt-emi-calculator', 'tools.pfm.debt-emi', ['principal' => 2000000, 'annual_rate_percent' => 9, 'tenure_years' => 15, 'extra_payment' => 5000]],
            'retirement' => ['retirement-calculator', 'tools.pfm.retirement', ['current_age' => 30, 'retirement_age' => 60, 'life_expectancy' => 85, 'current_monthly_expenses' => 50000, 'current_assets' => 500000, 'inflation_percent' => 6, 'pre_retirement_return_percent' => 10, 'post_retirement_return_percent' => 7]],
            'inflation' => ['inflation-calculator', 'tools.pfm.inflation', ['current_amount' => 10000, 'inflation_percent' => 6, 'years' => 10]],
            'savings investment' => ['savings-investment-calculator', 'tools.pfm.savings-investment', ['initial_amount' => 100000, 'monthly_contribution' => 5000, 'expected_return_percent' => 10, 'years' => 15]],
        ];
    }

    /**
     * @dataProvider everyToolProvider
     *
     * @param  array<string, int|string>  $input
     */
    public function test_every_tool_runs_the_full_signed_in_flow(string $toolKey, string $route, array $input): void
    {
        $this->storage();
        $user = $this->member('pfm-flow-'.$toolKey.'@example.com');

        $this->actingAs($user, 'user')->get(route($route))->assertOk();

        $this->actingAs($user, 'user')
            ->post(route($route.'.calculate'), $input)
            ->assertRedirect(route($route.'.results'));

        $this->actingAs($user, 'user')
            ->get(route($route.'.results'))
            ->assertOk()
            ->assertSee('Save result')
            ->assertSee('Save as draft')
            ->assertSee('View history')
            ->assertDontSee('NaN');

        $this->actingAs($user, 'user')->post(route($route.'.save'))->assertRedirect();

        $record = PfmCalculation::query()->where('user_id', $user->id)->where('tool_key', $toolKey)->first();
        $this->assertNotNull($record);

        $this->actingAs($user, 'user')->get(route($route.'.history'))->assertOk();
        $this->actingAs($user, 'user')->get(route($route.'.history.show', $record))->assertOk();

        $this->actingAs($user, 'user')
            ->delete(route($route.'.history.destroy', $record))
            ->assertRedirect(route($route.'.history'));
        $this->assertDatabaseMissing('pfm_calculations', ['id' => $record->id]);
    }

    public function test_pfm_stylesheet_keeps_its_own_colour_tokens_over_the_design_system(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-pfm.css'));

        // yuktione-tools-ds.css repoints these tokens at brand variables that are not loaded on PFM routes.
        $this->assertStringContainsString('.yk-pfm.yk-tools-ds', $css);
        $this->assertMatchesRegularExpression('/--yk-teal:\s*#[0-9a-f]{6};/i', $css);
    }

    public function test_edit_and_recalculate_clears_draft(): void
    {
        $this->storage();

        $this->post(route('tools.pfm.savings-investment.calculate'), [
            'initial_amount' => 10000,
            'monthly_contribution' => 1000,
            'expected_return_percent' => 0,
            'years' => 1,
        ])->assertRedirect(route('tools.pfm.savings-investment.results'));

        $this->post(route('tools.pfm.savings-investment.recalculate'))
            ->assertRedirect(route('tools.pfm.savings-investment'));

        $this->get(route('tools.pfm.savings-investment.results'))
            ->assertRedirect(route('tools.pfm.savings-investment'));
    }

    public function test_history_page_shows_metrics_and_actions(): void
    {
        $this->storage();
        $user = $this->member('pfm-history@example.com');

        $record = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'monthly-money-planner',
            'title' => 'Monthly Money Planner',
            'inputs' => [
                'monthly_income' => 50000,
                'essential_expenses' => 40000,
                'lifestyle_expenses' => 5000,
                'emis' => 0,
                'other_recurring' => 0,
                'investments' => 2000,
            ],
            'outputs' => [
                'free_cash' => -1700,
                'savings_rate_percent' => 0,
                'health_label' => 'Needs attention',
                'health_tone' => 'bad',
                'total_expenses' => 45000,
                'total_outflow' => 47000,
                'summary' => 'Free cash ₹-1,700.00 · Savings rate 0.00% · Needs attention',
            ],
            'summary' => 'Free cash ₹-1,700.00 · Savings rate 0.00% · Needs attention',
            'content_version' => 'pfm-logic-v2',
            'completion_key' => (string) \Illuminate\Support\Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($user, 'user')
            ->get(route('tools.pfm.money-planner.history'))
            ->assertOk()
            ->assertSee('Private history')
            ->assertSee('View results')
            ->assertSee('Continue')
            ->assertSee('Edit &amp; recalculate', false)
            ->assertSee('Delete')
            ->assertSee('Free cash')
            ->assertSee('Needs attention');

        $this->actingAs($user, 'user')
            ->get(route('tools.pfm.money-planner.history.show', $record))
            ->assertOk()
            ->assertSee('Monthly money picture')
            ->assertSee('Needs attention');
    }

    public function test_guest_cannot_open_private_history(): void
    {
        $this->storage();

        $this->get(route('tools.pfm.money-planner.history'))
            ->assertRedirect(route('login'));
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

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'PFM Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
