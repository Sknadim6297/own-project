<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\User;
use App\Services\ToolRegistryRegistrar;
use App\Services\ToolsCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 38 — Common tools follow Debt & EMI banner-free compact shell layout.
 */
class ToolsLayoutConsistencyTest extends TestCase
{
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

    public function test_debt_emi_is_the_layout_reference(): void
    {
        $html = $this->actingAs($this->member('t38-ref@example.com'), 'user')
            ->get(route('tools.pfm.debt-emi'))
            ->assertOk()
            ->assertSee('data-yk-tool-shell-ref="debt-emi"', false)
            ->assertSee('data-yk-tool-shell="1"', false)
            ->assertSee('data-yk-tool-compact-header', false)
            ->assertSee('yk-pfm__hero--compact', false)
            ->assertSee('yk-pfm__wrap--wide', false)
            ->assertSee('Debt &amp; EMI Calculator', false)
            ->assertSee('yuktione-tool-shell.css', false)
            ->getContent();

        $this->assertStringNotContainsString('id="pfmHeroBar"', $html);
        $this->assertStringContainsString('name="principal"', $html);
        $this->assertStringContainsString('data-pfm-form', $html);
    }

    public function test_applicable_tools_use_shell_and_compact_header_markers(): void
    {
        $user = $this->member('t38-shell@example.com');

        $pages = [
            'tools.pfm.money-planner' => ['Monthly Money Planner', 'data-pfm-form'],
            'tools.pfm.net-worth' => ['Net Worth', 'data-pfm-form'],
            'tools.pfm.emergency-fund' => ['Emergency Fund', 'data-pfm-form'],
            'tools.bmi-bmr' => ['BMI', 'Calculate Results'],
            'tools.psychology.mood' => ['Daily Mood Check', 'data-mood-form'],
            'tools.career-interest' => ['Career Interest Test', 'Start Assessment'],
            'tools.skills-strengths' => ['Skills', 'Start Assessment'],
            'tools.psychology.journal' => ['Journal', 'data-journal'],
            'tools.psychology.gratitude' => ['Gratitude', 'data-gratitude-form'],
            'tools.ps.mindfulness' => ['Mindfulness', 'data-ps-form'],
        ];

        foreach ($pages as $route => [$title, $control]) {
            $html = $this->actingAs($user, 'user')
                ->get(route($route))
                ->assertOk()
                ->assertSee($title)
                ->assertSee('data-yk-tool-shell="1"', false)
                ->assertSee('yuktione-tool-shell.css', false)
                ->getContent();

            $this->assertTrue(
                str_contains($html, 'data-yk-tool-compact-header')
                || str_contains($html, 'yk-pfm__hero--compact')
                || str_contains($html, 'data-yk-goal-compact-header')
                || str_contains($html, $control),
                "Missing compact header or control on {$route}"
            );
            $this->assertStringContainsString($control, $html);
        }
    }

    public function test_financial_goal_dedicated_layout_is_preserved(): void
    {
        $html = $this->actingAs($this->member('t38-fg@example.com'), 'user')
            ->get(route('tools.pfm.financial-goal'))
            ->assertOk()
            ->assertSee('data-yk-goal-compact-header', false)
            ->assertSee('yk-goal--single', false)
            ->assertSee('data-goal-years-slider', false)
            ->getContent();

        $this->assertStringNotContainsString('data-yk-tool-shell-ref="debt-emi"', $html);
        $this->assertStringContainsString('name="goal_type"', $html);
        $this->assertStringContainsString('yuktione-pfm-goal.css', $html);
    }

    public function test_shared_compact_header_partial_exists_and_homepage_untouched(): void
    {
        $partial = file_get_contents(resource_path('views/frontend/sections/partials/tool-compact-header.blade.php'));
        $this->assertIsString($partial);
        $this->assertStringContainsString('yk-tool-shell__header', $partial);
        $this->assertStringContainsString('data-yk-tool-shell-header', $partial);

        $css = file_get_contents(public_path('frontend/assets/css/yuktione-tool-shell.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('--yk-tool-shell-max: 1040px', $css);
        $this->assertStringContainsString('#8a0f4d', $css);
        $this->assertStringContainsString('#f8eaf1', $css);

        $hero = file_get_contents(resource_path('views/frontend/home/partials/hero.blade.php'));
        $this->assertStringContainsString('yk-hero', $hero);
        $this->assertStringNotContainsString('yk-tool-shell__header', $hero);

        $this->assertCount(23, (new \ReflectionClass(ToolsCatalog::class))->getConstant('TOOL_PLACEMENT'));
    }

    public function test_tarot_functional_intro_remains_intact(): void
    {
        $html = $this->actingAs($this->member('t38-tarot@example.com'), 'user')
            ->get(route('tools.tarot.intro'))
            ->assertOk()
            ->assertSee('data-yk-tool-shell="1"', false)
            ->assertSee('Start a Reading')
            ->assertSee('yk-tarot__disclaimer', false)
            ->assertSee('yk-tarot__steps', false)
            ->getContent();

        $this->assertStringNotContainsString('yk-tarot__stack', $html);
    }

    private function routeMap(): array
    {
        return [
            'daily-mood-check-in' => 'tools.psychology.mood',
            'digital-journal' => 'tools.psychology.journal',
            'gratitude-journal' => 'tools.psychology.gratitude',
            'career-interest-test' => 'tools.career-interest',
            'skills-strengths-assessment' => 'tools.skills-strengths',
            'bmi-bmr-calculator' => 'tools.bmi-bmr',
            'water-intake-tracker' => 'tools.water-intake',
            'weight-journey' => 'tools.weight-journey',
            'calorie-nutrition-tracker' => 'tools.nutrition',
            'tarot-reflection' => 'tools.tarot.intro',
            'parenting-stress-check' => 'tools.ps.stress-check',
            'child-routine-planner' => 'tools.ps.routine-planner',
            'parent-child-conversation-guide' => 'tools.ps.conversation-guide',
            'daily-mindfulness-calm' => 'tools.ps.mindfulness',
            'purpose-values-finder' => 'tools.ps.values-finder',
            'monthly-money-planner' => 'tools.pfm.money-planner',
            'net-worth-calculator' => 'tools.pfm.net-worth',
            'emergency-fund-calculator' => 'tools.pfm.emergency-fund',
            'financial-goal-calculator' => 'tools.pfm.financial-goal',
            'debt-emi-calculator' => 'tools.pfm.debt-emi',
            'retirement-calculator' => 'tools.pfm.retirement',
            'inflation-calculator' => 'tools.pfm.inflation',
            'savings-investment-calculator' => 'tools.pfm.savings-investment',
        ];
    }

    private function storage(): void
    {
        foreach ([
            'pfm_calculations', 'bmi_calculations', 'mood_checkins', 'journal_entries',
            'gratitude_entries', 'tarot_readings', 'career_interest_attempts',
            'skills_strengths_attempts', 'water_intake_entries', 'nutrition_logs',
            'weight_journey_entries', 'ps_calculations', 'tools', 'tool_categories',
            'customer_profiles', 'users',
        ] as $table) {
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

        app(ToolRegistryRegistrar::class)->register();

        foreach ($this->routeMap() as $slug => $routeName) {
            if (! \Illuminate\Support\Facades\Route::has($routeName)) {
                continue;
            }
            Tool::query()->where('slug', $slug)->update([
                'status' => 'active',
                'is_implemented' => true,
                'route_name' => $routeName,
            ]);
        }
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Task 38 Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
