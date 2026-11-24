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
 * Task 22 — New Test must start a genuinely fresh Monthly Money Planner calculation.
 */
class MonthlyMoneyPlannerNewTestTest extends TestCase
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

    public function test_money_planner_loads_and_calculates(): void
    {
        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Monthly Money Planner')
            ->assertSee('name="monthly_income"', false);

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('New Calculation')
            ->assertSee('7,000.00');
    }

    public function test_new_test_clears_previous_inputs_and_results_after_continue(): void
    {
        $user = $this->member('mmp-new-test@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $saved = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($saved);
        $savedInputs = $saved->inputs;
        $savedOutputs = $saved->outputs;
        $savedKey = $saved->completion_key;

        $this->get(route(self::ROUTE.'.continue', $saved))
            ->assertRedirect(route(self::ROUTE));

        $prefill = $this->get(route(self::ROUTE));
        $prefill->assertOk()
            ->assertSee('value="30000"', false)
            ->assertSee('value="18000"', false)
            ->assertSee('value="5000"', false);

        $this->post(route(self::ROUTE.'.new-test'))
            ->assertRedirect(route(self::ROUTE));

        $fresh = $this->get(route(self::ROUTE));
        $fresh->assertOk()
            ->assertSee('Started a new calculation')
            ->assertDontSee('value="30000"', false)
            ->assertDontSee('value="18000"', false)
            ->assertDontSee('value="5000"', false)
            ->assertDontSee('You have a recent result');

        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $saved->refresh();
        $this->assertSame($savedInputs, $saved->inputs);
        $this->assertSame($savedOutputs, $saved->outputs);
        $this->assertSame($savedKey, $saved->completion_key);
    }

    public function test_second_calculation_is_independent_after_new_test(): void
    {
        $user = $this->member('mmp-two-calcs@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $first = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route(self::ROUTE.'.continue', $first))->assertRedirect(route(self::ROUTE));
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));

        $this->post(route(self::ROUTE.'.calculate'), $this->inputB())
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('12,000.00')
            ->assertDontSee('value="30000"', false);

        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $rows = PfmCalculation::query()
            ->where('user_id', $user->id)
            ->where('tool_key', self::TOOL_KEY)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertNotSame($rows[0]->completion_key, $rows[1]->completion_key);
        $this->assertNotSame($rows[0]->id, $rows[1]->id);
        $this->assertSame(30000.0, (float) $rows[0]->inputs['monthly_income']);
        $this->assertSame(50000.0, (float) $rows[1]->inputs['monthly_income']);
        $this->assertSame(7000.0, (float) $rows[0]->outputs['free_cash']);
        $this->assertSame(12000.0, (float) $rows[1]->outputs['free_cash']);
        $this->assertSame(30000.0, (float) $first->fresh()->inputs['monthly_income']);
    }

    public function test_repeated_new_test_does_not_create_records(): void
    {
        $user = $this->member('mmp-repeat@example.com');
        $this->actingAs($user, 'user');

        $before = PfmCalculation::query()->count();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->assertSame($before, PfmCalculation::query()->count());
    }

    public function test_validation_failure_keeps_current_inputs_not_older_calculation(): void
    {
        $user = $this->member('mmp-validate@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_income' => 0,
            'essential_expenses' => 1000,
            'lifestyle_expenses' => 0,
            'emis' => 0,
            'other_recurring' => 0,
            'investments' => 0,
        ])->assertRedirect(route(self::ROUTE));

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('value="0"', false)
            ->assertDontSee('value="30000"', false);
    }

    public function test_new_test_does_not_clear_unrelated_tool_draft(): void
    {
        $this->post(route('tools.pfm.inflation.calculate'), [
            'current_amount' => 10000,
            'inflation_percent' => 6,
            'years' => 5,
        ])->assertRedirect(route('tools.pfm.inflation.results'));

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));

        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.'inflation-calculator'));
        $this->get(route('tools.pfm.inflation.results'))->assertOk();
    }

    public function test_results_and_history_expose_new_test_action(): void
    {
        $user = $this->member('mmp-buttons@example.com');
        $this->actingAs($user, 'user');

        $this->post(route(self::ROUTE.'.calculate'), $this->inputA())->assertRedirect();
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee(route(self::ROUTE.'.new-test'), false)
            ->assertSee('New Test');

        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $saved = PfmCalculation::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route(self::ROUTE.'.history.show', $saved))
            ->assertOk()
            ->assertSee('New Calculation')
            ->assertSee(route(self::ROUTE.'.new-test'), false);

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('New Calculation')
            ->assertSee(route(self::ROUTE.'.new-test'), false);
    }

    public function test_cross_user_continue_remains_forbidden(): void
    {
        $owner = $this->member('mmp-owner@example.com');
        $other = $this->member('mmp-intruder@example.com');

        $record = PfmCalculation::query()->create([
            'user_id' => $owner->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Monthly Money Planner',
            'inputs' => $this->inputA(),
            'outputs' => ['free_cash' => 7000, 'summary' => 'demo'],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.continue', $record))
            ->assertForbidden();
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
