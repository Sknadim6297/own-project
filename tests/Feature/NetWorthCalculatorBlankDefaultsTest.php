<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmDraft;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 27 — Net Worth Calculator fresh inputs are blank; blanks normalize to zero.
 */
class NetWorthCalculatorBlankDefaultsTest extends TestCase
{
    private const ROUTE = 'tools.pfm.net-worth';

    private const TOOL_KEY = 'net-worth-calculator';

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

    public function test_fresh_net_worth_inputs_are_blank_not_zero(): void
    {
        $html = $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('placeholder="Enter amount"', false)
            ->getContent();

        foreach (['cash', 'bank', 'fd', 'mf', 'stocks', 'gold', 'property', 'epf_nps', 'loans', 'credit_cards', 'other_debt'] as $field) {
            $this->assertMatchesRegularExpression(
                '/name="'.$field.'"[^>]*value=""/',
                $html,
                $field.' should render blank on a fresh form'
            );
            $this->assertDoesNotMatchRegularExpression(
                '/name="'.$field.'"[^>]*value="0"/',
                $html,
                $field.' must not be prefilled with zero'
            );
        }
    }

    public function test_money_planner_still_uses_its_own_defaults(): void
    {
        $html = $this->get(route('tools.pfm.money-planner'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/name="essential_expenses"[^>]*value="0"/', $html);
        $this->assertMatchesRegularExpression('/name="monthly_income"[^>]*value=""/', $html);
    }

    public function test_blank_optional_fields_normalize_to_zero_and_scenario_a(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), [
            'cash' => 50000,
            'mf' => 100000,
            'loans' => 30000,
        ])->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('150,000.00')
            ->assertSee('30,000.00')
            ->assertSee('120,000.00');

        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame(0.0, (float) ($draft['normalized']['bank'] ?? -1));
        $this->assertSame(0.0, (float) ($draft['normalized']['credit_cards'] ?? -1));
        $this->assertSame(150000.0, (float) ($draft['result']['total_assets'] ?? 0));
        $this->assertSame(30000.0, (float) ($draft['result']['total_liabilities'] ?? 0));
        $this->assertSame(120000.0, (float) ($draft['result']['net_worth'] ?? 0));
    }

    public function test_assets_only_and_negative_and_all_blank_scenarios(): void
    {
        $calculator = app(PfmCalculator::class);

        $assetsOnly = $calculator->calculate(self::TOOL_KEY, [
            'cash' => 25000,
            'bank' => 50000,
        ]);
        $this->assertSame(75000.0, $assetsOnly['result']['net_worth']);
        $this->assertSame(0.0, $assetsOnly['normalized']['loans']);

        $negative = $calculator->calculate(self::TOOL_KEY, [
            'cash' => 20000,
            'loans' => 50000,
        ]);
        $this->assertSame(-30000.0, $negative['result']['net_worth']);

        $blank = $calculator->calculate(self::TOOL_KEY, []);
        $this->assertSame(0.0, $blank['result']['net_worth']);
        $this->assertSame(0.0, $blank['result']['total_assets']);
        $this->assertSame(0.0, $blank['result']['total_liabilities']);
    }

    public function test_explicit_zero_is_accepted_saved_and_restored(): void
    {
        $user = $this->member('nw-zero@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), [
            'cash' => 0,
            'bank' => 1000,
            'loans' => 0,
        ])->assertRedirect(route(self::ROUTE.'.results'));

        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(0.0, (float) $saved->inputs['cash']);
        $this->assertSame(0.0, (float) $saved->inputs['loans']);
        $this->assertSame(1000.0, (float) $saved->outputs['net_worth']);

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect(route(self::ROUTE));
        $form = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="cash"[^>]*value="0"/', $form);
        $this->assertMatchesRegularExpression('/name="bank"[^>]*value="1000"/', $form);
    }

    public function test_invalid_non_empty_values_are_rejected_and_preserved(): void
    {
        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'cash' => 'abc',
                'loans' => 10,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('value="abc"', false);
    }

    public function test_new_test_clears_net_worth_draft_back_to_blank(): void
    {
        $user = $this->member('nw-newtest@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), ['cash' => 5000, 'loans' => 1000])->assertRedirect();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));

        $html = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="cash"[^>]*value=""/', $html);
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
    }

    public function test_historical_saved_zero_values_remain_unchanged_on_show(): void
    {
        $user = $this->member('nw-hist@example.com');
        $record = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Net Worth Calculator',
            'inputs' => [
                'cash' => 0,
                'bank' => 5000,
                'fd' => 0,
                'mf' => 0,
                'stocks' => 0,
                'gold' => 0,
                'property' => 0,
                'epf_nps' => 0,
                'loans' => 0,
                'credit_cards' => 0,
                'other_debt' => 0,
            ],
            'outputs' => [
                'tool_key' => self::TOOL_KEY,
                'total_assets' => 5000,
                'total_liabilities' => 0,
                'net_worth' => 5000,
                'debt_asset_ratio' => 0,
                'summary' => 'Net worth ₹5,000.00 (assets ₹5,000.00 − liabilities ₹0.00)',
            ],
            'summary' => 'Net worth ₹5,000.00',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('5,000.00');

        $record->refresh();
        $this->assertSame(0.0, (float) $record->inputs['cash']);
        $this->assertSame(5000.0, (float) $record->inputs['bank']);
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
            'name' => 'Net Worth Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
