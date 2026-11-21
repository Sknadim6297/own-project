<?php

namespace Tests\Feature;

use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionMealEntry;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyNutritionApiTest extends TestCase
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

    public function test_guests_cannot_use_nutrition_api(): void
    {
        $this->getJson('/api/v1/my/nutrition/dashboard')->assertUnauthorized();
        $this->postJson('/api/v1/my/nutrition/entries', [])->assertUnauthorized();
    }

    public function test_dashboard_search_portion_and_idempotent_log(): void
    {
        Sanctum::actingAs($this->member('nut-flow@example.com'));
        $food = $this->seedFood();

        $this->getJson('/api/v1/my/nutrition/dashboard')
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('goals.track_only', true)
            ->assertJsonPath('totals.calories_kcal', null)
            ->assertJsonPath('progress.meaningful', false);

        $this->getJson('/api/v1/my/nutrition/search?q=oats')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('foods.0.name', 'Rolled Oats');

        $this->getJson('/api/v1/my/nutrition/foods/'.$food->id.'/portion?quantity=50&unit=g')
            ->assertOk()
            ->assertJsonPath('preview.calories_kcal', '82.5000');

        $this->postJson('/api/v1/my/nutrition/goals', [
            'track_only' => false,
            'calories_kcal' => 2000,
            'protein_g' => 80,
        ])
            ->assertOk()
            ->assertJsonPath('goals.track_only', false)
            ->assertJsonPath('goals.calories_kcal', 2000);

        $key = (string) Str::uuid();
        $this->postJson('/api/v1/my/nutrition/entries', [
            'food_id' => $food->id,
            'meal_type' => 'breakfast',
            'quantity' => 50,
            'unit' => 'g',
            'idempotency_key' => $key,
        ])
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('entry.calories_kcal', '82.5000')
            ->assertJsonPath('dashboard.totals.calories_kcal', '82.5000')
            ->assertJsonPath('dashboard.progress.percent', 4);

        $this->postJson('/api/v1/my/nutrition/entries', [
            'food_id' => $food->id,
            'meal_type' => 'breakfast',
            'quantity' => 50,
            'unit' => 'g',
            'idempotency_key' => $key,
        ])
            ->assertOk()
            ->assertJsonPath('created', false);

        $this->assertSame(1, NutritionMealEntry::query()->count());

        $this->getJson('/api/v1/my/nutrition/insights')
            ->assertOk()
            ->assertJsonCount(7, 'days');
    }

    public function test_entries_are_owned_updatable_and_deletable(): void
    {
        $owner = $this->member('nut-owner@example.com');
        $other = $this->member('nut-other@example.com');
        $food = $this->seedFood();
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/my/nutrition/entries', [
            'food_id' => $food->id,
            'meal_type' => 'lunch',
            'quantity' => 100,
            'unit' => 'g',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        $id = (int) NutritionMealEntry::query()->value('id');

        Sanctum::actingAs($other);
        $this->deleteJson('/api/v1/my/nutrition/entries/'.$id)->assertForbidden();
        $this->putJson('/api/v1/my/nutrition/entries/'.$id, [
            'quantity' => 200,
            'unit' => 'g',
        ])->assertForbidden();
        $this->assertSame(1, NutritionMealEntry::query()->count());

        Sanctum::actingAs($owner);
        $this->putJson('/api/v1/my/nutrition/entries/'.$id, [
            'quantity' => 200,
            'unit' => 'g',
            'meal_type' => 'dinner',
        ])
            ->assertOk()
            ->assertJsonPath('entry.calories_kcal', '330.0000')
            ->assertJsonPath('entry.meal_type', 'dinner');

        $this->deleteJson('/api/v1/my/nutrition/entries/'.$id)
            ->assertOk()
            ->assertJsonPath('deleted', true)
            ->assertJsonPath('dashboard.totals.calories_kcal', null);
        $this->assertSame(0, NutritionMealEntry::query()->count());
    }

    public function test_validation_and_closed_tool(): void
    {
        Sanctum::actingAs($this->member('nut-rules@example.com'));
        $food = $this->seedFood();

        $this->postJson('/api/v1/my/nutrition/entries', [
            'food_id' => $food->id,
            'meal_type' => 'breakfast',
            'quantity' => 0,
            'unit' => 'g',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $this->postJson('/api/v1/my/nutrition/goals', [
            'track_only' => false,
            'calories_kcal' => 100,
        ])->assertUnprocessable();

        Tool::query()->where('slug', 'calorie-nutrition-tracker')->update([
            'status' => 'inactive',
            'route_name' => null,
            'is_implemented' => false,
        ]);

        $this->getJson('/api/v1/my/nutrition/dashboard')->assertForbidden();
        $this->assertSame(0, NutritionMealEntry::query()->count());
    }

    private function seedFood(): NutritionFood
    {
        $category = NutritionFoodCategory::query()->create([
            'name' => 'Grains',
            'slug' => 'grains',
            'display_order' => 1,
            'is_active' => true,
        ]);

        return NutritionFood::query()->create([
            'nutrition_food_category_id' => $category->id,
            'name' => 'Rolled Oats',
            'normalized_name' => 'rolled oats',
            'brand' => null,
            'variant' => null,
            'preparation_state' => 'raw',
            'reference_basis' => 'per_100g',
            'calories_kcal' => 165,
            'protein_g' => 13,
            'carbs_g' => 27,
            'fat_g' => 3,
            'fibre_g' => 4,
            'verification_status' => 'verified',
            'is_published' => true,
        ]);
    }

    private function storage(bool $open): void
    {
        foreach ([
            'nutrition_meal_entries',
            'nutrition_user_goals',
            'nutrition_food_servings',
            'nutrition_foods',
            'nutrition_food_categories',
            'tools',
            'tool_categories',
            'users',
        ] as $table) {
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
        Schema::create('nutrition_food_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('nutrition_foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_food_category_id')->nullable();
            $table->string('name');
            $table->string('normalized_name')->nullable();
            $table->string('brand')->nullable();
            $table->string('variant')->nullable();
            $table->string('preparation_state', 20)->default('unknown');
            $table->string('reference_basis', 20)->default('per_100g');
            $table->decimal('calories_kcal', 10, 4)->nullable();
            $table->decimal('protein_g', 10, 4)->nullable();
            $table->decimal('carbs_g', 10, 4)->nullable();
            $table->decimal('fat_g', 10, 4)->nullable();
            $table->decimal('fibre_g', 10, 4)->nullable();
            $table->string('verification_status', 20)->default('unverified');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
        Schema::create('nutrition_food_servings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_food_id')->constrained('nutrition_foods')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('quantity', 10, 4);
            $table->string('unit', 40);
            $table->decimal('grams_equivalent', 10, 4)->nullable();
            $table->timestamps();
        });
        Schema::create('nutrition_user_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('track_only')->default(true);
            $table->unsignedInteger('calories_kcal')->nullable();
            $table->decimal('protein_g', 10, 2)->nullable();
            $table->decimal('carbs_g', 10, 2)->nullable();
            $table->decimal('fat_g', 10, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('nutrition_meal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('nutrition_food_id')->nullable();
            $table->string('meal_type', 20);
            $table->decimal('quantity', 10, 4);
            $table->string('unit', 40);
            $table->decimal('portion_grams', 10, 4)->nullable();
            $table->timestamp('consumed_at');
            $table->string('food_name_snapshot');
            $table->string('brand_snapshot')->nullable();
            $table->decimal('calories_kcal_snapshot', 10, 4)->nullable();
            $table->decimal('protein_g_snapshot', 10, 4)->nullable();
            $table->decimal('carbs_g_snapshot', 10, 4)->nullable();
            $table->decimal('fat_g_snapshot', 10, 4)->nullable();
            $table->decimal('fibre_g_snapshot', 10, 4)->nullable();
            $table->boolean('incomplete_nutrients')->default(false);
            $table->string('idempotency_key', 64);
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
        });

        $category = ToolCategory::query()->create(['name' => 'Nutrition', 'slug' => 'nutrition']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Calorie & Nutrition Tracker',
            'slug' => 'calorie-nutrition-tracker',
            'module_key' => 'nutrition',
            'description' => 'Meal logging.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.nutrition' : null,
            'is_implemented' => $open,
            'display_order' => 4,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Nutrition API Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
