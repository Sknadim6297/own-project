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
 * Task 29 — Emergency Fund Recalculate restores inputs and never auto-saves.
 */
class EmergencyFundRecalculateTest extends TestCase
{
    private const ROUTE = 'tools.pfm.emergency-fund';

    private const TOOL_KEY = 'emergency-fund-calculator';

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

    public function test_recalculate_action_is_visible_on_results(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), $this->scenarioA())->assertRedirect();

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Recalculate')
            ->assertSee(route(self::ROUTE.'.recalculate'), false)
            ->assertDontSee('Edit &amp; recalculate', false)
            ->assertDontSee('Edit inputs');
    }

    public function test_recalculate_restores_inputs_including_coverage_and_zeros(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 20000,
            'coverage_months' => 6,
            'current_savings' => 0,
            'monthly_saving_capacity' => 0,
        ])->assertRedirect();

        $this->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        $html = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('value="20000"', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertMatchesRegularExpression('/name="coverage_months"[^>]*value="6"[^>]*checked/', $html);
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['result'] ?? null);
        $this->assertSame(20000.0, (float) (session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['normalized']['monthly_expenses'] ?? 0));
        $this->assertSame(0, PfmCalculation::query()->count());
    }

    public function test_scenarios_a_through_e_consecutive_recalculations_without_db_rows(): void
    {
        // A
        $this->post(route(self::ROUTE.'.calculate'), $this->scenarioA())->assertRedirect();
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('120,000.00')
            ->assertSee('90,000.00');

        // B — expenses 25000
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->get(route(self::ROUTE))->assertSee('value="20000"', false);
        $this->post(route(self::ROUTE.'.calculate'), array_merge($this->scenarioA(), [
            'monthly_expenses' => 25000,
        ]))->assertRedirect();
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('150,000.00')
            ->assertSee('120,000.00');

        // C — coverage 12
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 25000,
            'coverage_months' => 12,
            'current_savings' => 30000,
            'monthly_saving_capacity' => 10000,
        ])->assertRedirect();
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('300,000.00')
            ->assertSee('270,000.00');

        // D — savings 100000
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 25000,
            'coverage_months' => 12,
            'current_savings' => 100000,
            'monthly_saving_capacity' => 10000,
        ])->assertRedirect();
        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame(300000.0, (float) ($draft['result']['target_amount'] ?? 0));
        $this->assertSame(200000.0, (float) ($draft['result']['shortfall'] ?? 0));
        $this->assertSame(20.0, (float) ($draft['result']['months_to_target'] ?? 0));

        // E — capacity 20000 → months_to_target 10
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 25000,
            'coverage_months' => 12,
            'current_savings' => 100000,
            'monthly_saving_capacity' => 20000,
        ])->assertRedirect();
        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame(300000.0, (float) ($draft['result']['target_amount'] ?? 0));
        $this->assertSame(200000.0, (float) ($draft['result']['shortfall'] ?? 0));
        $this->assertSame(10.0, (float) ($draft['result']['months_to_target'] ?? 0));

        $this->assertSame(0, PfmCalculation::query()->count());
    }

    public function test_edit_mode_recalculate_preserves_editing_id_and_does_not_update_db(): void
    {
        $user = $this->member('ef-recalc-edit@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->scenarioA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation([
            'monthly_expenses' => 15000,
            'coverage_months' => 6,
            'current_savings' => 20000,
            'monthly_saving_capacity' => 4000,
        ]);

        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());

        $aId = $a->id;
        $aCreated = (string) $a->created_at;
        $aKey = $a->completion_key;
        $aUpdated = (string) $a->updated_at;

        $this->get(route(self::ROUTE.'.continue', $a))->assertRedirect();
        $editAt = (int) (session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['editing_updated_at'] ?? 0);

        for ($i = 0; $i < 3; $i++) {
            $expenses = 20000 + (($i + 1) * 1000);
            $this->post(route(self::ROUTE.'.calculate'), [
                'monthly_expenses' => $expenses,
                'coverage_months' => 6,
                'current_savings' => 30000,
                'monthly_saving_capacity' => 10000,
            ])->assertRedirect(route(self::ROUTE.'.results'));

            $this->get(route(self::ROUTE.'.results'))
                ->assertOk()
                ->assertSee('Save Changes')
                ->assertSee('Preview only')
                ->assertSee('Recalculate');

            $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect(route(self::ROUTE));

            $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
            $this->assertSame((int) $aId, (int) ($draft['editing_id'] ?? 0));
            $this->assertSame($editAt, (int) ($draft['editing_updated_at'] ?? 0));
            $this->assertSame((float) $expenses, (float) ($draft['normalized']['monthly_expenses'] ?? 0));
            $this->assertNull($draft['result'] ?? null);

            $a->refresh();
            $this->assertSame(20000.0, (float) $a->inputs['monthly_expenses']);
            $this->assertSame(120000.0, (float) $a->outputs['target_amount']);
            $this->assertSame($aUpdated, (string) $a->updated_at);
            $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());
        }

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 25000,
            'coverage_months' => 6,
            'current_savings' => 50000,
            'monthly_saving_capacity' => 10000,
        ])->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route(self::ROUTE.'.history.show', $a));

        $a->refresh();
        $b->refresh();
        $this->assertSame($aId, $a->id);
        $this->assertSame($aKey, $a->completion_key);
        $this->assertSame($aCreated, (string) $a->created_at);
        $this->assertSame(25000.0, (float) $a->inputs['monthly_expenses']);
        $this->assertSame(150000.0, (float) $a->outputs['target_amount']);
        $this->assertSame(100000.0, (float) $a->outputs['shortfall']);
        $this->assertSame(15000.0, (float) $b->inputs['monthly_expenses']);
        $this->assertSame(90000.0, (float) $b->outputs['target_amount']);
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_cancel_edit_after_multiple_recalculations_preserves_original(): void
    {
        $user = $this->member('ef-recalc-cancel@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->scenarioA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();

        $this->post(route(self::ROUTE.'.calculate'), array_merge($this->scenarioA(), [
            'monthly_expenses' => 25000,
        ]))->assertRedirect();
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), array_merge($this->scenarioA(), [
            'monthly_expenses' => 30000,
        ]))->assertRedirect();

        $this->post(route(self::ROUTE.'.cancel-edit'))
            ->assertRedirect(route(self::ROUTE.'.history.show', $saved));

        $saved->refresh();
        $this->assertSame(20000.0, (float) $saved->inputs['monthly_expenses']);
        $this->assertSame(120000.0, (float) $saved->outputs['target_amount']);
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
    }

    public function test_new_test_clears_draft_after_recalculate_unlike_recalculate(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), $this->scenarioA())->assertRedirect();
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $html = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="monthly_expenses"[^>]*value="20000"/', $html);
    }

    public function test_financial_edge_cases_via_recalculate_flow(): void
    {
        // Zero savings
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 6,
            'current_savings' => 0,
            'monthly_saving_capacity' => '',
        ])->assertRedirect();
        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame(60000.0, (float) $draft['result']['target_amount']);
        $this->assertSame(60000.0, (float) $draft['result']['shortfall']);
        $this->assertNull($draft['result']['months_to_target']);

        // Equal to target
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 6,
            'current_savings' => 60000,
            'monthly_saving_capacity' => 0,
        ])->assertRedirect();
        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame(0.0, (float) $draft['result']['shortfall']);

        // Surplus
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 6,
            'current_savings' => 80000.55,
            'monthly_saving_capacity' => 1000,
        ])->assertRedirect();
        $draft = session(PfmDraft::KEY_PREFIX.self::TOOL_KEY);
        $this->assertSame(0.0, (float) $draft['result']['shortfall']);
        $this->assertGreaterThanOrEqual(0, (float) $draft['result']['shortfall']);

        // Invalid negative
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'monthly_expenses' => -5,
                'coverage_months' => 6,
                'current_savings' => 0,
                'monthly_saving_capacity' => 0,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $this->assertSame(0, PfmCalculation::query()->count());
    }

    public function test_stale_edit_still_rejected_after_recalculate(): void
    {
        $user = $this->member('ef-recalc-stale@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->scenarioA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), array_merge($this->scenarioA(), [
            'monthly_expenses' => 25000,
        ]))->assertRedirect();
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), array_merge($this->scenarioA(), [
            'monthly_expenses' => 26000,
        ]))->assertRedirect();

        $saved->timestamps = false;
        $saved->inputs = array_merge($saved->inputs, ['monthly_expenses' => 21000]);
        $saved->outputs = array_merge($saved->outputs, ['target_amount' => 126000]);
        $saved->updated_at = now()->addMinute();
        $saved->save();

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route(self::ROUTE.'.results'))
            ->assertSessionHasErrors('save');

        $saved->refresh();
        $this->assertSame(21000.0, (float) $saved->inputs['monthly_expenses']);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_other_tool_drafts_and_labels_remain_isolated(): void
    {
        $this->post(route('tools.pfm.money-planner.calculate'), [
            'monthly_income' => 40000,
            'essential_expenses' => 20000,
            'lifestyle_expenses' => 5000,
            'emis' => 0,
            'other_recurring' => 0,
            'investments' => 2000,
        ])->assertRedirect();

        $this->post(route(self::ROUTE.'.calculate'), $this->scenarioA())->assertRedirect();
        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect();

        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.'monthly-money-planner')['result'] ?? null);
        $this->assertNotNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['result'] ?? null);

        $this->get(route('tools.pfm.money-planner.results'))
            ->assertOk()
            ->assertSee('Edit &amp; recalculate', false);
    }

    /**
     * @return array<string, float|int>
     */
    private function scenarioA(): array
    {
        return [
            'monthly_expenses' => 20000,
            'coverage_months' => 6,
            'current_savings' => 30000,
            'monthly_saving_capacity' => 10000,
        ];
    }

    /**
     * @param  array<string, float|int>  $input
     */
    private function saveCalculation(array $input): PfmCalculation
    {
        $this->post(route(self::ROUTE.'.calculate'), $input)->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        return PfmCalculation::query()
            ->where('tool_key', self::TOOL_KEY)
            ->orderByDesc('id')
            ->firstOrFail();
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
            'name' => 'Emergency Fund Recalc Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
