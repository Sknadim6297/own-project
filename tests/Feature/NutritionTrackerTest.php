<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminMenu;
use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionMealEntry;
use App\Models\NutritionUserGoal;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\ToolRegistryRegistrar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NutritionTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_closed_when_unpublished_and_registry_stays_coming_soon(): void
    {
        $this->storage(false);
        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('Coming soon');

        Schema::dropIfExists('tools');
        Schema::dropIfExists('tool_categories');
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

        app(ToolRegistryRegistrar::class)->register();
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'calorie-nutrition-tracker')->value('status'));
        $this->assertFalse(config('yuktione.programmes_enabled'));
    }

    public function test_guest_welcome_search_and_logging_gate(): void
    {
        $this->storage(true);
        $food = $this->seedFood(['name' => 'Cooked Rice', 'brand' => 'Home']);

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('Log in to track meals')
            ->assertSee('Search foods');

        $this->get(route('tools.nutrition.search', ['q' => 'Rice']))
            ->assertOk()
            ->assertSee('Cooked Rice')
            ->assertSee('Unverified');

        $this->get(route('tools.nutrition.portion', $food))
            ->assertOk()
            ->assertSee('Sign in to add');

        $this->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'breakfast',
            'quantity' => 100,
            'unit' => 'g',
            'idempotency_key' => 'guest-1',
        ])->assertForbidden();
    }

    public function test_search_filters_visibility_and_empty_catalogue(): void
    {
        $this->storage(true);
        $cat = NutritionFoodCategory::query()->create(['name' => 'Grains', 'slug' => 'grains', 'is_active' => true]);
        $visible = $this->seedFood([
            'name' => 'Brown Bread',
            'brand' => 'Bakery',
            'nutrition_food_category_id' => $cat->id,
            'preparation_state' => 'packaged',
            'verification_status' => 'verified',
        ]);
        $this->seedFood([
            'name' => 'Hidden Loaf',
            'is_published' => false,
        ]);
        $this->seedFood([
            'name' => 'Rejected Loaf',
            'verification_status' => 'rejected',
            'is_published' => true,
        ]);

        $this->get(route('tools.nutrition.search', ['q' => 'Bread', 'category' => $cat->id, 'state' => 'packaged']))
            ->assertOk()
            ->assertSee('Brown Bread')
            ->assertSee('Verified')
            ->assertDontSee('Hidden Loaf')
            ->assertDontSee('Rejected Loaf');

        NutritionFood::query()->delete();
        $this->get(route('tools.nutrition.search'))
            ->assertOk()
            ->assertSee('No matching foods');
        $this->assertNotNull($visible->id);
    }

    public function test_meal_add_idempotency_snapshot_edit_delete_ownership(): void
    {
        $this->storage(true);
        $owner = $this->member('nut-owner@example.com');
        $other = $this->member('nut-other@example.com');
        $food = $this->seedFood([
            'name' => 'Dal',
            'calories_kcal' => '116',
            'protein_g' => '9',
            'carbs_g' => '16',
            'fat_g' => '2',
            'fibre_g' => '8',
        ]);
        $this->actingAs($owner, 'user');

        $this->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'lunch',
            'quantity' => 100,
            'unit' => 'g',
            'idempotency_key' => 'n1',
        ])->assertRedirect();

        $this->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'lunch',
            'quantity' => 200,
            'unit' => 'g',
            'idempotency_key' => 'n1',
        ])->assertRedirect();
        $this->assertSame(1, NutritionMealEntry::query()->count());
        $entry = NutritionMealEntry::query()->first();
        $this->assertSame('116.0000', (string) $entry->calories_kcal_snapshot);

        $this->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'lunch',
            'quantity' => 50,
            'unit' => 'g',
            'idempotency_key' => 'n2',
        ])->assertRedirect();
        $this->assertSame(2, NutritionMealEntry::query()->count());

        $food->update(['calories_kcal' => '999']);
        $this->assertSame('116.0000', (string) $entry->fresh()->calories_kcal_snapshot);

        $this->actingAs($other, 'user')
            ->put(route('tools.nutrition.entries.update', $entry), [
                'quantity' => 10,
                'unit' => 'g',
            ])->assertForbidden();

        $this->actingAs($owner, 'user')
            ->put(route('tools.nutrition.entries.update', $entry), [
                'quantity' => 200,
                'unit' => 'g',
                'meal_type' => 'dinner',
            ])->assertRedirect();
        $entry->refresh();
        $this->assertSame('dinner', $entry->meal_type);
        // Quantity edits recalculate from the current catalogue values.
        $this->assertSame('1998.0000', (string) $entry->calories_kcal_snapshot);


        $this->delete(route('tools.nutrition.entries.destroy', $entry))->assertRedirect();
        $this->assertSame(1, NutritionMealEntry::query()->count());
    }

    public function test_unsupported_unit_incomplete_nutrients_and_goals(): void
    {
        $this->storage(true);
        $user = $this->member('nut-goals@example.com');
        $food = $this->seedFood([
            'name' => 'Paneer',
            'calories_kcal' => '265',
            'protein_g' => '18',
            'carbs_g' => null,
            'fat_g' => '20',
        ]);
        $this->actingAs($user, 'user');

        $this->get(route('tools.nutrition.portion', ['food' => $food, 'quantity' => 1, 'unit' => 'cup']))
            ->assertOk()
            ->assertSee('No serving definition is available');


        $this->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'snacks',
            'quantity' => 100,
            'unit' => 'g',
            'idempotency_key' => 'inc1',
        ])->assertRedirect();

        $entry = NutritionMealEntry::query()->first();
        $this->assertTrue($entry->incomplete_nutrients);
        $this->assertNull($entry->carbs_g_snapshot);

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('incomplete nutrient')
            ->assertSee('Tracking-only mode');

        $this->post(route('tools.nutrition.goals.update'), [
            'calories_kcal' => 2000,
            'protein_g' => 80,
            'carbs_g' => 220,
            'fat_g' => 60,
        ])->assertRedirect(route('tools.nutrition'));

        $goal = NutritionUserGoal::query()->where('user_id', $user->id)->first();
        $this->assertFalse($goal->track_only);
        $this->assertSame(2000, $goal->calories_kcal);

        $this->post(route('tools.nutrition.goals.update'), [
            'track_only' => 1,
        ])->assertRedirect();
        $goal->refresh();
        $this->assertTrue($goal->track_only);
        $this->assertNull($goal->calories_kcal);

        $this->get(route('tools.nutrition.insights'))->assertOk()->assertSee('Nutrition insights');
        $this->get(route('tools.nutrition.meal', ['meal' => 'snacks']))->assertOk()->assertSee('Paneer');
    }

    public function test_admin_food_crud_authorization_and_null_nutrients(): void
    {
        $this->adminStorage();
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
            $table->foreignId('nutrition_food_category_id')->nullable()->constrained('nutrition_food_categories')->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('variant')->nullable();
            $table->string('preparation_state', 20)->default('unknown');
            $table->string('reference_basis', 20)->default('per_100g');
            $table->decimal('calories_kcal', 10, 4)->nullable();
            $table->decimal('protein_g', 10, 4)->nullable();
            $table->decimal('carbs_g', 10, 4)->nullable();
            $table->decimal('fat_g', 10, 4)->nullable();
            $table->decimal('fibre_g', 10, 4)->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->string('verification_status', 20)->default('unverified');
            $table->boolean('is_published')->default(false);
            $table->string('import_batch', 80)->nullable();
            $table->string('import_row_ref', 80)->nullable();
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
        (require database_path('migrations/2026_11_04_000100_create_nutrition_import_review_tables.php'))->up();

        $this->get(route('admin.nutrition-foods.index'))->assertRedirect();


        $blocked = Admin::query()->create([
            'name' => 'Blocked',
            'email' => 'blocked-nut@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($blocked, 'admin')
            ->get(route('admin.nutrition-foods.index'))
            ->assertForbidden();

        $admin = Admin::query()->create([
            'name' => 'Super',
            'email' => 'super-nut@example.com',
            'password' => Hash::make('secret'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.nutrition-foods.store'), [
                'name' => 'Idli',
                'preparation_state' => 'cooked',
                'reference_basis' => 'per_100g',
                'calories_kcal' => '140',
                'protein_g' => '',
                'verification_status' => 'unverified',
                'is_published' => 1,
                'serving_label' => 'piece',
                'serving_quantity' => 1,
                'serving_unit' => 'piece',
                'serving_grams' => 30,
            ])->assertRedirect();

        $food = NutritionFood::query()->first();
        $this->assertSame('Idli', $food->name);
        $this->assertNull($food->protein_g);
        $this->assertFalse($food->is_published); // unpublished until verified
        $this->assertSame('unverified', $food->verification_status);
        $this->assertSame(1, $food->servings()->count());

        $this->put(route('admin.nutrition-foods.update', $food), [
            'name' => 'Idli Soft',
            'preparation_state' => 'cooked',
            'reference_basis' => 'per_100g',
            'calories_kcal' => '142',
            'protein_g' => '4.5',
            'verification_status' => 'verified',
            'is_published' => 1,
        ])->assertRedirect();
        $this->assertSame('Idli Soft', $food->fresh()->name);
        $this->assertSame('verified', $food->fresh()->verification_status);
        $this->assertTrue($food->fresh()->is_published);

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
            'customer_profiles',
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

        (require database_path('migrations/2026_11_03_220000_create_nutrition_tracker_tables.php'))->up();

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

    private function adminStorage(): void
    {
        foreach (['admin_menu_permissions', 'admin_menus', 'admins', 'bookings', 'job_applications', 'notifications'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->string('route_name')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('admin_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('admin_menu_id');
            $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('plan_type')->nullable();
        });
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        AdminMenu::query()->create([
            'name' => 'tools_management',
            'display_name' => 'Tools Management',
            'order' => 1,
        ]);
    }

    private function seedFood(array $attrs = []): NutritionFood
    {
        return NutritionFood::query()->create(array_merge([
            'name' => 'Sample Food',
            'preparation_state' => 'cooked',
            'reference_basis' => 'per_100g',
            'calories_kcal' => '100',
            'protein_g' => '5',
            'carbs_g' => '20',
            'fat_g' => '2',
            'fibre_g' => '1',
            'verification_status' => 'unverified',
            'is_published' => true,
        ], $attrs));
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Nutrition Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
