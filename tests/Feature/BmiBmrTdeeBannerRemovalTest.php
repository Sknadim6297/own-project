<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\BmiBmrCalculator;
use App\Support\Tools\ToolPageLeads;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 56 — BMI, BMR & TDEE Calculator: large decorative banner absent; compact header remains.
 * Presentation-only lock (Task 56). Field order is covered by Task 57 / BmiBmrAgeFieldOrderTest.
 */
class BmiBmrTdeeBannerRemovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_calculator_renders_compact_header_without_decorative_banner(): void
    {
        $this->storage(true);

        $html = $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-yk-tool-compact-header', $html);
        $this->assertStringContainsString('yk-bmi__hero--compact', $html);
        $this->assertStringContainsString('yk-bmi--compact-header', $html);
        // two-tone heading splits the accent span — assert fragments, not one contiguous string.
        $this->assertStringContainsString('BMI, BMR', $html);
        $this->assertStringContainsString('TDEE Calculator', $html);
        $this->assertStringContainsString('data-yk-tool-page-lead', $html);
        $this->assertStringContainsString('Estimate body mass index, metabolic rate, and daily energy requirements.', $html);
        $this->assertStringContainsString('not medical advice', $html);
        $this->assertNotSame('', ToolPageLeads::forBmi());

        $this->assertStringNotContainsString('yk-bmi__hero-art', $html);
        $this->assertStringNotContainsString('bmi-hero-woman', $html);
        $this->assertStringNotContainsString('yk-bmi__pills', $html);
        $this->assertStringNotContainsString('class="yk-bmi__leaf"', $html);
    }

    public function test_existing_fields_gender_options_remain_unchanged(): void
    {
        $this->storage(true);
        $html = $this->get(route('tools.bmi-bmr'))->assertOk()->getContent();

        $this->assertStringContainsString('data-bmi-form', $html);
        $this->assertStringContainsString('Calculate Results', $html);

        // Task 58 superseded the third Prefer-not-to-say option; Male/Female remain.
        foreach (['female' => 'Female', 'male' => 'Male'] as $value => $label) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringNotContainsString('Prefer not to say', $html);
        $this->assertStringNotContainsString('value="unspecified"', $html);

        foreach (['age', 'height_unit', 'weight_unit', 'sex', 'activity'] as $name) {
            $this->assertMatchesRegularExpression('/name=["\']'.$name.'["\']/', $html);
        }
    }

    public function test_identical_inputs_produce_identical_bmi_bmr_tdee_outputs(): void
    {
        $calc = app(BmiBmrCalculator::class);
        $payload = [
            'age' => 30,
            'height_cm' => 180.0,
            'weight_kg' => 80.0,
            'sex' => 'male',
            'activity' => 'sedentary',
        ];
        $a = $calc->calculate($payload);
        $b = $calc->calculate($payload);

        $this->assertSame($a, $b);
        $this->assertEqualsWithDelta(24.7, (float) $a['bmi']['display'], 0.05);
        $this->assertSame(1780, $a['bmr']['display']);
        $this->assertSame(2136, $a['tdee']['display']);
    }

    public function test_calculate_route_still_redirects_to_results_with_expected_values(): void
    {
        $this->storage(true);

        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
        ])->assertRedirect(route('tools.bmi-bmr.results'));

        $this->get(route('tools.bmi-bmr.results'))
            ->assertOk()
            ->assertSee('24.7')
            ->assertSee('Healthy weight')
            ->assertSee('1,780')
            ->assertSee('2,136');
    }

    public function test_compact_css_and_unrelated_surfaces_remain_scoped(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-bmi-bmr.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('.yk-bmi__hero--compact', $css);
        $this->assertStringContainsString('.yk-bmi--compact-header .yk-bmi__hero-art', $css);

        $homeHero = file_get_contents(resource_path('views/frontend/home/partials/hero.blade.php'));
        $this->assertIsString($homeHero);
        $this->assertStringNotContainsString('yk-bmi__hero--compact', $homeHero);

        $toolsCss = file_get_contents(public_path('frontend/assets/css/yuktione-tools.css'));
        $this->assertIsString($toolsCss);
        $this->assertStringContainsString('scrollbar-width: thin', $toolsCss);
    }

    private function storage(bool $open): void
    {
        foreach (['bmi_bmr_calculations', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
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
        Schema::create('bmi_bmr_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('formula_version', 80);
            $table->unsignedSmallInteger('age');
            $table->decimal('height_cm', 8, 2);
            $table->decimal('weight_kg', 8, 2);
            $table->string('sex', 20);
            $table->string('activity', 40);
            $table->decimal('activity_factor', 5, 3);
            $table->boolean('excluded_population')->default(false);
            $table->decimal('bmi_exact', 10, 6);
            $table->decimal('bmi_display', 5, 1);
            $table->string('bmi_category_key', 40)->nullable();
            $table->string('bmi_category_label', 80)->nullable();
            $table->unsignedInteger('bmr_kcal')->nullable();
            $table->unsignedInteger('tdee_kcal')->nullable();
            $table->json('payload');
            $table->timestamp('calculated_at');
            $table->timestamps();
        });

        $category = ToolCategory::query()->create(['name' => 'Nutrition', 'slug' => 'nutrition']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'BMI & BMR Calculator',
            'slug' => 'bmi-bmr-calculator',
            'module_key' => 'nutrition',
            'description' => 'Estimate BMI and BMR.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.bmi-bmr' : null,
            'is_implemented' => $open,
            'display_order' => 1,
        ]);
    }
}
