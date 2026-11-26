<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\BmiBmrCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 57 — BMI, BMR & TDEE: Age field appears below Weight in DOM (visual + keyboard order).
 * Formulas and Task 56 compact header remain unchanged.
 * Task 58 removed Prefer not to say from the web form (Male/Female only).
 */
class BmiBmrAgeFieldOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_weight_appears_before_age_in_dom_order(): void
    {
        $this->storage(true);

        $html = $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->getContent();

        $weightPos = strpos($html, 'name="weight_unit"');
        $agePos = strpos($html, 'id="bmi-age"');
        $heightPos = strpos($html, 'name="height_unit"');

        $this->assertNotFalse($weightPos, 'Weight unit controls should be present');
        $this->assertNotFalse($agePos, 'Age input should be present');
        $this->assertNotFalse($heightPos, 'Height unit controls should be present');
        $this->assertLessThan($agePos, $weightPos, 'Weight must appear before Age in the DOM');
        $this->assertLessThan($heightPos, $agePos, 'Age must appear before Height in the DOM (below Weight, above Height)');

        $this->assertStringContainsString('name="age"', $html);
        $this->assertStringContainsString('for="bmi-age"', $html);
        $this->assertStringContainsString('min="1"', $html);
        $this->assertStringContainsString('max="120"', $html);
        $this->assertStringContainsString('Adult categories from age 18+', $html);
        $this->assertStringContainsString('yk-bmi__metric--weight', $html);
        $this->assertStringContainsString('yk-bmi__metric--age', $html);
        $this->assertStringContainsString('yk-bmi__metric--height', $html);
    }

    public function test_gender_units_and_compact_header_remain(): void
    {
        $this->storage(true);
        $html = $this->get(route('tools.bmi-bmr'))->assertOk()->getContent();

        $this->assertStringContainsString('data-yk-tool-compact-header', $html);
        $this->assertStringContainsString('yk-bmi__hero--compact', $html);
        $this->assertStringContainsString('yk-bmi--compact-header', $html);
        $this->assertStringContainsString('BMI, BMR', $html);
        $this->assertStringContainsString('TDEE Calculator', $html);
        $this->assertStringNotContainsString('yk-bmi__hero-art', $html);

        // Task 58: Prefer not to say removed from this calculator's web form.
        foreach (['female' => 'Female', 'male' => 'Male'] as $value => $label) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringNotContainsString('Prefer not to say', $html);

        foreach (['kg', 'lb'] as $unit) {
            $this->assertMatchesRegularExpression('/name=["\']weight_unit["\'][^>]*value=["\']'.$unit.'["\']|value=["\']'.$unit.'["\'][^>]*name=["\']weight_unit["\']/', $html);
        }
        foreach (['cm', 'ft_in'] as $unit) {
            $this->assertStringContainsString('value="'.$unit.'"', $html);
        }
    }

    public function test_baseline_calculation_unchanged_after_field_reorder(): void
    {
        $calc = app(BmiBmrCalculator::class);
        $payload = [
            'age' => 30,
            'height_cm' => 180.0,
            'weight_kg' => 80.0,
            'sex' => 'male',
            'activity' => 'sedentary',
        ];
        $result = $calc->calculate($payload);

        $this->assertEqualsWithDelta(24.7, (float) $result['bmi']['display'], 0.05);
        $this->assertSame(1780, $result['bmr']['display']);
        $this->assertSame(2136, $result['tdee']['display']);
    }

    public function test_validation_and_calculate_still_work_with_reordered_fields(): void
    {
        $this->storage(true);

        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 0,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
        ])->assertSessionHasErrors();

        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 5,
            'sex' => 'male',
            'activity' => 'sedentary',
        ])->assertSessionHasErrors();

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

    public function test_css_places_age_under_weight_on_desktop_grid(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-bmi-bmr.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('.yk-bmi__metric--weight', $css);
        $this->assertStringContainsString('.yk-bmi__metric--age', $css);
        $this->assertStringContainsString('.yk-bmi__metric--height', $css);
        $this->assertStringContainsString('"weight height"', $css);
        $this->assertStringContainsString('"age height"', $css);
        $this->assertStringContainsString('grid-area: age', $css);
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
