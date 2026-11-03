<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Models\WeightGoal;
use App\Models\WeightMeasurement;
use App\Services\WeightJourneyService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WeightJourneyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_guest_intro_and_auth_gate(): void
    {
        $this->storage(true);
        $this->get(route('tools.weight-journey'))
            ->assertOk()
            ->assertSee('Log in to start')
            ->assertDontSee('Log weight');

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 70,
            'unit' => 'kg',
            'idempotency_key' => 'g1',
        ])->assertForbidden();
    }

    public function test_goal_track_only_history_and_archive(): void
    {
        $this->storage(true);
        $user = $this->member('wj@example.com');
        $this->actingAs($user, 'user');

        $this->post(route('tools.weight-journey.goal.store'), [
            'direction' => 'track',
            'starting_weight' => 80,
            'display_unit' => 'kg',
        ])->assertRedirect();

        $this->assertSame(1, WeightGoal::query()->where('status', 'active')->count());

        $this->post(route('tools.weight-journey.goal.store'), [
            'direction' => 'lose',
            'starting_weight' => 80,
            'target_weight' => 72,
            'display_unit' => 'kg',
        ])->assertRedirect();

        $this->assertSame(1, WeightGoal::query()->where('status', 'active')->count());
        $this->assertSame(1, WeightGoal::query()->where('status', 'archived')->count());
        $this->assertSame('track', WeightGoal::query()->where('status', 'archived')->first()->direction);

        $this->post(route('tools.weight-journey.goal.archive'))->assertRedirect();
        $this->assertSame(0, WeightGoal::query()->where('status', 'active')->count());
    }

    public function test_measurements_idempotency_same_day_edit_delete_ownership(): void
    {
        $this->storage(true);
        $owner = $this->member('wj-owner@example.com');
        $other = $this->member('wj-other@example.com');
        $this->actingAs($owner, 'user');

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 70.5,
            'unit' => 'kg',
            'idempotency_key' => 'm1',
            'note' => 'morning',
        ])->assertRedirect();

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 71,
            'unit' => 'kg',
            'idempotency_key' => 'm1',
        ])->assertRedirect();
        $this->assertSame(1, WeightMeasurement::query()->count());
        $this->assertSame('70.500', (string) WeightMeasurement::query()->first()->weight_kg);

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 70.8,
            'unit' => 'kg',
            'idempotency_key' => 'm2',
        ])->assertRedirect();
        $this->assertSame(2, WeightMeasurement::query()->count());

        $entry = WeightMeasurement::query()->where('idempotency_key', 'm1')->first();
        $this->actingAs($other, 'user')
            ->put(route('tools.weight-journey.measurements.update', $entry), [
                'weight' => 60,
                'unit' => 'kg',
            ])->assertForbidden();

        $this->actingAs($owner, 'user')
            ->put(route('tools.weight-journey.measurements.update', $entry), [
                'weight' => 70.25,
                'unit' => 'kg',
            ])->assertRedirect();
        $this->assertSame('70.250', (string) $entry->fresh()->weight_kg);

        $this->delete(route('tools.weight-journey.measurements.destroy', $entry))->assertRedirect();
        $this->assertSame(1, WeightMeasurement::query()->count());
    }

    public function test_validation_future_and_chart_aggregation(): void
    {
        $this->storage(true);
        $user = $this->member('wj-chart@example.com');
        $this->actingAs($user, 'user');
        $svc = app(WeightJourneyService::class);

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 10,
            'unit' => 'kg',
            'idempotency_key' => 'bad',
        ])->assertSessionHasErrors();

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 70,
            'unit' => 'kg',
            'recorded_at' => now('Asia/Kolkata')->addDay()->format('Y-m-d\TH:i'),
            'idempotency_key' => 'future',
        ])->assertSessionHasErrors();

        $day = CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        $svc->addMeasurement($user->id, '70.000', 'c1', $day->setTime(8, 0));
        $svc->addMeasurement($user->id, '70.500', 'c2', $day->setTime(20, 0));
        $chart = $svc->chart($user->id, 'week', 'kg');
        $todayPoints = array_values(array_filter($chart['points'], fn ($p) => $p['date'] === $day->toDateString()));
        $this->assertCount(1, $todayPoints);
        $this->assertSame('70.500', $todayPoints[0]['weight_kg']);
        $this->assertSame('latest_per_local_day', $chart['aggregation']);

        $this->get(route('tools.weight-journey.progress', ['period' => 'month']))
            ->assertOk()
            ->assertSee('Weight progress');
    }

    public function test_progress_no_division_by_zero_for_equal_start_target(): void
    {
        $this->storage(true);
        $user = $this->member('wj-prog@example.com');
        $svc = app(WeightJourneyService::class);
        $svc->createOrReplaceGoal($user->id, [
            'direction' => 'maintain',
            'starting_weight' => 70,
            'display_unit' => 'kg',
        ]);
        $svc->addMeasurement($user->id, '70.000', 'p1');
        $summary = $svc->journeySummary($user->id);
        $this->assertFalse($summary['progress']['meaningful']);
    }

    public function test_midnight_boundary(): void
    {
        $this->storage(true);
        $user = $this->member('wj-mid@example.com');
        $svc = app(WeightJourneyService::class);
        $justAfter = CarbonImmutable::parse('2026-11-02 18:30:00', 'UTC');
        $justBefore = CarbonImmutable::parse('2026-11-02 18:29:59', 'UTC');
        $svc->addMeasurement($user->id, '70.000', 'mid1', $justAfter);
        $svc->addMeasurement($user->id, '71.000', 'mid2', $justBefore);
        $chart = $svc->chart($user->id, 'year', 'kg');
        $dates = array_column($chart['points'], 'date');
        $this->assertContains('2026-11-03', $dates);
        $this->assertContains('2026-11-02', $dates);
    }

    public function test_publish_leaves_calorie_coming_soon(): void
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
        (require database_path('migrations/2026_11_03_210000_publish_weight_journey.php'))->up();

        $this->assertTrue(Tool::query()->where('slug', 'weight-journey')->first()->canLaunch());
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'calorie-nutrition-tracker')->value('status'));
        $this->assertFalse(config('yuktione.programmes_enabled'));
        $this->get(route('tools.index'))->assertOk()->assertSee('Weight Journey');
    }

    private function storage(bool $open): void
    {
        foreach (['weight_measurements', 'weight_goals', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
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
        Schema::create('weight_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('direction', 20);
            $table->decimal('starting_weight_kg', 8, 3);
            $table->decimal('target_weight_kg', 8, 3)->nullable();
            $table->date('target_date')->nullable();
            $table->string('display_unit', 8)->default('kg');
            $table->string('status', 20)->default('active');
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
        });
        Schema::create('weight_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('weight_kg', 8, 3);
            $table->timestamp('recorded_at');
            $table->string('note', 240)->nullable();
            $table->string('display_unit', 8)->default('kg');
            $table->string('idempotency_key', 64);
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
        });

        $category = ToolCategory::query()->create(['name' => 'Nutrition', 'slug' => 'nutrition']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Weight Journey',
            'slug' => 'weight-journey',
            'module_key' => 'nutrition',
            'description' => 'Weight tracking.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.weight-journey' : null,
            'is_implemented' => $open,
            'display_order' => 3,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Weight Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
