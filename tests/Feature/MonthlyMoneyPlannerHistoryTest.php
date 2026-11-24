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
 * Task 26 — Monthly Money Planner History clarity, actions, and isolation.
 */
class MonthlyMoneyPlannerHistoryTest extends TestCase
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

    public function test_history_requires_auth_and_sets_private_cache(): void
    {
        $this->get(route(self::ROUTE.'.history'))->assertRedirect(route('login'));

        $user = $this->member('mmp-hist-auth@example.com');
        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertHeader('Cache-Control');

        $cache = strtolower((string) $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);
    }

    public function test_empty_history_shows_money_planner_copy_and_new_calculation(): void
    {
        $user = $this->member('mmp-hist-empty@example.com');

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('No saved calculations yet')
            ->assertSee('Create your first monthly financial plan')
            ->assertSee('New Calculation')
            ->assertSee(route(self::ROUTE.'.new-test'), false)
            ->assertSee('View and manage your previously saved monthly financial plans.');
    }

    public function test_history_lists_abc_with_correct_metrics_and_actions(): void
    {
        $user = $this->member('mmp-hist-abc@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $c = $this->saveCalculation($this->inputC());

        $page = $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('3 saved calculations')
            ->assertSee('Monthly Money Plan')
            ->assertSee('Monthly income')
            ->assertSee('Expenses &amp; EMI', false)
            ->assertSee('Investments')
            ->assertSee('Available cash')
            ->assertSee('30,000.00')
            ->assertSee('60,000.00')
            ->assertSee('80,000.00')
            ->assertSee('7,000.00')
            ->assertSee('18,000.00')
            ->assertSee('25,000.00')
            ->assertSee('Show Saved')
            ->assertSee('Edit Calculation')
            ->assertDontSee('>Continue<', false)
            ->assertDontSee('Edit &amp; recalculate', false);

        $html = $page->getContent();
        $this->assertStringContainsString(route(self::ROUTE.'.history.show', $a), $html);
        $this->assertStringContainsString(route(self::ROUTE.'.continue', $b), $html);
        $this->assertStringContainsString(route(self::ROUTE.'.history.show', $c), $html);
    }

    public function test_history_orders_newest_first_and_edit_updates_card_values(): void
    {
        $user = $this->member('mmp-hist-edit@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $c = $this->saveCalculation($this->inputC());

        $bCreated = (string) $b->created_at;
        $bId = $b->id;

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputBEdited())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $b->refresh();
        $this->assertSame($bId, $b->id);
        $this->assertSame($bCreated, (string) $b->created_at);
        $this->assertSame(70000.0, (float) $b->inputs['monthly_income']);
        $this->assertSame(22000.0, (float) $b->outputs['free_cash']);
        $this->assertSame(3, PfmCalculation::query()->where('user_id', $user->id)->count());

        $history = $this->get(route(self::ROUTE.'.history'))->assertOk();
        $history->assertSee('70,000.00')
            ->assertSee('22,000.00')
            ->assertSee('30,000.00')
            ->assertSee('80,000.00')
            ->assertDontSee('value="60000"', false);

        $posC = strpos($history->getContent(), '80,000.00');
        $posB = strpos($history->getContent(), '70,000.00');
        $posA = strpos($history->getContent(), '30,000.00');
        $this->assertNotFalse($posC);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posA);
        $this->assertTrue($posC < $posB || $posB < $posA);

        $a->refresh();
        $c->refresh();
        $this->assertSame(30000.0, (float) $a->inputs['monthly_income']);
        $this->assertSame(80000.0, (float) $c->inputs['monthly_income']);
    }

    public function test_opening_history_does_not_modify_draft_or_records(): void
    {
        $user = $this->member('mmp-hist-draft@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputB())->assertRedirect();

        $draftBefore = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $updatedBefore = (string) $saved->updated_at;

        $this->get(route(self::ROUTE.'.history'))->assertOk();
        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();

        $this->assertSame(
            (float) ($draftBefore['normalized']['monthly_income'] ?? 0),
            (float) (session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['normalized']['monthly_income'] ?? 0)
        );
        $saved->refresh();
        $this->assertSame($updatedBefore, (string) $saved->updated_at);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_new_calculation_from_history_clears_edit_context(): void
    {
        $user = $this->member('mmp-hist-new@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['editing_id'] ?? null);

        $this->from(route(self::ROUTE.'.history'))
            ->post(route(self::ROUTE.'.new-test'))
            ->assertRedirect(route(self::ROUTE));

        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_cross_user_history_is_isolated(): void
    {
        $owner = $this->member('mmp-hist-owner@example.com');
        $other = $this->member('mmp-hist-other@example.com');

        $this->actingAs($owner, 'user');
        $this->saveCalculation($this->inputA());

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('No saved calculations yet')
            ->assertDontSee('30,000.00');
    }

    public function test_negative_and_zero_cash_display_safely(): void
    {
        $user = $this->member('mmp-hist-edge@example.com');
        PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Monthly Money Planner',
            'inputs' => [
                'monthly_income' => 10000,
                'essential_expenses' => 12000,
                'lifestyle_expenses' => 0,
                'emis' => 0,
                'other_recurring' => 0,
                'investments' => 0,
            ],
            'outputs' => [
                'total_expenses' => 12000,
                'free_cash' => -2000,
                'savings_rate_percent' => 0,
                'health_label' => 'Needs attention',
                'health_tone' => 'bad',
                'summary' => 'Free cash −₹2,000.00',
            ],
            'summary' => 'Free cash −₹2,000.00',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('Available cash')
            ->assertSee('2,000.00')
            ->assertSee('Needs attention');
    }

    public function test_fresh_start_partial_includes_client_dirty_hook(): void
    {
        $js = file_get_contents(public_path('frontend/assets/js/yuktione-pfm.js'));
        $this->assertStringContainsString('ykPfmConfirmFreshStart', $js);
        $this->assertStringContainsString('pfm.unsaved.monthly-money-planner', $js);

        $user = $this->member('mmp-hist-hook@example.com');
        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-yk-pfm-fresh-start', $html);
        $this->assertStringContainsString('ykPfmConfirmFreshStart', $html);
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
            'monthly_income' => 60000,
            'essential_expenses' => 22000,
            'lifestyle_expenses' => 8000,
            'emis' => 5000,
            'other_recurring' => 0,
            'investments' => 7000,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function inputBEdited(): array
    {
        return [
            'monthly_income' => 70000,
            'essential_expenses' => 25000,
            'lifestyle_expenses' => 10000,
            'emis' => 5000,
            'other_recurring' => 0,
            'investments' => 8000,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function inputC(): array
    {
        return [
            'monthly_income' => 80000,
            'essential_expenses' => 25000,
            'lifestyle_expenses' => 10000,
            'emis' => 10000,
            'other_recurring' => 0,
            'investments' => 10000,
        ];
    }

    /**
     * @param  array<string, float|int>  $input
     */
    private function saveCalculation(array $input): PfmCalculation
    {
        $this->post(route(self::ROUTE.'.calculate'), $input)->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        return PfmCalculation::query()->orderByDesc('id')->firstOrFail();
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
