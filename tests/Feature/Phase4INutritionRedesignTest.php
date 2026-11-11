<?php

namespace Tests\Feature;

use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionMealEntry;
use App\Models\NutritionUserGoal;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\NutritionPortionCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4I — Calorie & Nutrition Tracker premium redesign (UI + calculation integrity).
 */
class Phase4INutritionRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_nutrition_pages_opt_into_tools_ds_and_drop_lavender(): void
    {
        $this->storage(true);
        $this->seedFood(['name' => 'Basmati Rice']);

        $cssPath = public_path('frontend/assets/css/yuktione-nutrition.css');
        $jsPath = public_path('frontend/assets/js/yuktione-nutrition.js');
        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);

        $css = file_get_contents($cssPath);
        $this->assertStringNotContainsString('--yk-lavender', $css);
        $this->assertStringNotContainsString('157, 129, 219', $css);
        $this->assertStringContainsString('--yk-gold', $css);
        $this->assertStringContainsString('--yk-teal', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);

        $js = file_get_contents($jsPath);
        $this->assertStringContainsString('data-nut-submit-once', $js);
        $this->assertStringContainsString('data-nut-search', $js);
        $this->assertStringNotContainsString('fetch(', $js);

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-nut', false)
            ->assertSee('yuktione-nutrition.js', false)
            ->assertSee('Log in to track meals')
            ->assertDontSee('YuktiPoints', false);

        $user = $this->member('nut4i-ds@example.com');
        $this->actingAs($user, 'user')
            ->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-nut', false)
            ->assertSee('Calorie & Nutrition Tracker')
            ->assertDontSee('YuktiPoints', false);

        $layout = file_get_contents(resource_path('views/layouts/layout.blade.php'));
        $this->assertStringContainsString("routeIs('tools.nutrition*')", $layout);
        $this->assertStringContainsString('yuktione-nutrition.js', $layout);
    }

    public function test_guest_browse_and_auth_gate_preserved(): void
    {
        $this->storage(true);
        $food = $this->seedFood(['name' => 'Cooked Rice', 'brand' => 'Home']);

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('Log in to track meals')
            ->assertSee('Search foods');

        $this->get(route('tools.nutrition.search', ['q' => 'Rice']))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
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
            'idempotency_key' => 'guest-4i',
        ])->assertForbidden();

        $this->get(route('tools.nutrition.meal'))->assertForbidden();
        $this->get(route('tools.nutrition.goals'))->assertForbidden();
        $this->get(route('tools.nutrition.insights'))->assertForbidden();
    }

    public function test_authenticated_log_goals_insights_and_golden_portion(): void
    {
        $this->storage(true);
        $user = $this->member('nut4i-flow@example.com');
        $food = $this->seedFood([
            'name' => 'Dal Tadka',
            'calories_kcal' => '116',
            'protein_g' => '9',
            'carbs_g' => '16',
            'fat_g' => '2',
            'fibre_g' => '8',
        ]);

        $calc = (new NutritionPortionCalculator)->estimate($food, '100', 'g');
        $this->assertSame('116.0000', $calc['calories_kcal']);
        $this->assertSame('9.0000', $calc['protein_g']);

        $this->actingAs($user, 'user');

        $this->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'lunch',
            'quantity' => 100,
            'unit' => 'g',
            'idempotency_key' => '4i-m1',
        ])->assertRedirect();

        $this->assertSame(1, NutritionMealEntry::query()->where('user_id', $user->id)->count());
        $this->assertSame('116.0000', (string) NutritionMealEntry::query()->first()->calories_kcal_snapshot);

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('116')
            ->assertSee('Lunch');

        $this->post(route('tools.nutrition.goals.update'), [
            'calories_kcal' => 2000,
            'protein_g' => 80,
            'carbs_g' => 220,
            'fat_g' => 60,
        ])->assertRedirect(route('tools.nutrition'));

        $goal = NutritionUserGoal::query()->where('user_id', $user->id)->first();
        $this->assertFalse($goal->track_only);
        $this->assertSame(2000, $goal->calories_kcal);

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('of 2,000')
            ->assertSee('6%');

        $this->get(route('tools.nutrition.insights'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Nutrition insights');

        $this->get(route('tools.nutrition.meal', ['meal' => 'lunch']))
            ->assertOk()
            ->assertSee('Dal Tadka')
            ->assertSee('data-nut-submit-once', false);
    }

    public function test_idempotency_ownership_catalogue_gate_and_no_reward_hooks(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));
        $this->assertNotContains('calorie-nutrition-tracker', config('yuktione_rewards.eligible_tool_completion_slugs'));

        $this->storage(true);
        $owner = $this->member('nut4i-owner@example.com');
        $other = $this->member('nut4i-other@example.com');
        $food = $this->seedFood(['name' => 'Idli', 'calories_kcal' => '140']);

        $this->actingAs($owner, 'user')->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'breakfast',
            'quantity' => 100,
            'unit' => 'g',
            'idempotency_key' => '4i-idem',
        ])->assertRedirect();

        $this->actingAs($owner, 'user')->post(route('tools.nutrition.entries.store'), [
            'food_id' => $food->id,
            'meal_type' => 'breakfast',
            'quantity' => 200,
            'unit' => 'g',
            'idempotency_key' => '4i-idem',
        ])->assertRedirect();

        $this->assertSame(1, NutritionMealEntry::query()->where('user_id', $owner->id)->count());
        $entry = NutritionMealEntry::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('140.0000', (string) $entry->calories_kcal_snapshot);

        $this->actingAs($other, 'user')
            ->put(route('tools.nutrition.entries.update', $entry), [
                'quantity' => 50,
                'unit' => 'g',
            ])->assertForbidden();
        $this->actingAs($other, 'user')
            ->delete(route('tools.nutrition.entries.destroy', $entry))
            ->assertForbidden();

        NutritionFood::query()->delete();
        $this->get(route('tools.nutrition.search'))
            ->assertOk()
            ->assertSee('No matching foods')
            ->assertSee('Food Catalogue Pending');

        $controller = file_get_contents(app_path('Http/Controllers/Frontend/NutritionTrackerController.php'));
        $this->assertStringNotContainsString('YuktiPoint', $controller);
        $this->assertStringNotContainsString('RewardEngine', $controller);
        $this->assertStringNotContainsString('completion_key', $controller);

        $this->assertFileExists(public_path('frontend/assets/css/yuktione-weight-journey.css'));
        $this->assertStringNotContainsString('data-yk-nut', file_get_contents(public_path('frontend/assets/css/yuktione-weight-journey.css')));
    }

    public function test_search_filters_and_portion_preview_contract(): void
    {
        $this->storage(true);
        $cat = NutritionFoodCategory::query()->create(['name' => 'Grains', 'slug' => 'grains', 'is_active' => true]);
        $visible = $this->seedFood([
            'name' => 'Brown Bread',
            'brand' => 'Bakery',
            'nutrition_food_category_id' => $cat->id,
            'preparation_state' => 'packaged',
            'verification_status' => 'verified',
            'calories_kcal' => '250',
            'protein_g' => '8',
            'carbs_g' => '45',
            'fat_g' => '4',
        ]);
        $this->seedFood(['name' => 'Hidden Loaf', 'is_published' => false]);
        $this->seedFood(['name' => 'Rejected Loaf', 'verification_status' => 'rejected', 'is_published' => true]);

        $this->get(route('tools.nutrition.search', ['q' => 'Bread', 'category' => $cat->id, 'state' => 'packaged']))
            ->assertOk()
            ->assertSee('Brown Bread')
            ->assertSee('Verified')
            ->assertDontSee('Hidden Loaf')
            ->assertDontSee('Rejected Loaf');

        $this->get(route('tools.nutrition.portion', [
            'food' => $visible,
            'quantity' => 50,
            'unit' => 'g',
        ]))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('125') // 50% of 250 kcal
            ->assertSee('Preview nutrition');
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
            'name' => 'Phase 4I Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
