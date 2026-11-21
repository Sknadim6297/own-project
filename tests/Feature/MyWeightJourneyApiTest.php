<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Models\WeightMeasurement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyWeightJourneyApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureApiActiveSession::class);
        $this->storage(true);
        Carbon::setTestNow(Carbon::parse('2026-03-15 06:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guests_cannot_use_weight_journey_api(): void
    {
        $this->getJson('/api/v1/my/weight-journey/summary')->assertUnauthorized();
        $this->postJson('/api/v1/my/weight-journey/measurements', [])->assertUnauthorized();
    }

    public function test_summary_goal_and_measurement_flow_is_idempotent(): void
    {
        Sanctum::actingAs($this->member('wj-flow@example.com'));

        $this->getJson('/api/v1/my/weight-journey/summary')
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('latest', null)
            ->assertJsonCount(4, 'directions');

        $this->postJson('/api/v1/my/weight-journey/goal', [
            'direction' => 'lose',
            'starting_weight' => 80,
            'target_weight' => 72,
            'display_unit' => 'kg',
        ])
            ->assertOk()
            ->assertJsonPath('goal.direction', 'lose')
            ->assertJsonPath('goal.target_weight_kg', '72.000');

        $key = (string) Str::uuid();
        $this->postJson('/api/v1/my/weight-journey/measurements', [
            'weight' => 78.5,
            'unit' => 'kg',
            'idempotency_key' => $key,
        ])
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('summary.latest', '78.500');

        $this->postJson('/api/v1/my/weight-journey/measurements', [
            'weight' => 78.5,
            'unit' => 'kg',
            'idempotency_key' => $key,
        ])
            ->assertOk()
            ->assertJsonPath('created', false);

        $this->assertSame(1, WeightMeasurement::query()->count());

        $this->getJson('/api/v1/my/weight-journey/summary')
            ->assertOk()
            ->assertJsonPath('latest', '78.500')
            ->assertJsonPath('progress.meaningful', true)
            ->assertJsonPath('progress.percent', 19);

        $this->getJson('/api/v1/my/weight-journey/chart?period=month')
            ->assertOk()
            ->assertJsonCount(1, 'chart.points');
    }

    public function test_measurements_are_owned_and_deletable(): void
    {
        $owner = $this->member('wj-owner@example.com');
        $other = $this->member('wj-other@example.com');
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/my/weight-journey/measurements', [
            'weight' => 70,
            'unit' => 'kg',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        $id = (int) WeightMeasurement::query()->value('id');

        Sanctum::actingAs($other);
        $this->deleteJson('/api/v1/my/weight-journey/measurements/'.$id)->assertForbidden();
        $this->assertSame(1, WeightMeasurement::query()->count());

        Sanctum::actingAs($owner);
        $this->deleteJson('/api/v1/my/weight-journey/measurements/'.$id)
            ->assertOk()
            ->assertJsonPath('deleted', true)
            ->assertJsonPath('summary.latest', null);
        $this->assertSame(0, WeightMeasurement::query()->count());
    }

    public function test_validation_and_closed_tool(): void
    {
        Sanctum::actingAs($this->member('wj-rules@example.com'));

        $this->postJson('/api/v1/my/weight-journey/measurements', [
            'weight' => 10,
            'unit' => 'kg',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $this->postJson('/api/v1/my/weight-journey/goal', [
            'direction' => 'lose',
            'starting_weight' => 80,
            'display_unit' => 'kg',
        ])->assertUnprocessable();

        Tool::query()->where('slug', 'weight-journey')->update([
            'status' => 'inactive',
            'route_name' => null,
            'is_implemented' => false,
        ]);

        $this->getJson('/api/v1/my/weight-journey/summary')->assertForbidden();
        $this->assertSame(0, WeightMeasurement::query()->count());
    }

    private function storage(bool $open): void
    {
        foreach (['weight_measurements', 'weight_goals', 'tools', 'tool_categories', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
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
            'name' => 'Weight API Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
