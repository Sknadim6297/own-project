<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 31 — Financial Goal Calculator large banner removed; compact header remains.
 */
class FinancialGoalBannerRemovalTest extends TestCase
{
    private const ROUTE = 'tools.pfm.financial-goal';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureCustomerTermsAccepted::class,
        ]);
        $this->storage();
    }

    public function test_form_renders_compact_header_without_large_banner_art(): void
    {
        $html = $this->actingAs($this->member('fg-banner@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Financial Goal Calculator')
            ->assertSee('Estimate the monthly investment needed to achieve your financial goal.')
            ->assertSee('data-yk-goal-compact-header', false)
            ->assertSee('yk-goal--compact-header', false)
            ->assertSee('data-yk-icon="target"', false)
            ->getContent();

        $this->assertStringNotContainsString('yk-goal__hero-art', $html);
        $this->assertStringNotContainsString('class="yk-goal__hero"', $html);
        $this->assertStringContainsString('name="goal_type"', $html);
        $this->assertStringContainsString('data-pfm-form', $html);
    }

    public function test_emergency_fund_shared_pfm_hero_remains_available(): void
    {
        $html = $this->actingAs($this->member('fg-ef-isolation@example.com'), 'user')
            ->get(route('tools.pfm.emergency-fund'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('yk-pfm__hero', $html);
        $this->assertStringNotContainsString('yk-goal__hero-art', $html);
    }

    private function storage(): void
    {
        foreach (['pfm_calculations', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
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
        Schema::create('pfm_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tool_key', 64)->index();
            $table->string('title', 160);
            $table->json('inputs');
            $table->json('outputs');
            $table->string('summary', 255)->nullable();
            $table->string('content_version', 64)->nullable();
            $table->uuid('completion_key')->unique();
            $table->boolean('is_draft')->default(false)->index();
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Money & Finance',
            'slug' => 'money-finance',
            'is_active' => true,
            'display_order' => 5,
        ]);

        foreach (PfmCatalog::TOOLS as $tool) {
            Tool::query()->create([
                'tool_category_id' => $category->id,
                'name' => $tool['name'],
                'slug' => $tool['slug'],
                'module_key' => PfmCatalog::MODULE_KEY,
                'description' => $tool['description'],
                'status' => 'active',
                'route_name' => $tool['route'],
                'is_implemented' => true,
                'display_order' => $tool['display_order'],
            ]);
        }
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'FG Banner Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
