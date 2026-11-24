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
 * Task 24 — Edit & Save updates the selected Monthly Money Planner record.
 */
class MonthlyMoneyPlannerEditSaveTest extends TestCase
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

    public function test_edit_form_prefills_owned_calculation_inputs(): void
    {
        $user = $this->member('mmp-edit-prefill@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());

        $this->get(route(self::ROUTE.'.continue', $saved))
            ->assertRedirect(route(self::ROUTE));

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertSee('Edit Calculation')
            ->assertSee('Editing saved calculation #'.$saved->id)
            ->assertSee('Recalculate')
            ->assertSee('Cancel')
            ->assertSee('value="30000"', false)
            ->assertSee('value="18000"', false)
            ->assertSee('value="5000"', false)
            ->assertSee('value="0"', false);
    }

    public function test_editing_b_does_not_load_a(): void
    {
        $user = $this->member('mmp-edit-ab@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect(route(self::ROUTE));

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Editing saved calculation #'.$b->id)
            ->assertSee('value="50000"', false)
            ->assertSee('value="20000"', false)
            ->assertDontSee('value="30000"', false)
            ->assertDontSee('Editing saved calculation #'.$a->id);
    }

    public function test_recalculate_does_not_modify_saved_record(): void
    {
        $user = $this->member('mmp-edit-preview@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputB());
        $createdAt = (string) $saved->created_at;
        $updatedAt = (string) $saved->updated_at;

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputBEdited())
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Save Changes')
            ->assertSee('18,000.00')
            ->assertSee('Preview only');

        $saved->refresh();
        $this->assertSame(50000.0, (float) $saved->inputs['monthly_income']);
        $this->assertSame(12000.0, (float) $saved->outputs['free_cash']);
        $this->assertSame($createdAt, (string) $saved->created_at);
        $this->assertSame($updatedAt, (string) $saved->updated_at);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_save_changes_updates_existing_record_atomically(): void
    {
        $user = $this->member('mmp-edit-save@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $bId = $b->id;
        $bCreated = (string) $b->created_at;
        $bKey = $b->completion_key;

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputBEdited())->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route(self::ROUTE.'.history.show', $b));

        $b->refresh();
        $a->refresh();

        $this->assertSame($bId, $b->id);
        $this->assertSame($bKey, $b->completion_key);
        $this->assertSame($bCreated, (string) $b->created_at);
        $this->assertSame(60000.0, (float) $b->inputs['monthly_income']);
        $this->assertSame(7000.0, (float) $b->inputs['investments']);
        $this->assertSame(18000.0, (float) $b->outputs['free_cash']);
        $this->assertSame(30000.0, (float) $a->inputs['monthly_income']);
        $this->assertSame(7000.0, (float) $a->outputs['free_cash']);
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->get(route(self::ROUTE.'.history.show', $b))
            ->assertOk()
            ->assertSee('Changes saved.')
            ->assertSee('60,000.00')
            ->assertSee('18,000.00');

        $this->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee('60,000.00')
            ->assertSee('30,000.00');
    }

    public function test_cancel_leaves_saved_data_unchanged(): void
    {
        $user = $this->member('mmp-edit-cancel@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputB());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputBEdited())->assertRedirect();

        $this->post(route(self::ROUTE.'.cancel-edit'))
            ->assertRedirect(route(self::ROUTE.'.history.show', $saved));

        $saved->refresh();
        $this->assertSame(50000.0, (float) $saved->inputs['monthly_income']);
        $this->assertSame(12000.0, (float) $saved->outputs['free_cash']);
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
    }

    public function test_validation_errors_preserve_edited_inputs(): void
    {
        $user = $this->member('mmp-edit-validation@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'monthly_income' => -10,
                'essential_expenses' => 1000,
                'lifestyle_expenses' => 0,
                'emis' => 0,
                'other_recurring' => 0,
                'investments' => 0,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('value="-10"', false)
            ->assertSee('Editing saved calculation');

        $saved->refresh();
        $this->assertSame(30000.0, (float) $saved->inputs['monthly_income']);
    }

    public function test_duplicate_save_changes_does_not_create_extra_rows(): void
    {
        $user = $this->member('mmp-edit-dup@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputB());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputBEdited())->assertRedirect();

        $this->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect(route(self::ROUTE));

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_stale_concurrent_edit_is_rejected(): void
    {
        $user = $this->member('mmp-edit-stale@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputB());
        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $this->post(route(self::ROUTE.'.calculate'), $this->inputBEdited())->assertRedirect();

        // Simulate another tab saving first (force a newer updated_at than the edit session).
        $saved->timestamps = false;
        $saved->inputs = array_merge($saved->inputs, ['monthly_income' => 55000]);
        $saved->outputs = array_merge($saved->outputs, ['free_cash' => 15000]);
        $saved->summary = 'Updated elsewhere';
        $saved->updated_at = now()->addMinute();
        $saved->save();

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route(self::ROUTE.'.results'))
            ->assertSessionHasErrors('save');

        $saved->refresh();
        $this->assertSame(55000.0, (float) $saved->inputs['monthly_income']);
        $this->assertSame(15000.0, (float) $saved->outputs['free_cash']);
        $this->assertNotSame(18000.0, (float) $saved->outputs['free_cash']);
    }

    public function test_ownership_cross_tool_and_guest_edit_are_rejected(): void
    {
        $owner = $this->member('mmp-edit-owner@example.com');
        $other = $this->member('mmp-edit-other@example.com');

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

        $this->get(route(self::ROUTE.'.continue', $money))->assertRedirect(route('login'));

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.continue', $money))
            ->assertForbidden();

        $this->actingAs($owner, 'user')
            ->get(route(self::ROUTE.'.continue', $inflation))
            ->assertForbidden();
    }

    public function test_new_test_clears_edit_context_and_creates_independent_record(): void
    {
        $user = $this->member('mmp-edit-newtest@example.com');
        $this->actingAs($user, 'user');

        $a = $this->saveCalculation($this->inputA());
        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect();
        $b = $this->saveCalculation($this->inputB());

        $this->get(route(self::ROUTE.'.continue', $b))->assertRedirect();
        $this->assertTrue(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['editing_id'] === $b->id
            || (int) (session(PfmDraft::KEY_PREFIX.self::TOOL_KEY)['editing_id'] ?? 0) === (int) $b->id);

        $this->post(route(self::ROUTE.'.new-test'))->assertRedirect(route(self::ROUTE));
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_income' => 40000,
            'essential_expenses' => 10000,
            'lifestyle_expenses' => 5000,
            'emis' => 0,
            'other_recurring' => 0,
            'investments' => 2000,
        ])->assertRedirect();
        $this->post(route(self::ROUTE.'.save'))->assertRedirect();

        $this->assertSame(3, PfmCalculation::query()->where('user_id', $user->id)->count());
        $c = PfmCalculation::query()->where('user_id', $user->id)->orderByDesc('id')->first();
        $this->assertNotSame($a->id, $c->id);
        $this->assertNotSame($b->id, $c->id);
        $this->assertSame(40000.0, (float) $c->inputs['monthly_income']);

        $b->refresh();
        $this->assertSame(50000.0, (float) $b->inputs['monthly_income']);
        $this->assertSame(12000.0, (float) $b->outputs['free_cash']);
    }

    public function test_zero_income_negative_free_cash_edit_save(): void
    {
        $user = $this->member('mmp-edit-edge@example.com');
        $this->actingAs($user, 'user');

        $saved = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => self::TOOL_KEY,
            'title' => 'Monthly Money Planner',
            'inputs' => [
                'monthly_income' => 10000,
                'essential_expenses' => 5000,
                'lifestyle_expenses' => 0,
                'emis' => 0,
                'other_recurring' => 0,
                'investments' => 0,
            ],
            'outputs' => [
                'total_expenses' => 5000,
                'total_outflow' => 5000,
                'free_cash' => 5000,
                'savings_rate_percent' => 50,
                'health_label' => 'Healthy',
                'health_tone' => 'good',
                'summary' => 'demo',
            ],
            'summary' => 'demo',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $this->get(route(self::ROUTE.'.continue', $saved))->assertRedirect();
        $response = $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_income' => 0,
            'essential_expenses' => 5000,
            'lifestyle_expenses' => 0,
            'emis' => 2000,
            'other_recurring' => 0,
            'investments' => 0,
        ]);

        // Zero income may be rejected by validation; if accepted, save must update consistently.
        if ($response->isRedirect() && $response->headers->get('Location') === route(self::ROUTE.'.results')) {
            $this->post(route(self::ROUTE.'.save'))->assertRedirect();
            $saved->refresh();
            $this->assertSame(0.0, (float) $saved->inputs['monthly_income']);
            $this->assertLessThan(0, (float) $saved->outputs['free_cash']);
        } else {
            $response->assertRedirect(route(self::ROUTE));
            $saved->refresh();
            $this->assertSame(10000.0, (float) $saved->inputs['monthly_income']);
        }
    }

    public function test_opening_show_saved_does_not_enter_edit_mode(): void
    {
        $user = $this->member('mmp-edit-show@example.com');
        $this->actingAs($user, 'user');

        $saved = $this->saveCalculation($this->inputA());
        $this->get(route(self::ROUTE.'.history.show', $saved))->assertOk();
        $this->assertNull(session(PfmDraft::KEY_PREFIX.self::TOOL_KEY));
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

    /**
     * @return array<string, float|int>
     */
    private function inputBEdited(): array
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
