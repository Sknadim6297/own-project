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
 * Task 28 — Edit & Save updates the selected Emergency Fund calculation in place.
 */
class EmergencyFundEditSaveTest extends TestCase
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

    public function test_edit_calculation_action_is_available_on_show_and_history(): void
    {
        $user = $this->member('ef-edit-actions@example.com');
        $this->actingAs($user, 'user');
        $saved = $this->saveCalculation($this->inputA());

        $this->get(route(self::ROUTE.'.history.show', $saved))
            ->assertOk()
            ->assertSee('Edit Calculation')
            ->assertDontSee('Edit &amp; recalculate', false)
            ->assertSee(route(self::ROUTE.'.continue', $saved), false);

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('Edit Calculation')
            ->assertDontSee('>Continue<', false)
            ->assertDontSee('Edit &amp; recalculate', false);
    }

    public function test_edit_form_prefills_owned_calculation_inputs(): void
    {
        $user = $this->member('ef-edit-prefill@example.com');
        $this->actingAs($user, 'user');
        $saved = $this->saveCalculation($this->inputA());

        $this->get(route(self::ROUTE.'.continue', $saved))
            ->assertRedirect(route(self::ROUTE));

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertSeeText('Edit Emergency Fund Calculation')
            ->assertSee('Editing saved calculation #'.$saved->id)
            ->assertSee('Recalculate')
            ->assertSee('Cancel Edit')
            ->assertSee('value="20000"', false)
            ->assertSee('value="30000"', false)
            ->assertSee('selected', false);
    }

    public function test_opening_edit_mode_does_not_modify_database(): void
    {
        $user = $this->member('ef-edit-open@example.com');
        $this->actingAs($user, 'user');
        $saved = $this->saveCalculation($this->inputA());
        $createdAt = (string) $saved->created_at;
        $updatedAt = (string) $saved->updated_at;
        $inputs = $saved->inputs;

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();

        $saved->refresh();
        $this->assertSame($inputs, $saved->inputs);
        $this->assertSame($createdAt, (string) $saved->created_at);
        $this->assertSame($updatedAt, (string) $saved->updated_at);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_editing_b_does_not_load_a(): void
    {
        $user = $this->member('ef-edit-ab@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect(route(self::ROUTE));

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Editing saved calculation #'.$b->id)
            ->assertSee('value="15000"', false)
            ->assertSee('value="20000"', false)
            ->assertDontSee('value="30000"', false)
            ->assertDontSee('Editing saved calculation #'.$a->id);
    }

    public function test_preview_does_not_modify_saved_record(): void
    {
        $user = $this->member('ef-edit-preview@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $createdAt = (string) $saved->created_at;
        $updatedAt = (string) $saved->updated_at;

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputAEdited())
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Save Changes')
            ->assertSee('Preview only')
            ->assertSee('150,000.00')
            ->assertSee('100,000.00');

        $saved->refresh();
        $this->assertSame(20000.0, (float) $saved->inputs['monthly_expenses']);
        $this->assertSame(120000.0, (float) $saved->outputs['target_amount']);
        $this->assertSame(90000.0, (float) $saved->outputs['shortfall']);
        $this->assertSame($createdAt, (string) $saved->created_at);
        $this->assertSame($updatedAt, (string) $saved->updated_at);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_save_changes_updates_existing_record_atomically(): void
    {
        $user = $this->member('ef-edit-save@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());
        $this->assertSame(120000.0, (float) $a->outputs['target_amount']);
        $this->assertSame(90000.0, (float) $a->outputs['shortfall']);
        $this->assertSame(90000.0, (float) $b->outputs['target_amount']);
        $this->assertSame(70000.0, (float) $b->outputs['shortfall']);

        $aId = $a->id;
        $aCreated = (string) $a->created_at;
        $aKey = $a->completion_key;

        $this->get(route(self::ROUTE.'.continue', $a))->assertRedirect();
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->post(route(self::ROUTE.'.calculate'), $this->inputAEdited())->assertRedirect();
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());
        $a->refresh();
        $this->assertSame(120000.0, (float) $a->outputs['target_amount']);

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route(self::ROUTE.'.history.show', $a));

        $a->refresh();
        $b->refresh();

        $this->assertSame($aId, $a->id);
        $this->assertSame($aKey, $a->completion_key);
        $this->assertSame($aCreated, (string) $a->created_at);
        $this->assertSame(25000.0, (float) $a->inputs['monthly_expenses']);
        $this->assertSame(50000.0, (float) $a->inputs['current_savings']);
        $this->assertSame(150000.0, (float) $a->outputs['target_amount']);
        $this->assertSame(100000.0, (float) $a->outputs['shortfall']);
        $this->assertSame(15000.0, (float) $b->inputs['monthly_expenses']);
        $this->assertSame(90000.0, (float) $b->outputs['target_amount']);
        $this->assertSame(70000.0, (float) $b->outputs['shortfall']);
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->get(route(self::ROUTE.'.history.show', $a))
            ->assertOk()
            ->assertSee('Emergency fund calculation updated successfully.')
            ->assertSee('150,000.00')
            ->assertSee('100,000.00');

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('150,000.00')
            ->assertSee('90,000.00');
    }

    public function test_cancel_edit_leaves_saved_data_unchanged(): void
    {
        $user = $this->member('ef-edit-cancel@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputAEdited())->assertRedirect();

        $this->post(route(self::ROUTE.'.cancel-edit'))
            ->assertRedirect(route(self::ROUTE.'.history.show', $saved));

        $saved->refresh();
        $this->assertSame(20000.0, (float) $saved->inputs['monthly_expenses']);
        $this->assertSame(120000.0, (float) $saved->outputs['target_amount']);
        $this->assertSame(90000.0, (float) $saved->outputs['shortfall']);
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
    }

    public function test_validation_errors_preserve_edited_inputs(): void
    {
        $user = $this->member('ef-edit-validation@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'monthly_expenses' => -10,
                'coverage_months' => 6,
                'current_savings' => 1000,
                'monthly_saving_capacity' => 0,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('value="-10"', false)
            ->assertSee('Editing saved calculation');

        $saved->refresh();
        $this->assertSame(20000.0, (float) $saved->inputs['monthly_expenses']);
    }

    public function test_duplicate_save_changes_does_not_create_extra_rows(): void
    {
        $user = $this->member('ef-edit-dup@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputAEdited())->assertRedirect();

        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect(route(self::ROUTE));

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_stale_concurrent_edit_is_rejected(): void
    {
        $user = $this->member('ef-edit-stale@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputAEdited())->assertRedirect();

        $saved->timestamps = false;
        $saved->inputs = array_merge($saved->inputs, ['monthly_expenses' => 22000]);
        $saved->outputs = array_merge($saved->outputs, ['target_amount' => 132000, 'shortfall' => 102000]);
        $saved->summary = 'Updated elsewhere';
        $saved->updated_at = now()->addMinute();
        $saved->save();

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route(self::ROUTE.'.results'))
            ->assertSessionHasErrors('save');

        $saved->refresh();
        $this->assertSame(22000.0, (float) $saved->inputs['monthly_expenses']);
        $this->assertSame(132000.0, (float) $saved->outputs['target_amount']);
        $this->assertNotSame(150000.0, (float) $saved->outputs['target_amount']);
    }

    public function test_ownership_cross_tool_and_guest_edit_are_rejected(): void
    {
        $owner = $this->member('ef-edit-owner@example.com');
        $other = $this->member('ef-edit-other@example.com');

        $emergency = PfmCalculation::query()->create([
            'user_id' => $owner->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Emergency Fund Calculator',
            'inputs' => $this->inputA(),
            'outputs' => [
                'target_amount' => 120000,
                'shortfall' => 90000,
                'coverage_months_actual' => 1.5,
                'months_to_target' => null,
                'summary' => 'demo',
            ],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $money = PfmCalculation::query()->create([
            'user_id' => $owner->id,
            'tool_key' => 'monthly-money-planner',
            'title' => 'Monthly Money Planner',
            'inputs' => ['monthly_income' => 30000],
            'outputs' => ['free_cash' => 1000],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $netWorth = PfmCalculation::query()->create([
            'user_id' => $owner->id,
            'tool_key' => 'net-worth-calculator',
            'title' => 'Net Worth',
            'inputs' => ['cash' => 1000],
            'outputs' => ['net_worth' => 1000],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->get(route(self::ROUTE.'.continue', $emergency))->assertRedirect(route('login'));

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.continue', $emergency))
            ->assertForbidden();

        $this->actingAs($owner, 'user')
            ->get(route(self::ROUTE.'.continue', $money))
            ->assertForbidden();

        $this->actingAs($owner, 'user')
            ->get(route(self::ROUTE.'.continue', $netWorth))
            ->assertForbidden();
    }

    public function test_new_test_clears_edit_context_and_creates_independent_record(): void
    {
        $user = $this->member('ef-edit-newtest@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect();
        $this->assertSame((int) $b->id, (int) (session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['editing_id'] ?? 0));

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 3,
            'current_savings' => 5000,
            'monthly_saving_capacity' => 1000,
        ])->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $this->assertSame(3, PfmCalculation::query()->where('user_id', $user->id)->count());
        $c = PfmCalculation::query()->where('user_id', $user->id)->orderByDesc('id')->first();
        $this->assertNotSame($a->id, $c->id);
        $this->assertNotSame($b->id, $c->id);
        $this->assertSame(10000.0, (float) $c->inputs['monthly_expenses']);

        $b->refresh();
        $this->assertSame(15000.0, (float) $b->inputs['monthly_expenses']);
        $this->assertSame(90000.0, (float) $b->outputs['target_amount']);
    }

    public function test_zero_savings_and_surplus_edge_cases_edit_save(): void
    {
        $user = $this->member('ef-edit-edge@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation([
            'monthly_expenses' => 10000,
            'coverage_months' => 6,
            'current_savings' => 0,
            'monthly_saving_capacity' => 5000,
        ]);
        $this->assertSame(60000.0, (float) $saved->outputs['target_amount']);
        $this->assertSame(60000.0, (float) $saved->outputs['shortfall']);

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 6,
            'current_savings' => 60000,
            'monthly_saving_capacity' => 0,
        ])->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $saved->refresh();
        $this->assertSame(60000.0, (float) $saved->inputs['current_savings']);
        $this->assertSame(0.0, (float) $saved->outputs['shortfall']);

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 6,
            'current_savings' => 80000,
            'monthly_saving_capacity' => 0,
        ])->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $saved->refresh();
        $this->assertSame(80000.0, (float) $saved->inputs['current_savings']);
        $this->assertSame(0.0, (float) $saved->outputs['shortfall']);
    }

    public function test_opening_show_saved_does_not_enter_edit_mode(): void
    {
        $user = $this->member('ef-edit-show@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
    }

    public function test_money_planner_edit_still_works_independently(): void
    {
        $user = $this->member('ef-edit-mmp-iso@example.com');
        $this->actingAs($user, 'user');

        $ef = $this->saveCalculation($this->inputA());
        $this->post(route('tools.pfm.money-planner.calculate'), [
            'monthly_income' => 40000,
            'essential_expenses' => 20000,
            'lifestyle_expenses' => 5000,
            'emis' => 0,
            'other_recurring' => 0,
            'investments' => 2000,
        ])->assertRedirect();
        $this->post(route('tools.pfm.money-planner.save'))->assertRedirect();
        $mmp = PfmCalculation::query()
            ->where('user_id', $user->id)
            ->where('tool_key', 'monthly-money-planner')
            ->firstOrFail();

        $this->get(route(self::ROUTE.'.continue', $ef))->assertRedirect();
        $this->assertSame((int) $ef->id, (int) (session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['editing_id'] ?? 0));
        $this->assertNull(session(PfmDraft::KEY_PREFIX.'monthly-money-planner'));

        $this->get(route('tools.pfm.money-planner.continue', $mmp))->assertRedirect();
        $this->assertSame((int) $mmp->id, (int) (session(PfmDraft::KEY_PREFIX.'monthly-money-planner')['editing_id'] ?? 0));
    }

    /**
     * @return array<string, float|int>
     */
    private function inputA(): array
    {
        return [
            'monthly_expenses' => 20000,
            'coverage_months' => 6,
            'current_savings' => 30000,
            'monthly_saving_capacity' => 5000,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function inputB(): array
    {
        return [
            'monthly_expenses' => 15000,
            'coverage_months' => 6,
            'current_savings' => 20000,
            'monthly_saving_capacity' => 4000,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function inputAEdited(): array
    {
        return [
            'monthly_expenses' => 25000,
            'coverage_months' => 6,
            'current_savings' => 50000,
            'monthly_saving_capacity' => 5000,
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
            'name' => 'Emergency Fund Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
