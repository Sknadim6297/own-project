<?php

namespace Tests\Feature;

use App\Models\BmiBmrCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\BmiBmrCalculator;
use App\Services\BmiBmrDraft;
use App\Services\BmiBmrInputNormalizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4D — BMI & BMR Calculator premium redesign (UI + calculation integrity).
 */
class Phase4DBmiBmrRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_bmi_pages_opt_into_tools_ds_and_drop_lavender(): void
    {
        $this->storage(true);

        $cssPath = public_path('frontend/assets/css/yuktione-bmi-bmr.css');
        $jsPath = public_path('frontend/assets/js/yuktione-bmi-bmr.js');
        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);
        $css = file_get_contents($cssPath);
        $this->assertStringNotContainsString('--yk-lavender', $css);
        $this->assertStringNotContainsString('157, 129, 219', $css);
        $this->assertStringNotContainsString('9d81db', $css);
        $this->assertStringNotContainsString('9D81DB', $css);
        $this->assertStringContainsString('--yk-gold', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);

        $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-bmi', false)
            ->assertSee('data-bmi-form', false)
            ->assertSee('yuktione-bmi-bmr.js', false)
            ->assertSee('name="height_unit"', false)
            ->assertSee('name="weight_unit"', false)
            ->assertSee('name="height_cm"', false)
            ->assertSee('name="weight_kg"', false)
            ->assertSee('disabled', false)
            ->assertSee('Calculate')
            ->assertDontSee('YuktiPoints', false);

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('data-yk-mood', false)
            ->assertDontSee('data-yk-bmi', false);
    }

    public function test_golden_metric_and_imperial_formulas_match_existing_calculator(): void
    {
        $metric = (new BmiBmrCalculator)->calculate([
            'age' => 30,
            'height_cm' => 180,
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
        ]);
        $this->assertSame(24.7, $metric['bmi']['display']);
        $this->assertSame('healthy', $metric['bmi']['category']['key']);
        $this->assertSame(1780, $metric['bmr']['display']);
        $this->assertSame(2136, $metric['tdee']['display']);
        $this->assertSame('Mifflin–St Jeor', $metric['bmr']['equation']);

        $normalized = (new BmiBmrInputNormalizer)->normalize([
            'age' => 30,
            'height_unit' => 'ft_in',
            'height_ft' => 5,
            'height_in' => 10.866,
            'weight_unit' => 'lb',
            'weight_lb' => 176.37,
            'sex' => 'male',
            'activity' => 'sedentary',
        ]);
        $imperial = (new BmiBmrCalculator)->calculate($normalized);
        $this->assertEqualsWithDelta(24.7, $imperial['bmi']['display'], 0.2);
        $this->assertSame('healthy', $imperial['bmi']['category']['key']);
        $this->assertTrue($imperial['bmr']['eligible']);
    }

    public function test_unit_switch_contract_metric_and_imperial_submissions(): void
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
            // Inactive unit fields may appear disabled client-side; if present empty they stay nullable.
            'height_ft' => '',
            'height_in' => '',
            'weight_lb' => '',
        ])->assertRedirect(route('tools.bmi-bmr.results'));

        $this->get(route('tools.bmi-bmr.results'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('24.7')
            ->assertSee('1,780')
            ->assertSee('2,136');

        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 25,
            'height_unit' => 'ft_in',
            'height_ft' => 5,
            'height_in' => 4.96,
            'weight_unit' => 'lb',
            'weight_lb' => 132.28,
            'sex' => 'female',
            'activity' => 'moderately-active',
            'height_cm' => '',
            'weight_kg' => '',
        ])->assertRedirect(route('tools.bmi-bmr.results'));

        $draft = session(BmiBmrDraft::KEY);
        $this->assertIsArray($draft);
        $result = $draft['result'] ?? null;
        $this->assertIsArray($result);
        $this->assertSame(1345, $result['bmr']['display']);
    }

    public function test_guest_calculate_auth_save_ownership_and_idempotency(): void
    {
        $this->storage(true);
        $member = $this->member('bmi4d@example.com');
        $other = $this->member('bmi4d-other@example.com');

        $this->actingAs($member, 'user');
        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
        ])->assertRedirect(route('tools.bmi-bmr.results'));

        $this->post(route('tools.bmi-bmr.save'))->assertRedirect();
        $this->assertSame(1, BmiBmrCalculation::query()->count());
        $saved = BmiBmrCalculation::query()->first();
        $this->assertSame($member->id, $saved->user_id);

        // Replaying the same draft completion key must not create a second row.
        $this->withSession([
            BmiBmrDraft::KEY => [
                'result' => $saved->payload,
                'normalized' => [
                    'age' => 30,
                    'height_cm' => 180,
                    'weight_kg' => 80,
                    'sex' => 'male',
                    'activity' => 'sedentary',
                    'excluded_population' => false,
                    'height_unit' => 'cm',
                    'weight_unit' => 'kg',
                    'display_inputs' => [],
                ],
                'completion_key' => $saved->completion_key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ])->post(route('tools.bmi-bmr.save'))->assertRedirect();

        $this->assertSame(1, BmiBmrCalculation::query()->count());

        $this->actingAs($other, 'user')
            ->get(route('tools.bmi-bmr.history.show', $saved))
            ->assertForbidden();

        $this->actingAs($member, 'user')
            ->get(route('tools.bmi-bmr.history'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('My BMI/BMR History');
    }

    public function test_validation_rejects_invalid_inputs_without_changing_rules(): void
    {
        $this->storage(true);

        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 0,
            'weight_unit' => 'kg',
            'weight_kg' => 70,
            'sex' => 'female',
            'activity' => 'sedentary',
        ])->assertRedirect(route('tools.bmi-bmr'))->assertSessionHasErrors();
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

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase 4D Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
