<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Models\WeightGoal;
use App\Models\WeightMeasurement;
use App\Services\WeightJourneyService;
use App\Support\WeightJourney\WeightJourneyCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4H — Weight Journey premium redesign (UI + tracking integrity).
 */
class Phase4HWeightJourneyRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_weight_pages_opt_into_tools_ds_and_drop_lavender(): void
    {
        $this->storage(true);

        $cssPath = public_path('frontend/assets/css/yuktione-weight-journey.css');
        $jsPath = public_path('frontend/assets/js/yuktione-weight-journey.js');
        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);

        $css = file_get_contents($cssPath);
        $this->assertStringNotContainsString('--yk-lavender', $css);
        $this->assertStringNotContainsString('157, 129, 219', $css);
        $this->assertStringContainsString('--yk-gold', $css);
        $this->assertStringContainsString('--yk-teal', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('yk-wj__chart', $css);

        $js = file_get_contents($jsPath);
        $this->assertStringContainsString('data-wj-submit-once', $js);
        $this->assertStringContainsString('data-submitting', $js);
        $this->assertStringNotContainsString('fetch(', $js);

        $this->get(route('tools.weight-journey'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-wj', false)
            ->assertSee('yuktione-weight-journey.js', false)
            ->assertSee('Log in to start')
            ->assertDontSee('Log weight')
            ->assertDontSee('YuktiPoints', false);

        $user = $this->member('wj4h-ds@example.com');
        $this->actingAs($user, 'user')
            ->get(route('tools.weight-journey'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-wj', false)
            ->assertSee('Weight Journey')
            ->assertSee('Log weight')
            ->assertSee('No measurements in this period yet', false)
            ->assertDontSee('YuktiPoints', false);

        $layout = file_get_contents(resource_path('views/layouts/layout.blade.php'));
        $this->assertStringContainsString("routeIs('tools.weight-journey*')", $layout);
        $this->assertStringContainsString('yuktione-weight-journey.js', $layout);
    }

    public function test_guest_welcome_and_auth_gate_preserved(): void
    {
        $this->storage(true);

        $this->get(route('tools.weight-journey'))
            ->assertOk()
            ->assertSee('Weight Journey')
            ->assertSee('Log in to start')
            ->assertSee(route('login'), false);

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 70,
            'unit' => 'kg',
            'idempotency_key' => 'guest-4h-1',
        ])->assertForbidden();

        $this->get(route('tools.weight-journey.log'))->assertForbidden();
        $this->get(route('tools.weight-journey.goal'))->assertForbidden();
        $this->get(route('tools.weight-journey.progress'))->assertForbidden();
    }

    public function test_authenticated_log_goal_progress_and_chart_integrity(): void
    {
        $this->storage(true);
        $user = $this->member('wj4h-flow@example.com');
        $this->actingAs($user, 'user');
        $svc = app(WeightJourneyService::class);

        $this->post(route('tools.weight-journey.goal.store'), [
            'direction' => 'lose',
            'starting_weight' => 80,
            'target_weight' => 72,
            'display_unit' => 'kg',
        ])->assertRedirect(route('tools.weight-journey'));

        $goal = WeightGoal::query()->where('user_id', $user->id)->where('status', 'active')->first();
        $this->assertNotNull($goal);
        $this->assertSame('lose', $goal->direction);

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 78.5,
            'unit' => 'kg',
            'idempotency_key' => '4h-m1',
            'note' => 'morning',
        ])->assertRedirect(route('tools.weight-journey.log'));

        $this->assertSame(1, WeightMeasurement::query()->where('user_id', $user->id)->count());

        $this->get(route('tools.weight-journey'))
            ->assertOk()
            ->assertSee('78.5 kg')
            ->assertSee('Lose weight')
            ->assertSee('Weight trend chart', false)
            ->assertSee('data-wj-chart', false);

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 10,
            'unit' => 'kg',
            'idempotency_key' => '4h-bad',
        ])->assertSessionHasErrors();

        $this->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 78,
            'unit' => 'kg',
            'recorded_at' => now('Asia/Kolkata')->addDay()->format('Y-m-d\TH:i'),
            'idempotency_key' => '4h-future',
        ])->assertSessionHasErrors();

        $day = CarbonImmutable::now('Asia/Kolkata')->subDay()->startOfDay();
        $svc->addMeasurement($user->id, '78.000', '4h-c1', $day->setTime(8, 0));
        $svc->addMeasurement($user->id, '77.500', '4h-c2', $day->setTime(20, 0));
        $chart = $svc->chart($user->id, 'week', 'kg');
        $dayPoints = array_values(array_filter($chart['points'], fn ($p) => $p['date'] === $day->toDateString()));
        $this->assertCount(1, $dayPoints);
        $this->assertSame('77.500', $dayPoints[0]['weight_kg']);
        $this->assertSame(WeightJourneyCatalog::CHART_AGGREGATION, $chart['aggregation']);

        $this->get(route('tools.weight-journey.progress', ['period' => 'week']))
            ->assertOk()
            ->assertSee('Weight progress')
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('wj-chart-title', false)
            ->assertSee('77.5 kg');

        $summary = $svc->journeySummary($user->id);
        $this->assertTrue($summary['progress']['meaningful']);
        $this->assertIsInt($summary['progress']['percent']);
    }

    public function test_idempotency_ownership_boundaries_and_no_reward_hooks(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));
        $this->assertNotContains('weight-journey', config('yuktione_rewards.eligible_tool_completion_slugs'));

        $this->storage(true);
        $owner = $this->member('wj4h-owner@example.com');
        $other = $this->member('wj4h-other@example.com');
        $svc = app(WeightJourneyService::class);

        $this->actingAs($owner, 'user')->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 70.5,
            'unit' => 'kg',
            'idempotency_key' => '4h-idem-1',
        ])->assertRedirect();

        $this->actingAs($owner, 'user')->post(route('tools.weight-journey.measurements.store'), [
            'weight' => 71,
            'unit' => 'kg',
            'idempotency_key' => '4h-idem-1',
        ])->assertRedirect();

        $this->assertSame(1, WeightMeasurement::query()->where('user_id', $owner->id)->count());
        $this->assertSame('70.500', (string) WeightMeasurement::query()->where('user_id', $owner->id)->value('weight_kg'));

        $entry = WeightMeasurement::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($other, 'user')
            ->put(route('tools.weight-journey.measurements.update', $entry), [
                'weight' => 60,
                'unit' => 'kg',
            ])->assertForbidden();
        $this->actingAs($other, 'user')
            ->delete(route('tools.weight-journey.measurements.destroy', $entry))
            ->assertForbidden();

        $justAfter = CarbonImmutable::parse('2026-11-02 18:30:00', 'UTC');
        $justBefore = CarbonImmutable::parse('2026-11-02 18:29:59', 'UTC');
        $svc->addMeasurement($owner->id, '70.000', '4h-mid-1', $justAfter);
        $svc->addMeasurement($owner->id, '71.000', '4h-mid-2', $justBefore);
        $chart = $svc->chart($owner->id, 'year', 'kg');
        $dates = array_column($chart['points'], 'date');
        $this->assertContains('2026-11-03', $dates);
        $this->assertContains('2026-11-02', $dates);

        $controller = file_get_contents(app_path('Http/Controllers/Frontend/WeightJourneyController.php'));
        $this->assertStringNotContainsString('YuktiPoint', $controller);
        $this->assertStringNotContainsString('RewardEngine', $controller);
        $this->assertStringNotContainsString('completion_key', $controller);

        $this->assertFileExists(public_path('frontend/assets/css/yuktione-water-intake.css'));
        $this->assertStringNotContainsString('data-yk-wj', file_get_contents(public_path('frontend/assets/css/yuktione-water-intake.css')));
    }

    public function test_maintain_and_track_goals_skip_percentage(): void
    {
        $this->storage(true);
        $user = $this->member('wj4h-maintain@example.com');
        $svc = app(WeightJourneyService::class);

        $svc->createOrReplaceGoal($user->id, [
            'direction' => 'maintain',
            'starting_weight' => 70,
            'display_unit' => 'kg',
        ]);
        $svc->addMeasurement($user->id, '70.000', '4h-m-maintain');
        $summary = $svc->journeySummary($user->id);
        $this->assertFalse($summary['progress']['meaningful']);
        $this->assertSame('maintain', $summary['progress']['reason']);

        $this->actingAs($user, 'user')
            ->get(route('tools.weight-journey'))
            ->assertOk()
            ->assertSee('Maintain / track goals do not use a percentage-complete indicator');
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
            'name' => 'Phase 4H Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
