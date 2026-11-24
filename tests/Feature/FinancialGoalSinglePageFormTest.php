<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 35 — Financial Goal Calculator single continuous input form (no wizard).
 */
class FinancialGoalSinglePageFormTest extends TestCase
{
    private const ROUTE = 'tools.pfm.financial-goal';

    private const FIELDS = [
        'goal_type',
        'goal_name',
        'current_cost',
        'years',
        'existing_savings',
        'inflation_percent',
        'expected_return_percent',
    ];

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

    public function test_all_seven_inputs_render_without_wizard_navigation(): void
    {
        $html = $this->actingAs($this->member('fg-single@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('yk-goal--single', false)
            ->assertSee('data-yk-goal-single', false)
            ->assertSee('goal-section-details', false)
            ->assertSee('goal-section-cost', false)
            ->assertSee('goal-section-duration', false)
            ->assertSee('goal-section-savings', false)
            ->assertSee('goal-section-assumptions', false)
            ->assertSee('Calculate')
            ->assertSee('all inputs on one page')
            ->getContent();

        foreach (self::FIELDS as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $html);
        }

        $this->assertStringNotContainsString('data-pfm-wizard', $html);
        $this->assertStringNotContainsString('data-pfm-next', $html);
        $this->assertStringNotContainsString('data-pfm-prev', $html);
        $this->assertStringNotContainsString('data-pfm-step=', $html);
        $this->assertStringNotContainsString('Calculator steps', $html);
        $this->assertStringNotContainsString('>Continue<', $html);
        $this->assertStringNotContainsString('3 quick steps', $html);

        // Defaults preserved
        $this->assertMatchesRegularExpression('/name="goal_type"[^>]*value="custom"[^>]*checked|value="custom"[^>]*checked[^>]*name="goal_type"|name="goal_type" value="custom"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="existing_savings"[^>]*value="0"|id="pfm-existing"[^>]*value="0"/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-inflation"[^>]*value="6"/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-return"[^>]*value="10"/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-years"[^>]*value=""/', $html);

        // Years slider still present (Task 33)
        $this->assertStringContainsString('data-yk-goal-years', $html);
        $this->assertStringContainsString('data-goal-years-slider', $html);
        $this->assertStringContainsString('yk-goal--compact-header', $html);
    }

    public function test_all_seven_inputs_submit_together_and_match_engine(): void
    {
        $user = $this->member('fg-single-calc@example.com');
        $payload = [
            'goal_type' => 'home',
            'goal_name' => 'Dream flat',
            'current_cost' => 2500000,
            'years' => 10,
            'existing_savings' => 300000,
            'inflation_percent' => 6,
            'expected_return_percent' => 10,
        ];

        $expected = app(PfmCalculator::class)->calculate('financial-goal-calculator', $payload);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $draft = session('pfm.draft.financial-goal-calculator');
        $this->assertIsArray($draft);
        $this->assertEqualsWithDelta((float) $expected['result']['monthly_investment'], (float) $draft['result']['monthly_investment'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['result']['required_corpus'], (float) $draft['result']['required_corpus'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['result']['funding_gap'], (float) $draft['result']['funding_gap'], 0.01);

        // Calculate must not auto-save a DB record
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_validation_preserves_values_on_single_page_form(): void
    {
        $user = $this->member('fg-single-val@example.com');
        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'goal_type' => 'education',
                'goal_name' => 'MBA',
                'current_cost' => 1500000,
                'years' => 0, // invalid
                'existing_savings' => 50000,
                'inflation_percent' => 6,
                'expected_return_percent' => 10,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('yk-goal--single', false)
            ->getContent();

        $this->assertStringContainsString('value="MBA"', $html);
        $this->assertStringContainsString('value="1500000"', $html);
        $this->assertStringContainsString('value="50000"', $html);
        $this->assertMatchesRegularExpression('/name="goal_type"[^>]*value="education"[^>]*checked|value="education"[^>]*checked/', $html);
        $this->assertStringNotContainsString('data-pfm-wizard', $html);
        $this->assertStringNotContainsString('data-pfm-next', $html);
    }

    public function test_draft_restore_and_recalculate_and_save_and_new_test(): void
    {
        $user = $this->member('fg-single-flow@example.com');
        $payload = [
            'goal_type' => 'car',
            'goal_name' => 'EV',
            'current_cost' => 900000,
            'years' => 7,
            'existing_savings' => 100000,
            'inflation_percent' => 5,
            'expected_return_percent' => 9,
        ];

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $form = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="pfm-years"[^>]*value="7(\.0)?"/', $form);
        $this->assertStringContainsString('value="900000"', $form);
        $this->assertStringContainsString('yk-goal--single', $form);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $beforeSave = PfmCalculation::query()->where('user_id', $user->id)->count();
        $this->assertSame(0, $beforeSave);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.save'))
            ->assertRedirect();

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertSame(7.0, (float) ($record->inputs['years'] ?? 0));

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk();

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.new-test'))
            ->assertRedirect(route(self::ROUTE));

        $fresh = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="pfm-years"[^>]*value=""/', $fresh);
        $this->assertStringContainsString('yk-goal--single', $fresh);
        $this->assertStringNotContainsString('data-pfm-next', $fresh);
    }

    public function test_other_pfm_wizards_remain_intact(): void
    {
        $ef = $this->actingAs($this->member('fg-single-ef@example.com'), 'user')
            ->get(route('tools.pfm.emergency-fund'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-pfm-wizard', $ef);
        $this->assertStringContainsString('data-pfm-next', $ef);
        $this->assertStringNotContainsString('yk-goal--single', $ef);
        $this->assertStringNotContainsString('yuktione-pfm-goal.css', $ef);

        $mmp = $this->actingAs($this->member('fg-single-mmp@example.com'), 'user')
            ->get(route('tools.pfm.money-planner'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-pfm-wizard', $mmp);
        $this->assertStringContainsString('data-pfm-next', $mmp);
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
            'name' => 'FG Single Page Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
