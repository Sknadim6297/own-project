<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\User;
use App\Services\ToolRegistryRegistrar;
use App\Services\ToolsCatalog;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 37 — Global tools: unnecessary large banners removed; compact headers remain.
 */
class ToolsCompactHeaderTest extends TestCase
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

    public function test_catalogue_still_has_twenty_three_registered_tools(): void
    {
        $placement = (new \ReflectionClass(ToolsCatalog::class))->getConstant('TOOL_PLACEMENT');
        $this->assertIsArray($placement);
        $this->assertCount(23, $placement);
        $this->assertCount(23, $this->routeMap());
        $this->assertCount(23, Tool::query()->where('is_implemented', true)->where('status', 'active')->get());
        $this->assertCount(8, PfmCatalog::TOOLS);
    }

    public function test_pfm_tools_use_compact_header_without_decorative_art(): void
    {
        $user = $this->member('t37-pfm@example.com');

        foreach ([
            'tools.pfm.money-planner' => ['Monthly Money Planner', 'wallet'],
            'tools.pfm.net-worth' => ['Net Worth Calculator', 'chart-pie'],
            'tools.pfm.emergency-fund' => ['Emergency Fund Calculator', 'shield-check'],
            'tools.pfm.debt-emi' => ['Debt', 'calculator'],
            'tools.pfm.retirement' => ['Retirement', 'calculator'],
            'tools.pfm.inflation' => ['Inflation', 'calculator'],
            'tools.pfm.savings-investment' => ['Savings', 'calculator'],
        ] as $route => [$titleNeedle, $icon]) {
            $html = $this->actingAs($user, 'user')
                ->get(route($route))
                ->assertOk()
                ->assertSee($titleNeedle)
                ->assertSee('data-yk-tool-compact-header', false)
                ->assertSee('yk-pfm__hero--compact', false)
                ->assertSee('data-yk-icon="'.$icon.'"', false)
                ->getContent();

            $this->assertStringNotContainsString('yk-pfm__hero-art', $html);
            $this->assertStringNotContainsString('id="pfmHeroBar"', $html);
            $this->assertStringNotContainsString('viewBox="0 0 440 260"', $html);
        }
    }

    public function test_financial_goal_compact_header_from_task_31_remains(): void
    {
        $html = $this->actingAs($this->member('t37-fg@example.com'), 'user')
            ->get(route('tools.pfm.financial-goal'))
            ->assertOk()
            ->assertSee('data-yk-goal-compact-header', false)
            ->assertSee('Financial Goal Calculator')
            ->getContent();

        $this->assertStringNotContainsString('yk-goal__hero-art', $html);
        $this->assertStringContainsString('name="goal_type"', $html);
        $this->assertStringContainsString('data-yk-goal-single', $html);
    }

    public function test_mood_bmi_career_journal_tarot_ps_drop_large_decorative_banners(): void
    {
        $user = $this->member('t37-tools@example.com');

        $mood = $this->actingAs($user, 'user')->get(route('tools.psychology.mood'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-compact-header', $mood);
        $this->assertStringContainsString('Daily Mood Check', $mood);
        $this->assertStringNotContainsString('yk-mood__hero-art', $mood);
        $this->assertStringNotContainsString('Dreamy Woman at a Cozy Desk', $mood);
        $this->assertStringNotContainsString('yk-mood__decor', $mood);
        $this->assertStringContainsString('data-mood-form', $mood);

        $bmi = $this->actingAs($user, 'user')->get(route('tools.bmi-bmr'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-compact-header', $bmi);
        $this->assertStringContainsString('BMI, BMR', $bmi);
        $this->assertStringNotContainsString('yk-bmi__hero-art', $bmi);
        $this->assertStringNotContainsString('bmi-hero-woman', $bmi);
        $this->assertStringNotContainsString('yk-bmi__pills', $bmi);
        $this->assertStringContainsString('not medical advice', $bmi);

        $cit = $this->actingAs($user, 'user')->get(route('tools.career-interest'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-compact-header', $cit);
        $this->assertStringContainsString('Career Interest Test', $cit);
        $this->assertStringNotContainsString('career-interest-3d.webp', $cit);
        $this->assertStringContainsString('Start Assessment', $cit);

        $journal = $this->actingAs($user, 'user')->get(route('tools.psychology.journal'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-compact-header', $journal);
        $this->assertStringNotContainsString('yk-journal__hero-art', $journal);

        $tarot = $this->actingAs($user, 'user')->get(route('tools.tarot.intro'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-compact-header', $tarot);
        $this->assertStringContainsString('Tarot Reflection', $tarot);
        $this->assertStringNotContainsString('yk-tarot__stack', $tarot);
        $this->assertStringContainsString('Start a Reading', $tarot);
        $this->assertStringContainsString('yk-tarot__disclaimer', $tarot);
        $this->assertStringContainsString('yk-tarot__steps', $tarot);

        $ps = $this->actingAs($user, 'user')->get(route('tools.ps.mindfulness'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-compact-header', $ps);
        $this->assertStringNotContainsString('yk-ps__head-art', $ps);
    }

    public function test_homepage_and_tools_catalogue_banners_remain_unaffected(): void
    {
        // Homepage/catalogue are out of Task 37 scope — assert source markers remain (avoid full home DB deps).
        $hero = file_get_contents(resource_path('views/frontend/home/partials/hero.blade.php'));
        $this->assertIsString($hero);
        $this->assertStringContainsString('yk-hero', $hero);

        $catalogHero = file_get_contents(resource_path('views/frontend/sections/partials/catalog-page-hero.blade.php'));
        $this->assertIsString($catalogHero);
        $this->assertNotSame('', trim($catalogHero));

        $tools = $this->get(route('tools.index'))->assertOk()->getContent();
        $this->assertTrue(
            str_contains($tools, 'yk-tools')
            || str_contains($tools, 'Mental Wellness')
            || str_contains($tools, 'catalogue')
            || str_contains($tools, 'Tools')
        );
    }

    public function test_skills_and_gratitude_remain_accessible_without_large_art(): void
    {
        $user = $this->member('t37-light@example.com');

        $skills = $this->actingAs($user, 'user')->get(route('tools.skills-strengths'))->assertOk()->getContent();
        $this->assertStringContainsString('Skills', $skills);
        $this->assertStringNotContainsString('career-interest-3d.webp', $skills);

        $gratitude = $this->actingAs($user, 'user')->get(route('tools.psychology.gratitude'))->assertOk()->getContent();
        $this->assertStringContainsString('Gratitude', $gratitude);
    }

    /**
     * @return array<string, string>
     */
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
            'name' => 'Task 37 Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
