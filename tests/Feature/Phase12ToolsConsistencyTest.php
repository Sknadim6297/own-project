<?php

namespace Tests\Feature;

use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\ExpertsDiscovery;
use App\Services\ToolRegistryRegistrar;
use App\Services\ToolsCatalog;
use App\Support\Tools\ToolsSection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase12ToolsConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('brand')->nullable();
            $table->string('variant')->nullable();
            $table->string('preparation_state', 20)->default('unknown');
            $table->string('reference_basis', 20)->default('per_100g');
            $table->decimal('calories_kcal', 10, 4)->nullable();
            $table->decimal('protein_g', 10, 4)->nullable();
            $table->decimal('carbs_g', 10, 4)->nullable();
            $table->decimal('fat_g', 10, 4)->nullable();
            $table->string('verification_status', 32)->default('unverified');
            $table->string('nutrient_completeness', 32)->default('incomplete');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        app(ToolRegistryRegistrar::class)->register();
        $this->publishAllTools();
    }

    public function test_nutrition_catalogue_pending_clears_when_food_published(): void
    {
        $request = Request::create(route('tools.index'), 'GET');
        $browse = app(ToolsCatalog::class)->browse($request);
        $card = collect($browse['items']->items())->firstWhere('slug', 'calorie-nutrition-tracker');
        $this->assertNotNull($card);
        $this->assertSame('Food Catalogue Pending', $card['availability']);
        $this->assertSame('pending', $card['availability_state']);
        $this->assertSame('View status', $card['cta']);

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Food Catalogue Pending')
            ->assertSee('View status');

        $category = NutritionFoodCategory::query()->create([
            'name' => 'Staples',
            'slug' => 'staples',
            'display_order' => 1,
            'is_active' => true,
        ]);
        NutritionFood::query()->create([
            'nutrition_food_category_id' => $category->id,
            'name' => 'Verified Rice',
            'preparation_state' => 'cooked',
            'reference_basis' => 'per_100g',
            'calories_kcal' => 130,
            'protein_g' => 2.7,
            'carbs_g' => 28,
            'fat_g' => 0.3,
            'verification_status' => 'verified',
            'nutrient_completeness' => 'macros_present',
            'is_published' => true,
        ]);

        $browseReady = app(ToolsCatalog::class)->browse(Request::create(route('tools.index'), 'GET'));
        $ready = collect($browseReady['items']->items())->firstWhere('slug', 'calorie-nutrition-tracker');
        $this->assertSame('Available', $ready['availability']);
        $this->assertSame('available', $ready['availability_state']);
        $this->assertSame('Open', $ready['cta']);
    }

    public function test_tools_nav_active_for_registered_tool_routes(): void
    {
        $html = $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->getContent();

        $this->assertTrue(ToolsSection::isActive());
        $this->assertMatchesRegularExpression(
            '/aria-current="page"[^>]*>\s*Tools\s*</s',
            $html
        );
    }

    public function test_welcome_screens_include_back_to_tools(): void
    {
        $this->get(route('tools.career-interest'))
            ->assertOk()
            ->assertSee('Back to Tools')
            ->assertSee(route('tools.index'), false);

        $this->get(route('tools.skills-strengths'))
            ->assertOk()
            ->assertSee('Back to Tools');

        $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->assertSee('Back to Tools');

        $this->get(route('tools.nutrition'))
            ->assertOk()
            ->assertSee('Food Catalogue Pending')
            ->assertSee('Back to Tools');
    }

    public function test_expert_discovery_uses_verified_categories_only(): void
    {
        $discovery = app(ExpertsDiscovery::class);

        $psych = $discovery->psychologists();
        $this->assertSame(route('professionals', ['category' => 'mental-health']), $psych['url']);

        $career = $discovery->career();
        $this->assertSame('career-business', $career['category']);
        $this->assertStringContainsString('category=career-business', $career['url']);

        $nutrition = $discovery->nutrition();
        $this->assertSame('nutrition-fitness', $nutrition['category']);
        $this->assertStringContainsString('category=nutrition-fitness', $nutrition['url']);
    }

    private function publishAllTools(): void
    {
        $map = [
            'daily-mood-check-in' => 'tools.psychology.mood',
            'digital-journal' => 'tools.psychology.journal',
            'gratitude-journal' => 'tools.psychology.gratitude',
            'career-interest-test' => 'tools.career-interest',
            'skills-strengths-assessment' => 'tools.skills-strengths',
            'bmi-bmr-calculator' => 'tools.bmi-bmr',
            'water-intake-tracker' => 'tools.water-intake',
            'weight-journey' => 'tools.weight-journey',
            'calorie-nutrition-tracker' => 'tools.nutrition',
        ];

        foreach ($map as $slug => $route) {
            Tool::query()->where('slug', $slug)->update([
                'status' => 'active',
                'is_implemented' => true,
                'route_name' => $route,
            ]);
        }

        $this->assertTrue(ToolCategory::query()->exists());
    }
}
