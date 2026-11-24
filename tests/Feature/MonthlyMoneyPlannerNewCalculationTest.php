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
 * Task 25 — New Calculation reuses Task 22 fresh-start and creates independent records.
 */
class MonthlyMoneyPlannerNewCalculationTest extends TestCase
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

    public function test_new_calculation_navigation_uses_new_test_route(): void
    {
        $user = $this->member('mmp-nc-nav@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('New Calculation')
            ->assertSee(route(self::ROUTE.'.new-test'), false)
            ->assertDontSee('>New Test<', false);

        $this->get(route(self::ROUTE.'.history.show', $saved))
            ->assertOk()
            ->assertSee('New Calculation')
            ->assertSee(route(self::ROUTE.'.new-test'), false);

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('New Calculation');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('New Calculation')
            ->assertSee('Save Calculation');
    }

    public function test_new_calculation_clears_draft_and_does_not_create_row(): void
    {
        $user = $this->member('mmp-nc-clear@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->post(route(self::ROUTE.'.calculate'), $this->inputB())->assertRedirect();
        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $this->post(route(self::ROUTE.'.new-test'))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHas('status');

        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());

        $form = $this->get(route(self::ROUTE))->assertOk();
        $form->assertDontSee('value="30000"', false);
        $form->assertDontSee('value="50000"', false);
        $form->assertDontSee('value="60000"', false);
    }

    public function test_new_calculation_from_edit_mode_cannot_update_old_record(): void
    {
        $user = $this->member('mmp-nc-edit@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect();
        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame((int) $b->id, (int) ($draft['editing_id'] ?? 0));

        $this->post(route(self::ROUTE.'.calculate'), $this->inputC())->assertRedirect();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));

        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $b->refresh();
        $this->assertSame(60000.0, (float) $b->inputs['monthly_income']);
        $this->assertSame(18000.0, (float) $b->outputs['free_cash']);

        $this->post(route(self::ROUTE.'.calculate'), $this->inputC())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $this->assertSame(3, PfmCalculation::query()->where('user_id', $user->id)->count());
        $c = PfmCalculation::query()->where('user_id', $user->id)->orderByDesc('id')->firstOrFail();
        $this->assertNotSame($a->id, $c->id);
        $this->assertNotSame($b->id, $c->id);
        $this->assertNotSame($b->completion_key, $c->completion_key);
        $this->assertSame(80000.0, (float) $c->inputs['monthly_income']);
        $this->assertSame(25000.0, (float) $c->outputs['free_cash']);

        $a->refresh();
        $b->refresh();
        $this->assertSame(30000.0, (float) $a->inputs['monthly_income']);
        $this->assertSame(60000.0, (float) $b->inputs['monthly_income']);
    }

    public function test_saving_new_calculation_creates_independent_abc_records(): void
    {
        $user = $this->member('mmp-nc-abc@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->post(route(self::ROUTE.'.calculate'), $this->inputC())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect();

        $this->assertSame(3, PfmCalculation::query()->where('user_id', $user->id)->count());
        $c = PfmCalculation::query()->where('user_id', $user->id)->orderByDesc('id')->firstOrFail();

        $this->get(route(self::ROUTE.'.history.show', $c))
            ->assertOk()
            ->assertSee('80,000.00')
            ->assertSee('25,000.00');

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee(route(self::ROUTE.'.history.show', $c), false);

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('80,000.00')
            ->assertSee('60,000.00')
            ->assertSee('30,000.00');

        $a->refresh();
        $b->refresh();
        $this->assertSame(7000.0, (float) $a->outputs['free_cash']);
        $this->assertSame(18000.0, (float) $b->outputs['free_cash']);
    }

    public function test_new_calculation_does_not_affect_unrelated_tool_draft(): void
    {
        $user = $this->member('mmp-nc-isolation@example.com');
        $this->actingAs($user, 'user');

        $otherKey = 'inflation-calculator';
        session([
            PfmDraft::KEY_PREFIX.$otherKey => [
                'result' => ['future_cost' => 12000],
                'normalized' => ['current_amount' => 10000],
                'completion_key' => (string) Str::uuid(),
                'awaiting_login' => false,
                'editing_id' => null,
                'editing_updated_at' => null,
                'owner' => 'user:'.$user->id,
                'expires_at' => now()->addHour()->getTimestamp(),
            ],
        ]);

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();

        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $this->assertIsArray(session(PfmDraft::KEY_PREFIX.$otherKey));
        $this->assertSame(10000.0, (float) session(PfmDraft::KEY_PREFIX.$otherKey)['normalized']['current_amount']);
    }

    public function test_repeated_new_calculation_and_duplicate_save_remain_safe(): void
    {
        $user = $this->member('mmp-nc-dup@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->post(route(self::ROUTE.'.calculate'), $this->inputC())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect(route(self::ROUTE));

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_results_confirm_attribute_present_when_unsaved(): void
    {
        $user = $this->member('mmp-nc-confirm@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Start a new calculation? Your unsaved changes will be discarded', false);
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
