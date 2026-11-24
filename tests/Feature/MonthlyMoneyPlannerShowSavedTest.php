<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmDraft;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 23 — Show Saved displays original inputs/results without mutating drafts.
 */
class MonthlyMoneyPlannerShowSavedTest extends TestCase
{
    private const ROUTE = 'tools.pfm.money-planner';

    private const TOOL_KEY = 'monthly-money-planner';

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

    public function test_save_redirects_to_show_saved_with_original_inputs_and_results(): void
    {
        $user = $this->member('mmp-show-a@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $response = $this->post(route(self::ROUTE.'.save'));
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $response->assertRedirect(route(self::ROUTE.'.history.show', $saved));

        $show = $this->get(route(self::ROUTE.'.history.show', $saved));
        $show->assertOk()
            ->assertHeader('Cache-Control')
            ->assertSee('Show Saved', false)
            ->assertSee('Saved financial inputs')
            ->assertSee('Monthly income')
            ->assertSee('30,000.00')
            ->assertSee('18,000.00')
            ->assertSee('5,000.00')
            ->assertSee('7,000.00')
            ->assertSee('Financial allocation breakdown')
            ->assertSee('Monthly money picture')
            ->assertSee('New Calculation')
            ->assertSee('Edit Calculation')
            ->assertSee('View History');

        $cache = strtolower((string) $show->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);
    }

    public function test_calculation_a_and_b_remain_independent_on_show_saved(): void
    {
        $user = $this->member('mmp-show-ab@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $a = PfmCalculation::query()->where('user_id', $user->id)->orderBy('id')->firstOrFail();

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputB())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $b = PfmCalculation::query()->where('user_id', $user->id)->orderByDesc('id')->firstOrFail();

        $this->assertNotSame($a->id, $b->id);

        $this->get(route(self::ROUTE.'.history.show', $a))
            ->assertOk()
            ->assertSee('30,000.00')
            ->assertSee('7,000.00')
            ->assertDontSee('50,000.00')
            ->assertDontSee('12,000.00');

        $this->get(route(self::ROUTE.'.history.show', $b))
            ->assertOk()
            ->assertSee('50,000.00')
            ->assertSee('12,000.00')
            ->assertDontSee('value="30000"', false);

        $a->refresh();
        $b->refresh();
        $this->assertSame(30000.0, (float) $a->inputs['monthly_income']);
        $this->assertSame(7000.0, (float) $a->outputs['free_cash']);
        $this->assertSame(50000.0, (float) $b->inputs['monthly_income']);
        $this->assertSame(12000.0, (float) $b->outputs['free_cash']);
    }

    public function test_show_saved_does_not_overwrite_active_draft(): void
    {
        $user = $this->member('mmp-show-draft@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputB())->assertRedirect();

        $draftKey = PfmDraft::KEY_PREFIX.self::TOOL_KEY;
        $draftBefore = session($draftKey);
        $this->assertIsArray($draftBefore);
        $this->assertSame(50000.0, (float) ($draftBefore['normalized']['monthly_income'] ?? 0));

        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();

        $draftAfter = session($draftKey);
        $this->assertIsArray($draftAfter);
        $this->assertSame(
            (float) ($draftBefore['normalized']['monthly_income'] ?? 0),
            (float) ($draftAfter['normalized']['monthly_income'] ?? 0)
        );
        $this->assertSame(
            (string) ($draftBefore['completion_key'] ?? ''),
            (string) ($draftAfter['completion_key'] ?? '')
        );
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_form_banner_links_to_show_saved(): void
    {
        $user = $this->member('mmp-show-banner@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Show Saved')
            ->assertSee(route(self::ROUTE.'.history.show', $saved), false);
    }

    public function test_history_show_saved_opens_selected_record(): void
    {
        $user = $this->member('mmp-show-history@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('Show Saved')
            ->assertDontSee('>View results<', false);
    }

    public function test_zero_income_and_negative_free_cash_display_safely(): void
    {
        $user = $this->member('mmp-show-edge@example.com');
        $record = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Monthly Money Planner',
            'inputs' => [
                'monthly_income' => 0,
                'essential_expenses' => 5000,
                'lifestyle_expenses' => 0,
                'emis' => 2000,
                'other_recurring' => 0,
                'investments' => 0,
            ],
            'outputs' => [
                'total_expenses' => 7000,
                'total_outflow' => 7000,
                'free_cash' => -7000,
                'savings_rate' => 0,
                'savings_rate_percent' => 0,
                'health_label' => 'Needs attention',
                'health_tone' => 'bad',
                'summary' => 'Free cash −₹7,000.00 · Savings rate 0.00% · Needs attention',
            ],
            'summary' => 'Free cash −₹7,000.00 · Savings rate 0.00% · Needs attention',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('0.00')
            ->assertSee('7,000.00')
            ->assertSee('Needs attention');
    }

    public function test_refresh_show_saved_does_not_duplicate_records(): void
    {
        $user = $this->member('mmp-show-refresh@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();
        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();
        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_ownership_and_cross_tool_access_are_rejected(): void
    {
        $owner = $this->member('mmp-show-owner@example.com');
        $other = $this->member('mmp-show-other@example.com');

        $money = PfmCalculation::query()->create([
            'user_id' => $owner->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Monthly Money Planner',
            'inputs' => $this->inputA(),
            'outputs' => ['free_cash' => 7000, 'summary' => 'demo', 'savings_rate_percent' => 23.33, 'health_label' => 'Healthy', 'health_tone' => 'good', 'total_outflow' => 23000],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $inflation = PfmCalculation::query()->create([
            'user_id' => $owner->id,
            'tool_key' => 'inflation-calculator',
            'title' => 'Inflation',
            'inputs' => ['current_amount' => 10000],
            'outputs' => ['future_cost' => 12000],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->get(route(self::ROUTE.'.history.show', $money))->assertRedirect(route('login'));

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $money))
            ->assertForbidden();

        $this->actingAs($owner, 'user')
            ->get(route(self::ROUTE.'.history.show', $inflation))
            ->assertForbidden();
    }

    public function test_missing_optional_historical_fields_do_not_break_show(): void
    {
        $user = $this->member('mmp-show-legacy@example.com');
        $record = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Monthly Money Planner',
            'inputs' => [
                'monthly_income' => 20000,
                'essential_expenses' => 10000,
            ],
            'outputs' => [
                'free_cash' => 5000,
                'savings_rate_percent' => 10,
                'health_label' => 'Okay',
                'health_tone' => 'warn',
                'total_outflow' => 15000,
            ],
            'summary' => null,
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('20,000.00')
            ->assertSee('Saved financial inputs');
    }

    public function test_new_test_still_clears_draft_after_show_saved(): void
    {
        $user = $this->member('mmp-show-newtest@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect(route(self::ROUTE));
        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    /**
     * @return array<string, float|int>
     */
    private function inputA(): array
    {
        return [
            'monthly_income' => 30000,
            'essential_expenses' => 18000,
            'lifestyle_expenses' => 0,
            'emis' => 5000,
            'other_recurring' => 0,
            'investments' => 0,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function inputB(): array
    {
        return [
            'monthly_income' => 50000,
            'essential_expenses' => 20000,
            'lifestyle_expenses' => 8000,
            'emis' => 5000,
            'other_recurring' => 0,
            'investments' => 5000,
        ];
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
            'name' => 'Money Planner Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
