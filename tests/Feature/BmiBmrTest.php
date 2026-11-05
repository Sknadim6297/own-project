<?php

namespace Tests\Feature;

use App\Models\BmiBmrCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\BmiBmrDraft;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BmiBmrTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_guest_can_calculate_and_save_after_login(): void
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

        $this->get(route('tools.bmi-bmr.understand'))
            ->assertOk()
            ->assertSee('Understand your BMI')
            ->assertSee('cannot indicate');

        $this->get(route('tools.bmi-bmr.guidance'))
            ->assertOk()
            ->assertSee('Personalized guidance')
            ->assertSee('Illustrative examples only')
            ->assertSee(route('professionals', ['category' => 'nutrition-fitness']), false);

        $this->post(route('tools.bmi-bmr.save'))->assertRedirect(route('login'));
        $this->assertTrue(session(BmiBmrDraft::KEY)['awaiting_login']);

        $member = $this->member('bmi-guest@example.com');
        $this->actingAs($member, 'user');
        Event::dispatch(new Login('user', $member, false));
        app(BmiBmrDraft::class)->claimForCurrentUser((int) $member->id);

        $this->get(route('tools.bmi-bmr.finish'))->assertRedirect();
        $saved = BmiBmrCalculation::query()->first();
        $this->assertNotNull($saved);
        $this->assertSame($member->id, $saved->user_id);
        $this->assertNull(session(BmiBmrDraft::KEY));

        $this->get(route('tools.bmi-bmr.history'))->assertOk()->assertSee('My BMI/BMR History');
        $this->get(route('tools.bmi-bmr.history.show', $saved))->assertOk()->assertSee('24.7');
    }

    public function test_invalid_inputs_and_closed_tool(): void
    {
        $this->storage(true);
        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => -10,
            'weight_unit' => 'kg',
            'weight_kg' => 70,
            'sex' => 'female',
            'activity' => 'sedentary',
        ])->assertRedirect(route('tools.bmi-bmr'))->assertSessionHasErrors();

        $this->storage(false);
        // recreate closed tool after storage wiped tools — storage(false) recreates schema
        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 170,
            'weight_unit' => 'kg',
            'weight_kg' => 65,
            'sex' => 'female',
            'activity' => 'sedentary',
        ])->assertForbidden();
    }

    public function test_ownership_and_delete(): void
    {
        $this->storage(true);
        $owner = $this->member('bmi-owner@example.com');
        $other = $this->member('bmi-other@example.com');
        $record = $this->seedCalculation($owner);

        $this->actingAs($other, 'user')
            ->get(route('tools.bmi-bmr.history.show', $record))
            ->assertForbidden();
        $this->actingAs($other, 'user')
            ->delete(route('tools.bmi-bmr.history.destroy', $record))
            ->assertForbidden();
        $this->assertSame(1, BmiBmrCalculation::query()->count());

        $this->actingAs($owner, 'user')
            ->delete(route('tools.bmi-bmr.history.destroy', $record))
            ->assertRedirect(route('tools.bmi-bmr.history'));
        $this->assertSame(0, BmiBmrCalculation::query()->count());
    }

    public function test_duplicate_save_is_idempotent(): void
    {
        $this->storage(true);
        $member = $this->member('bmi-dup@example.com');
        $this->actingAs($member, 'user');

        $this->post(route('tools.bmi-bmr.calculate'), [
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 180,
            'weight_unit' => 'kg',
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
        ]);
        $key = session(BmiBmrDraft::KEY)['completion_key'];
        $draft = session(BmiBmrDraft::KEY);

        $this->post(route('tools.bmi-bmr.save'))->assertRedirect();
        $this->assertSame(1, BmiBmrCalculation::query()->count());

        $this->withSession([BmiBmrDraft::KEY => $draft + ['owner' => 'user:'.$member->id]])
            ->post(route('tools.bmi-bmr.save'))
            ->assertRedirect();
        $this->assertSame(1, BmiBmrCalculation::query()->count());
        $this->assertSame($key, BmiBmrCalculation::query()->first()->completion_key);
    }

    public function test_publish_migration_and_other_nutrition_remain_unpublished(): void
    {
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

        app(\App\Services\ToolRegistryRegistrar::class)->register();
        $this->assertFalse(Tool::query()->where('slug', 'bmi-bmr-calculator')->first()->canLaunch());

        (require database_path('migrations/2026_11_03_170000_publish_bmi_bmr_calculator.php'))->up();

        $tool = Tool::query()->where('slug', 'bmi-bmr-calculator')->firstOrFail();
        $this->assertTrue($tool->canLaunch());
        $this->assertSame('tools.bmi-bmr', $tool->route_name);
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'water-intake-tracker')->value('status'));
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'weight-journey')->value('status'));
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'calorie-nutrition-tracker')->value('status'));

        $this->get(route('tools.index'))->assertOk()->assertSee('BMI & BMR Calculator');
        $this->assertFalse(config('yuktione.programmes_enabled'));
    }

    private function seedCalculation(User $user): BmiBmrCalculation
    {
        $record = new BmiBmrCalculation([
            'completion_key' => (string) \Illuminate\Support\Str::uuid(),
            'formula_version' => 'bmi-who-adult-v1+mifflin-st-jeor-v1',
            'age' => 30,
            'height_cm' => 180,
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
            'activity_factor' => 1.2,
            'excluded_population' => false,
            'bmi_exact' => 24.691358,
            'bmi_display' => 24.7,
            'bmi_category_key' => 'healthy',
            'bmi_category_label' => 'Healthy weight',
            'bmr_kcal' => 1780,
            'tdee_kcal' => 2136,
            'payload' => ['bmi' => ['display' => 24.7]],
            'calculated_at' => now(),
        ]);
        $record->user_id = $user->id;
        $record->save();

        return $record;
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
            'name' => 'BMI Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
