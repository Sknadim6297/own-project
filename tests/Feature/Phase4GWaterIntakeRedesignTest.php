<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Models\WaterIntakeEntry;
use App\Models\WaterIntakeSetting;
use App\Services\WaterIntakeService;
use App\Support\WaterIntake\WaterIntakeCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4G — Water Intake Tracker premium redesign (UI + tracking integrity).
 */
class Phase4GWaterIntakeRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_water_pages_opt_into_tools_ds_and_drop_lavender(): void
    {
        $this->storage(true);

        $cssPath = public_path('frontend/assets/css/yuktione-water-intake.css');
        $jsPath = public_path('frontend/assets/js/yuktione-water-intake.js');
        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);

        $css = file_get_contents($cssPath);
        $this->assertStringNotContainsString('--yk-lavender', $css);
        $this->assertStringNotContainsString('157, 129, 219', $css);
        $this->assertStringContainsString('--yk-gold', $css);
        $this->assertStringContainsString('--yk-teal', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('yk-water__progress', $css);

        $js = file_get_contents($jsPath);
        $this->assertStringContainsString('data-water-submit-once', $js);
        $this->assertStringContainsString('data-submitting', $js);
        $this->assertStringNotContainsString('fetch(', $js);

        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-water', false)
            ->assertSee('yuktione-water-intake.js', false)
            ->assertSee('Log in to start tracking')
            ->assertDontSee('Quick add')
            ->assertDontSee('YuktiPoints', false);

        $user = $this->member('water4g-ds@example.com');
        $this->actingAs($user, 'user')
            ->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-water', false)
            ->assertSee('Quick add')
            ->assertSee('data-water-submit-once', false)
            ->assertSee('Custom amount')
            ->assertSee('Weekly history')
            ->assertDontSee('YuktiPoints', false);

        $layout = file_get_contents(resource_path('views/layouts/layout.blade.php'));
        $this->assertStringContainsString("routeIs('tools.water-intake*')", $layout);
        $this->assertStringContainsString('yuktione-water-intake.css', $layout);
        $this->assertStringContainsString('yuktione-water-intake.js', $layout);
    }

    public function test_guest_welcome_and_auth_gate_preserved(): void
    {
        $this->storage(true);

        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('Water Intake Tracker')
            ->assertSee('Log in to start tracking')
            ->assertSee(route('login'), false);

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 250,
            'idempotency_key' => 'guest-4g-1',
        ])->assertForbidden();

        $this->get(route('tools.water-intake.week'))->assertForbidden();
        $this->get(route('tools.water-intake.goal'))->assertForbidden();
        $this->get(route('tools.water-intake.today'))->assertForbidden();
    }

    public function test_authenticated_log_goal_week_and_over_goal_display(): void
    {
        $this->storage(true);
        $user = $this->member('water4g-flow@example.com');
        $this->actingAs($user, 'user');
        $service = app(WaterIntakeService::class);
        $today = $service->today()->toDateString();

        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('0%')
            ->assertSee('2,000 ml')
            ->assertSee('+150 ml')
            ->assertSee('+250 ml')
            ->assertSee('+500 ml')
            ->assertSee('+750 ml');

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 250,
            'idempotency_key' => '4g-qa-250',
        ])->assertRedirect(route('tools.water-intake', ['date' => $today]));

        $this->assertSame(250, $service->dayTotalMl($user->id, $today));

        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('250 ml')
            ->assertSee('Water entry added.');

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 0,
            'idempotency_key' => '4g-bad-0',
        ])->assertSessionHasErrors();

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 9000,
            'idempotency_key' => '4g-bad-big',
        ])->assertSessionHasErrors();

        $this->post(route('tools.water-intake.goal.update'), [
            'daily_goal_ml' => 500,
            'display_unit' => 'ml',
        ])->assertRedirect(route('tools.water-intake'));

        $settings = WaterIntakeSetting::query()->where('user_id', $user->id)->first();
        $this->assertSame(500, $settings->daily_goal_ml);
        $this->assertSame(2000, $settings->previous_goal_ml);

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 400,
            'idempotency_key' => '4g-over-1',
        ])->assertRedirect();

        $dash = $service->dashboard($user->id, $today);
        $this->assertSame(650, $dash['total_ml']);
        $this->assertSame(500, $dash['goal_ml']);
        $this->assertSame(130, $dash['percent']);
        $this->assertSame(0, $dash['remaining_ml']);

        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('130%')
            ->assertSee('Over goal')
            ->assertSee('+150 ml');

        $this->get(route('tools.water-intake.week'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Weekly hydration')
            ->assertSee('No entries');

        $this->get(route('tools.water-intake.today'))
            ->assertOk()
            ->assertSee('Day’s intake')
            ->assertSee('250 ml')
            ->assertSee('400 ml');
    }

    public function test_idempotency_ownership_boundaries_and_no_reward_hooks(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));
        $this->assertNotContains('water-intake-tracker', config('yuktione_rewards.eligible_tool_completion_slugs'));

        $this->storage(true);
        $owner = $this->member('water4g-owner@example.com');
        $other = $this->member('water4g-other@example.com');
        $service = app(WaterIntakeService::class);

        $this->actingAs($owner, 'user')->post(route('tools.water-intake.store'), [
            'amount_ml' => 500,
            'idempotency_key' => '4g-idem-1',
        ])->assertRedirect();

        $this->actingAs($owner, 'user')->post(route('tools.water-intake.store'), [
            'amount_ml' => 150,
            'idempotency_key' => '4g-idem-1',
        ])->assertRedirect();

        $this->assertSame(1, WaterIntakeEntry::query()->where('user_id', $owner->id)->count());
        $this->assertSame(500, WaterIntakeEntry::query()->where('user_id', $owner->id)->value('amount_ml'));

        $entry = WaterIntakeEntry::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($other, 'user')
            ->put(route('tools.water-intake.entries.update', $entry), ['amount_ml' => 100])
            ->assertForbidden();
        $this->actingAs($other, 'user')
            ->delete(route('tools.water-intake.entries.destroy', $entry))
            ->assertForbidden();

        // 2026-11-03 00:00 IST = 2026-11-02 18:30 UTC
        $justAfter = CarbonImmutable::parse('2026-11-02 18:30:00', 'UTC');
        $justBefore = CarbonImmutable::parse('2026-11-02 18:29:59', 'UTC');
        $service->addEntry($owner->id, 100, '4g-mid-1', $justAfter);
        $service->addEntry($owner->id, 200, '4g-mid-2', $justBefore);
        $this->assertSame(100, $service->dayTotalMl($owner->id, '2026-11-03'));
        $this->assertSame(200, $service->dayTotalMl($owner->id, '2026-11-02'));

        $controller = file_get_contents(app_path('Http/Controllers/Frontend/WaterIntakeController.php'));
        $this->assertStringNotContainsString('YuktiPoint', $controller);
        $this->assertStringNotContainsString('RewardEngine', $controller);
        $this->assertStringNotContainsString('completion_key', $controller);

        $this->assertFileExists(public_path('frontend/assets/css/yuktione-tarot.css'));
        $this->assertFileExists(public_path('frontend/assets/css/yuktione-bmi-bmr.css'));
        $this->assertStringNotContainsString('data-yk-water', file_get_contents(public_path('frontend/assets/css/yuktione-tarot.css')));
    }

    public function test_goal_validation_constraints_preserved(): void
    {
        $this->storage(true);
        $user = $this->member('water4g-goal@example.com');
        $this->actingAs($user, 'user');

        $this->get(route('tools.water-intake.goal'))
            ->assertOk()
            ->assertSee('Daily water goal')
            ->assertSee((string) WaterIntakeCatalog::GOAL_MIN_ML)
            ->assertSee(number_format(WaterIntakeCatalog::GOAL_MAX_ML));

        $this->post(route('tools.water-intake.goal.update'), [
            'daily_goal_ml' => WaterIntakeCatalog::GOAL_MIN_ML - 1,
            'display_unit' => 'ml',
        ])->assertSessionHasErrors('daily_goal_ml');

        $this->post(route('tools.water-intake.goal.update'), [
            'daily_goal_ml' => WaterIntakeCatalog::GOAL_MAX_ML + 1,
            'display_unit' => 'ml',
        ])->assertSessionHasErrors('daily_goal_ml');

        $this->post(route('tools.water-intake.goal.update'), [
            'daily_goal_ml' => 2500,
            'display_unit' => 'cups',
        ])->assertSessionHasErrors('display_unit');

        $this->post(route('tools.water-intake.goal.update'), [
            'daily_goal_ml' => 2500,
            'display_unit' => 'L',
        ])->assertRedirect(route('tools.water-intake'));

        $settings = WaterIntakeSetting::query()->where('user_id', $user->id)->first();
        $this->assertSame(2500, $settings->daily_goal_ml);
        $this->assertSame('L', $settings->display_unit);
    }

    private function storage(bool $open): void
    {
        foreach (['water_intake_entries', 'water_intake_settings', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
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
        Schema::create('water_intake_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->unsignedInteger('daily_goal_ml')->default(2000);
            $table->unsignedInteger('previous_goal_ml')->nullable();
            $table->timestamp('goal_changed_at')->nullable();
            $table->string('display_unit', 8)->default('ml');
            $table->timestamps();
        });
        Schema::create('water_intake_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_ml');
            $table->timestamp('consumed_at');
            $table->string('idempotency_key', 64);
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'consumed_at']);
        });

        $category = ToolCategory::query()->create(['name' => 'Nutrition', 'slug' => 'nutrition']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Water Intake Tracker',
            'slug' => 'water-intake-tracker',
            'module_key' => 'nutrition',
            'description' => 'Track water intake.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.water-intake' : null,
            'is_implemented' => $open,
            'display_order' => 2,
        ]);

    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase 4G Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
