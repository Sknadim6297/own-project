<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Models\WaterIntakeEntry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyWaterIntakeApiTest extends TestCase
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

    public function test_guests_cannot_use_water_intake_api(): void
    {
        $this->getJson('/api/v1/my/water-intake/dashboard')->assertUnauthorized();
        $this->postJson('/api/v1/my/water-intake/entries', [])->assertUnauthorized();
    }

    public function test_dashboard_and_quick_adds_match_the_website_catalogue(): void
    {
        Sanctum::actingAs($this->member('water-dash@example.com'));

        $this->getJson('/api/v1/my/water-intake/dashboard')
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('goal_ml', 2000)
            ->assertJsonPath('total_ml', 0)
            ->assertJsonPath('remaining_ml', 2000)
            ->assertJsonPath('percent', 0)
            ->assertJsonCount(4, 'quick_adds')
            ->assertJsonPath('quick_adds.0.ml', 150)
            ->assertJsonPath('quick_adds.1.ml', 250)
            ->assertJsonPath('quick_adds.2.ml', 500)
            ->assertJsonPath('quick_adds.3.ml', 750)
            ->assertJsonPath('timezone', 'Asia/Kolkata');
    }

    public function test_entries_are_idempotent_owned_and_deletable(): void
    {
        $owner = $this->member('water-owner@example.com');
        $other = $this->member('water-other@example.com');
        Sanctum::actingAs($owner);
        $key = (string) Str::uuid();

        $this->postJson('/api/v1/my/water-intake/entries', [
            'amount_ml' => 250,
            'idempotency_key' => $key,
        ])
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('dashboard.total_ml', 250)
            ->assertJsonPath('dashboard.percent', 13);

        $this->postJson('/api/v1/my/water-intake/entries', [
            'amount_ml' => 250,
            'idempotency_key' => $key,
        ])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('dashboard.total_ml', 250);

        $this->assertSame(1, WaterIntakeEntry::query()->count());
        $entryId = (int) WaterIntakeEntry::query()->value('id');

        Sanctum::actingAs($other);
        $this->deleteJson('/api/v1/my/water-intake/entries/'.$entryId)->assertForbidden();
        $this->assertSame(1, WaterIntakeEntry::query()->count());

        Sanctum::actingAs($owner);
        $this->deleteJson('/api/v1/my/water-intake/entries/'.$entryId)
            ->assertOk()
            ->assertJsonPath('deleted', true)
            ->assertJsonPath('dashboard.total_ml', 0);
        $this->assertSame(0, WaterIntakeEntry::query()->count());
    }

    public function test_goal_update_and_week_summary_use_real_totals(): void
    {
        Sanctum::actingAs($this->member('water-goal@example.com'));

        $this->patchJson('/api/v1/my/water-intake/settings', [
            'daily_goal_ml' => 1500,
            'display_unit' => 'ml',
        ])
            ->assertOk()
            ->assertJsonPath('settings.daily_goal_ml', 1500)
            ->assertJsonPath('dashboard.goal_ml', 1500);

        $this->postJson('/api/v1/my/water-intake/entries', [
            'amount_ml' => 500,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->getJson('/api/v1/my/water-intake/week')
            ->assertOk()
            ->assertJsonCount(7, 'days')
            ->assertJsonPath('average_ml', 500);

        $today = collect($this->getJson('/api/v1/my/water-intake/week')->json('days'))
            ->firstWhere('is_today', true);
        $this->assertSame(500, $today['total_ml']);
        $this->assertSame(1500, $today['goal_ml']);
        $this->assertTrue($today['has_entries']);
    }

    public function test_validation_rejects_out_of_range_amounts_and_closed_tool(): void
    {
        Sanctum::actingAs($this->member('water-rules@example.com'));

        $this->postJson('/api/v1/my/water-intake/entries', [
            'amount_ml' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $this->postJson('/api/v1/my/water-intake/entries', [
            'amount_ml' => 5001,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $this->patchJson('/api/v1/my/water-intake/settings', [
            'daily_goal_ml' => 100,
            'display_unit' => 'ml',
        ])->assertUnprocessable();

        Tool::query()->where('slug', 'water-intake-tracker')->update([
            'status' => 'inactive',
            'route_name' => null,
            'is_implemented' => false,
        ]);

        $this->getJson('/api/v1/my/water-intake/dashboard')->assertForbidden();
        $this->postJson('/api/v1/my/water-intake/entries', [
            'amount_ml' => 250,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertForbidden();
        $this->assertSame(0, WaterIntakeEntry::query()->count());
    }

    private function storage(bool $open): void
    {
        foreach (['water_intake_entries', 'water_intake_settings', 'tools', 'tool_categories', 'users'] as $table) {
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
            'name' => 'Water API Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
