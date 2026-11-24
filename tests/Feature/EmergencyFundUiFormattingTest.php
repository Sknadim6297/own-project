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
 * Task 30 — Emergency Fund premium UI formatting (presentation only).
 */
class EmergencyFundUiFormattingTest extends TestCase
{
    private const ROUTE = 'tools.pfm.emergency-fund';

    private const TOOL_KEY = 'emergency-fund-calculator';

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

    public function test_form_shows_coverage_cards_currency_prefix_and_icons(): void
    {
        $html = $this->actingAs($this->member('ef-ui-form@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('yk-pfm--ef', $html);
        $this->assertStringContainsString('yk-ef__duration-grid', $html);
        $this->assertStringContainsString('name="coverage_months"', $html);
        $this->assertStringContainsString('value="3"', $html);
        $this->assertStringContainsString('value="6"', $html);
        $this->assertStringContainsString('value="9"', $html);
        $this->assertStringContainsString('value="12"', $html);
        $this->assertStringContainsString('yk-ef__prefix', $html);
        $this->assertStringContainsString('data-yk-icon="shield-check"', $html);
        $this->assertStringContainsString('Essential monthly expenses', $html);
        $this->assertStringNotContainsString('<option value="6"', $html);
    }

    public function test_results_show_progress_summary_and_primary_save_hierarchy(): void
    {
        $this->actingAs($this->member('ef-ui-results@example.com'), 'user');

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 25000,
            'coverage_months' => 6,
            'current_savings' => 50000,
            'monthly_saving_capacity' => 10000,
        ])->assertRedirect();

        $html = $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Recommended emergency fund target')
            ->assertSee('150,000.00')
            ->assertSee('Save Calculation')
            ->assertSee('Recalculate')
            ->assertSee('View History')
            ->assertSee('Emergency fund progress')
            ->getContent();

        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('yk-ef__narrative', $html);
        $this->assertStringContainsString('approximately', $html);
    }

    public function test_custom_coverage_month_is_preserved_on_form(): void
    {
        $this->actingAs($this->member('ef-ui-custom@example.com'), 'user');

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 8,
            'current_savings' => 0,
            'monthly_saving_capacity' => 0,
        ])->assertRedirect();

        $this->post(route(self::ROUTE.'.recalculate'))->assertRedirect(route(self::ROUTE));

        $html = $this->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('value="8"', $html);
        $this->assertStringContainsString('months (saved)', $html);
        $this->assertMatchesRegularExpression('/name="coverage_months"[^>\n]*value="8"[^>]*checked|name="coverage_months"\s+value="8"\s+checked/s', $html);
    }

    public function test_zero_capacity_and_achieved_target_copy(): void
    {
        $this->actingAs($this->member('ef-ui-achieved@example.com'), 'user');

        $this->post(route(self::ROUTE.'.calculate'), [
            'monthly_expenses' => 10000,
            'coverage_months' => 3,
            'current_savings' => 30000,
            'monthly_saving_capacity' => 0,
        ])->assertRedirect();

        $html = $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Target achieved')
            ->assertSee('meet or exceed the recommended target')
            ->getContent();

        $this->assertStringNotContainsString('you could reach your emergency fund target in approximately', $html);
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
            'name' => 'Emergency Fund UI Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
