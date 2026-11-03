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

class WaterIntakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_guest_sees_intro_and_cannot_store(): void
    {
        $this->storage(true);
        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('Log in to start tracking')
            ->assertDontSee('Quick adds');

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 250,
            'idempotency_key' => 'guest-key-1',
        ])->assertForbidden();
    }

    public function test_empty_dashboard_quick_add_custom_and_validation(): void
    {
        $this->storage(true);
        $user = $this->member('water@example.com');
        $this->actingAs($user, 'user');

        $this->get(route('tools.water-intake'))
            ->assertOk()
            ->assertSee('0%')
            ->assertSee('2,000 ml');

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 250,
            'idempotency_key' => 'qa-1',
        ])->assertRedirect();

        $this->assertSame(250, app(WaterIntakeService::class)->dayTotalMl($user->id, app(WaterIntakeService::class)->today()->toDateString()));

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 0,
            'idempotency_key' => 'bad-0',
        ])->assertSessionHasErrors();

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => -5,
            'idempotency_key' => 'bad-neg',
        ])->assertSessionHasErrors();

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 9000,
            'idempotency_key' => 'bad-big',
        ])->assertSessionHasErrors();

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 150,
            'idempotency_key' => 'qa-1',
        ])->assertRedirect();
        $this->assertSame(1, WaterIntakeEntry::query()->count());
        $this->assertSame(250, WaterIntakeEntry::query()->first()->amount_ml);
    }

    public function test_edit_delete_ownership_and_totals(): void
    {
        $this->storage(true);
        $owner = $this->member('owner-water@example.com');
        $other = $this->member('other-water@example.com');
        $this->actingAs($owner, 'user');

        $this->post(route('tools.water-intake.store'), [
            'amount_ml' => 500,
            'idempotency_key' => 'e1',
        ]);
        $entry = WaterIntakeEntry::query()->first();

        $this->actingAs($other, 'user')
            ->put(route('tools.water-intake.entries.update', $entry), ['amount_ml' => 100])
            ->assertForbidden();
        $this->actingAs($other, 'user')
            ->delete(route('tools.water-intake.entries.destroy', $entry))
            ->assertForbidden();

        $this->actingAs($owner, 'user')
            ->put(route('tools.water-intake.entries.update', $entry), ['amount_ml' => 300])
            ->assertRedirect();
        $this->assertSame(300, $entry->fresh()->amount_ml);

        $service = app(WaterIntakeService::class);
        $date = $service->today()->toDateString();
        $this->assertSame(300, $service->dayTotalMl($owner->id, $date));

        $this->delete(route('tools.water-intake.entries.destroy', $entry))->assertRedirect();
        $this->assertSame(0, WaterIntakeEntry::query()->count());
        $this->assertSame(0, $service->dayTotalMl($owner->id, $date));
    }

    public function test_goal_change_and_week_aggregation_with_missing_days(): void
    {
        $this->storage(true);
        $user = $this->member('week-water@example.com');
        $this->actingAs($user, 'user');
        $service = app(WaterIntakeService::class);

        $this->post(route('tools.water-intake.goal.update'), [
            'daily_goal_ml' => 3000,
            'display_unit' => 'L',
        ])->assertRedirect();

        $settings = WaterIntakeSetting::query()->where('user_id', $user->id)->first();
        $this->assertSame(3000, $settings->daily_goal_ml);
        $this->assertSame('L', $settings->display_unit);
        $this->assertSame(2000, $settings->previous_goal_ml);

        $today = $service->today();
        $service->addEntry($user->id, 500, 'w1', $today->setTime(10, 0));
        $service->addEntry($user->id, 250, 'w2', $today->subDay()->setTime(9, 0));

        $week = $service->weekSummary($user->id, $today->toDateString());
        $this->assertCount(7, $week);
        $logged = array_values(array_filter($week, fn ($d) => $d['has_entries']));
        $this->assertCount(2, $logged);
        $empty = array_values(array_filter($week, fn ($d) => ! $d['has_entries']));
        $this->assertNotEmpty($empty);

        $this->get(route('tools.water-intake.week'))
            ->assertOk()
            ->assertSee('Weekly hydration')
            ->assertSee('No entries');
    }

    public function test_midnight_boundary_places_entry_on_correct_local_day(): void
    {
        $this->storage(true);
        $user = $this->member('midnight@example.com');
        $service = app(WaterIntakeService::class);

        // 2026-11-03 00:00 IST = 2026-11-02 18:30 UTC
        $justAfter = CarbonImmutable::parse('2026-11-02 18:30:00', 'UTC');
        $justBefore = CarbonImmutable::parse('2026-11-02 18:29:59', 'UTC');

        $service->addEntry($user->id, 100, 'mid-1', $justAfter);
        $service->addEntry($user->id, 200, 'mid-2', $justBefore);

        $this->assertSame(100, $service->dayTotalMl($user->id, '2026-11-03'));
        $this->assertSame(200, $service->dayTotalMl($user->id, '2026-11-02'));
    }

    public function test_publish_keeps_other_nutrition_unpublished(): void
    {
        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
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
            $table->string('icon')->nullable();
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        app(\App\Services\ToolRegistryRegistrar::class)->register();
        (require database_path('migrations/2026_11_03_190000_publish_water_intake_tracker.php'))->up();

        $this->assertTrue(Tool::query()->where('slug', 'water-intake-tracker')->first()->canLaunch());
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'weight-journey')->value('status'));
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'calorie-nutrition-tracker')->value('status'));
        $this->assertFalse(config('yuktione.programmes_enabled'));
        $this->get(route('tools.index'))->assertOk()->assertSee('Water Intake Tracker');
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
            'name' => 'Water Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
