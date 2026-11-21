<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1DebtEmiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureApiActiveSession::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->default(true);
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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
            $table->string('module_key')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('access_type')->default('free');
            $table->string('status')->default('active');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(true);
            $table->timestamps();
        });

        Schema::create('pfm_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('tool_key');
            $table->string('title');
            $table->json('inputs');
            $table->json('outputs');
            $table->text('summary')->nullable();
            $table->string('content_version')->nullable();
            $table->uuid('completion_key')->unique();
            $table->boolean('is_draft')->default(false);
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Money',
            'slug' => 'money-finance',
            'is_active' => true,
            'display_order' => 1,
        ]);

        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Debt & EMI Calculator',
            'slug' => 'debt-emi-calculator',
            'module_key' => 'money-finance',
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.pfm.debt-emi',
            'display_order' => 5,
        ]);
    }

    public function test_guest_can_calculate_and_matches_calculator_service(): void
    {
        $response = $this->postJson('/api/v1/pfm/debt-emi/calculate', [
            'principal' => 500000,
            'annual_rate_percent' => 12,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ])->assertOk();

        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'annual_rate_percent' => 12,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ]);

        $this->assertSame($expected['result']['emi'], $response->json('result.emi'));
        $this->assertSame($expected['result']['standard_interest'], $response->json('result.standard_interest'));
        $this->assertNotEmpty($response->json('completion_key'));
    }

    public function test_save_is_idempotent_and_isolated(): void
    {
        $user = User::query()->create([
            'name' => 'EMI User',
            'email' => 'emi-api@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);
        $other = User::query()->create([
            'name' => 'Other',
            'email' => 'emi-other@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);

        Sanctum::actingAs($user);
        $key = (string) Str::uuid();

        $save = $this->postJson('/api/v1/my/pfm/debt-emi/save', [
            'completion_key' => $key,
            'principal' => 2000000,
            'annual_rate_percent' => 9,
            'tenure_years' => 15,
            'extra_payment' => 5000,
        ])->assertCreated();

        $id = $save->json('calculation.id');
        $this->assertSame(1, PfmCalculation::query()->count());

        $this->postJson('/api/v1/my/pfm/debt-emi/save', [
            'completion_key' => $key,
            'principal' => 2000000,
            'annual_rate_percent' => 9,
            'tenure_years' => 15,
            'extra_payment' => 5000,
        ])->assertOk()->assertJsonPath('created', false)->assertJsonPath('calculation.id', $id);

        $this->assertSame(1, PfmCalculation::query()->count());

        $this->getJson('/api/v1/my/pfm/debt-emi/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/my/pfm/debt-emi/calculations/'.$id)->assertNotFound();
        $this->getJson('/api/v1/my/pfm/debt-emi/history')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_zero_rate_matches_unit_expectation(): void
    {
        $this->postJson('/api/v1/pfm/debt-emi/calculate', [
            'principal' => 120000,
            'annual_rate_percent' => 0,
            'tenure_years' => 1,
            'extra_payment' => 0,
        ])->assertOk()->assertJsonPath('result.emi', 10000);
    }

    public function test_admin_pause_blocks_calculate(): void
    {
        Tool::query()->where('slug', 'debt-emi-calculator')->update([
            'status' => 'inactive',
            'is_implemented' => false,
            'route_name' => null,
        ]);

        $this->getJson('/api/v1/pfm/debt-emi/options')->assertOk()->assertJsonPath('open', false);
        $this->postJson('/api/v1/pfm/debt-emi/calculate', [
            'principal' => 100000,
            'annual_rate_percent' => 10,
            'tenure_years' => 2,
        ])->assertForbidden();
    }

    public function test_delete_owned_calculation(): void
    {
        $user = User::query()->create([
            'name' => 'Delete User',
            'email' => 'emi-delete@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/v1/my/pfm/debt-emi/save', [
            'completion_key' => (string) Str::uuid(),
            'principal' => 100000,
            'annual_rate_percent' => 10,
            'tenure_years' => 2,
        ])->json('calculation.id');

        $this->deleteJson('/api/v1/my/pfm/debt-emi/calculations/'.$id)->assertOk();
        $this->assertDatabaseMissing('pfm_calculations', ['id' => $id]);
    }
}
