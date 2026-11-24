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
 * Task 32 — Financial Goal Calculator aligns with Yuktione plum / pearl design.
 */
class FinancialGoalDesignConsistencyTest extends TestCase
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

    public function test_form_uses_brand_tokens_and_keeps_task_31_compact_header(): void
    {
        $html = $this->actingAs($this->member('fg-design@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('Financial Goal Calculator')
            ->assertSee('Estimate the monthly investment needed to achieve your financial goal.')
            ->assertSee('data-yk-goal-compact-header', false)
            ->assertSee('yk-goal--compact-header', false)
            ->assertSee('data-yk-icon="target"', false)
            ->assertSee('data-yk-icon="calculator"', false)
            ->getContent();

        $this->assertStringNotContainsString('yk-goal__hero-art', $html);
        $this->assertStringNotContainsString('class="yk-goal__hero"', $html);
        $this->assertStringNotContainsString('yk-goal__summary-art', $html);
        $this->assertStringContainsString('name="goal_type"', $html);
        $this->assertStringContainsString('data-pfm-form', $html);

        $css = file_get_contents(public_path('frontend/assets/css/yuktione-pfm-goal.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('--g-paper: #fff8fb', $css);
        $this->assertStringContainsString('--g-green: #8a0f4d', $css);
        $this->assertStringContainsString('--g-mint: #f8eaf1', $css);
        $this->assertStringNotContainsString('--g-green: #14705f', $css);
        $this->assertStringNotContainsString('--g-paper: #f6f3ec', $css);
    }

    public function test_other_pfm_tools_remain_on_shared_stylesheet(): void
    {
        $ef = $this->actingAs($this->member('fg-design-ef@example.com'), 'user')
            ->get(route('tools.pfm.emergency-fund'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('yk-pfm__hero', $ef);
        $this->assertStringContainsString('yuktione-pfm.css', $ef);
        $this->assertStringNotContainsString('yuktione-pfm-goal.css', $ef);

        $mmp = $this->actingAs($this->member('fg-design-mmp@example.com'), 'user')
            ->get(route('tools.pfm.money-planner'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('yuktione-pfm.css', $mmp);
        $this->assertStringNotContainsString('yuktione-pfm-goal.css', $mmp);
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
            'name' => 'FG Design Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
