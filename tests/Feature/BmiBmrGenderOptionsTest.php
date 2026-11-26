<?php

namespace Tests\Feature;

use App\Models\BmiBmrCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\BmiBmrCalculator;
use App\Support\BmiBmr\BmiBmrCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 58 — BMI web form offers Male/Female only; Prefer not to say removed for new calculations.
 * Historical unspecified records remain readable. Tasks 59–62 not started.
 */
class BmiBmrGenderOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_form_renders_male_and_female_without_prefer_not_to_say(): void
    {
        $this->storage(true);

        $html = $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="female"', $html);
        $this->assertStringContainsString('Female', $html);
        $this->assertStringContainsString('value="male"', $html);
        $this->assertStringContainsString('Male', $html);
        $this->assertStringContainsString('yk-bmi__seg--2', $html);
        $this->assertStringNotContainsString('Prefer not to say', $html);
        $this->assertStringNotContainsString('value="unspecified"', $html);

        // Tasks 56 + 57 markers remain.
        $this->assertStringContainsString('data-yk-tool-compact-header', $html);
        $this->assertStringContainsString('yk-bmi__hero--compact', $html);
        $weightPos = strpos($html, 'name="weight_unit"');
        $agePos = strpos($html, 'id="bmi-age"');
        $this->assertNotFalse($weightPos);
        $this->assertNotFalse($agePos);
        $this->assertLessThan($agePos, $weightPos);
    }

    public function test_male_and_female_submissions_succeed_with_expected_outputs(): void
    {
        $this->storage(true);

        $base = [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'activity' => 'sedentary',
        ];

        $this->post(route('tools.bmi-bmr.calculate'), $base + ['sex' => 'male'])
            ->assertRedirect(route('tools.bmi-bmr.results'));
        $this->get(route('tools.bmi-bmr.results'))
            ->assertOk()
            ->assertSee('24.7')
            ->assertSee('1,780')
            ->assertSee('2,136');

        $this->post(route('tools.bmi-bmr.calculate'), $base + ['sex' => 'female'])
            ->assertRedirect(route('tools.bmi-bmr.results'));
        $this->get(route('tools.bmi-bmr.results'))
            ->assertOk()
            ->assertSee('24.7')
            ->assertSee('1,614')
            ->assertSee('1,937');
    }

    public function test_missing_and_unsupported_sex_are_rejected(): void
    {
        $this->storage(true);

        $base = [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'activity' => 'sedentary',
        ];

        $this->from(route('tools.bmi-bmr'))
            ->post(route('tools.bmi-bmr.calculate'), $base)
            ->assertRedirect(route('tools.bmi-bmr'))
            ->assertSessionHasErrors('sex');

        $this->from(route('tools.bmi-bmr'))
            ->post(route('tools.bmi-bmr.calculate'), $base + ['sex' => 'unspecified'])
            ->assertRedirect(route('tools.bmi-bmr'))
            ->assertSessionHasErrors('sex');

        $this->from(route('tools.bmi-bmr'))
            ->post(route('tools.bmi-bmr.calculate'), $base + ['sex' => 'other'])
            ->assertRedirect(route('tools.bmi-bmr'))
            ->assertSessionHasErrors('sex');
    }

    public function test_legacy_unspecified_draft_clears_sex_but_keeps_other_inputs(): void
    {
        $this->storage(true);

        $display = [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'sex' => BmiBmrCatalog::SEX_UNSPECIFIED,
            'activity' => 'sedentary',
        ];

        session([
            \App\Services\BmiBmrDraft::KEY => [
                'result' => null,
                'normalized' => [
                    'display_inputs' => $display,
                    'age' => 30,
                    'height_cm' => 180.0,
                    'weight_kg' => 80.0,
                    'sex' => BmiBmrCatalog::SEX_UNSPECIFIED,
                    'activity' => 'sedentary',
                ],
                'completion_key' => (string) Str::uuid(),
                'awaiting_login' => false,
                'owner' => 'guest:'.session('nutrition.bmi_bmr_guest', (string) Str::uuid()),
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ]);
        // Align draft owner with BmiBmrDraft::owner() for guests.
        if (! session()->has('nutrition.bmi_bmr_guest')) {
            session(['nutrition.bmi_bmr_guest' => (string) Str::uuid()]);
        }
        $guest = session('nutrition.bmi_bmr_guest');
        $draft = session(\App\Services\BmiBmrDraft::KEY);
        $draft['owner'] = 'guest:'.$guest;
        session([\App\Services\BmiBmrDraft::KEY => $draft]);

        $html = $this->get(route('tools.bmi-bmr'))->assertOk()->getContent();

        $this->assertStringContainsString('value="80"', $html);
        $this->assertStringContainsString('value="30"', $html);
        $this->assertStringContainsString('value="180"', $html);
        $this->assertStringNotContainsString('value="unspecified"', $html);
        $this->assertDoesNotMatchRegularExpression('/name=["\']sex["\'][^>]*checked|checked[^>]*name=["\']sex["\']/', $html);
    }

    public function test_historical_unspecified_record_remains_readable_unchanged(): void
    {
        $this->storage(true);
        $user = User::query()->create([
            'name' => 'BMI Guest',
            'email' => 'bmi-t58@example.com',
            'password' => bcrypt('password'),
        ]);

        $payload = (new BmiBmrCalculator)->calculate([
            'age' => 30,
            'height_cm' => 180.0,
            'weight_kg' => 80.0,
            'sex' => BmiBmrCatalog::SEX_UNSPECIFIED,
            'activity' => 'sedentary',
        ]);

        $record = new BmiBmrCalculation([
            'completion_key' => (string) Str::uuid(),
            'formula_version' => BmiBmrCatalog::FORMULA_VERSION,
            'age' => 30,
            'height_cm' => 180,
            'weight_kg' => 80,
            'sex' => BmiBmrCatalog::SEX_UNSPECIFIED,
            'activity' => 'sedentary',
            'activity_factor' => 1.2,
            'excluded_population' => false,
            'bmi_exact' => $payload['bmi']['exact'],
            'bmi_display' => $payload['bmi']['display'],
            'bmi_category_key' => $payload['bmi']['category']['key'] ?? null,
            'bmi_category_label' => $payload['bmi']['category']['label'] ?? null,
            'bmr_kcal' => $payload['bmr']['display'] ?? null,
            'tdee_kcal' => $payload['tdee']['display'] ?? null,
            'payload' => $payload,
            'calculated_at' => now(),
        ]);
        $record->user_id = $user->id;
        $record->save();

        $this->assertSame(BmiBmrCatalog::SEX_UNSPECIFIED, $record->fresh()->sex);
        $this->assertFalse($payload['bmr']['eligible']);
        $this->assertEqualsWithDelta(24.7, (float) $record->bmi_display, 0.05);
        $this->assertNull($record->bmr_kcal);

        $this->actingAs($user, 'user')
            ->get(route('tools.bmi-bmr.history.show', $record))
            ->assertOk()
            ->assertSee('unspecified')
            ->assertSee('24.7');

        $this->assertSame(BmiBmrCatalog::SEX_UNSPECIFIED, $record->fresh()->sex);
        $this->assertSame((string) $payload['bmi']['display'], (string) $record->fresh()->bmi_display);
    }

    public function test_web_sex_keys_differ_from_full_catalog_and_formulas_unchanged(): void
    {
        $this->assertSame(['male', 'female'], BmiBmrCatalog::webSexKeys());
        $this->assertContains(BmiBmrCatalog::SEX_UNSPECIFIED, BmiBmrCatalog::sexKeys());

        $calc = app(BmiBmrCalculator::class);
        $male = $calc->calculate([
            'age' => 30, 'height_cm' => 180.0, 'weight_kg' => 80.0, 'sex' => 'male', 'activity' => 'sedentary',
        ]);
        $female = $calc->calculate([
            'age' => 30, 'height_cm' => 180.0, 'weight_kg' => 80.0, 'sex' => 'female', 'activity' => 'sedentary',
        ]);

        $this->assertSame(1780, $male['bmr']['display']);
        $this->assertSame(2136, $male['tdee']['display']);
        $this->assertSame(1614, $female['bmr']['display']);
        $this->assertSame(1937, $female['tdee']['display']);
        $this->assertEqualsWithDelta(24.7, (float) $male['bmi']['display'], 0.05);
        $this->assertEqualsWithDelta(24.7, (float) $female['bmi']['display'], 0.05);
    }

    public function test_unrelated_registration_surfaces_not_forced_to_bmi_web_sex_keys(): void
    {
        // Scope protection: catalog still retains unspecified for historical/engine use;
        // BMI web restriction must not delete the constant used by other consumers.
        $this->assertSame('unspecified', BmiBmrCatalog::SEX_UNSPECIFIED);
        $this->assertNotSame(BmiBmrCatalog::webSexKeys(), BmiBmrCatalog::sexKeys());

        $register = resource_path('views/frontend/auth');
        if (is_dir($register)) {
            $files = glob($register.'/*.blade.php') ?: [];
            foreach ($files as $file) {
                $contents = file_get_contents($file);
                $this->assertIsString($contents);
                $this->assertStringNotContainsString('webSexKeys', $contents);
            }
        }
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
