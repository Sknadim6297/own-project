<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\User;
use App\Services\ToolRegistryRegistrar;
use App\Services\ToolsCatalog;
use App\Support\Tools\ToolPageLeads;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 39 — Short page-level tool descriptions followed directly by the functional tool.
 */
class ToolsShortDescriptionTest extends TestCase
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

    public function test_tool_page_leads_cover_all_registered_catalogue_tools(): void
    {
        $placement = (new \ReflectionClass(ToolsCatalog::class))->getConstant('TOOL_PLACEMENT');
        $this->assertCount(23, $placement);
        $this->assertCount(23, ToolPageLeads::all());

        foreach (array_keys($placement) as $slug) {
            $lead = ToolPageLeads::for($slug);
            $this->assertNotNull($lead, "Missing ToolPageLeads entry for {$slug}");
            $this->assertNotSame('', trim($lead));
            $words = str_word_count($lead);
            $this->assertGreaterThanOrEqual(8, $words, "Lead too short for {$slug}");
            $this->assertLessThanOrEqual(40, $words, "Lead too long for {$slug}");
        }
    }

    public function test_applicable_tool_pages_show_page_lead_before_functional_control(): void
    {
        $user = $this->member('t39-leads@example.com');

        $cases = [
            'tools.psychology.mood' => [
                'lead' => ToolPageLeads::for('daily-mood-check-in'),
                'control' => 'data-mood-card',
            ],
            'tools.psychology.journal' => [
                'lead' => ToolPageLeads::for('digital-journal'),
                'control' => 'name="prompt_key"',
            ],
            'tools.psychology.gratitude' => [
                'lead' => ToolPageLeads::for('gratitude-journal'),
                'control' => 'data-gratitude-card',
            ],
            'tools.career-interest' => [
                'lead' => ToolPageLeads::for('career-interest-test'),
                'control' => 'Start Assessment',
            ],
            'tools.skills-strengths' => [
                'lead' => ToolPageLeads::for('skills-strengths-assessment'),
                'control' => 'Start Assessment',
            ],
            'tools.bmi-bmr' => [
                'lead' => 'Estimate body mass index',
                'control' => 'name="age"',
            ],
            'tools.ps.mindfulness' => [
                'lead' => ToolPageLeads::for('daily-mindfulness-calm'),
                'control' => 'data-ps-wizard',
            ],
            'tools.ps.stress-check' => [
                'lead' => ToolPageLeads::for('parenting-stress-check'),
                'control' => 'data-ps-wizard',
            ],
            'tools.ps.values-finder' => [
                'lead' => ToolPageLeads::for('purpose-values-finder'),
                'control' => 'data-ps-wizard',
            ],
            'tools.tarot.intro' => [
                'lead' => ToolPageLeads::for('tarot-reflection'),
                'control' => 'Start a Reading',
            ],
            'tools.pfm.debt-emi' => [
                'lead' => 'Estimate your monthly loan repayments',
                'control' => 'name="principal"',
            ],
            'tools.pfm.money-planner' => [
                'lead' => 'Plan your monthly income',
                'control' => 'name="monthly_income"',
            ],
            'tools.pfm.financial-goal' => [
                'lead' => ToolPageLeads::for('financial-goal-calculator'),
                'control' => 'name="goal_type"',
            ],
        ];

        foreach ($cases as $route => $expect) {
            $html = $this->actingAs($user, 'user')
                ->get(route($route))
                ->assertOk()
                ->assertSee($expect['lead'], false)
                ->assertSee('data-yk-tool-page-lead', false)
                ->assertSee($expect['control'], false)
                ->getContent();

            $leadPos = stripos($html, 'data-yk-tool-page-lead');
            $controlPos = stripos($html, $expect['control']);
            $this->assertNotFalse($leadPos, "Missing page lead marker on {$route}");
            $this->assertNotFalse($controlPos, "Missing functional control on {$route}");
            $this->assertLessThan($controlPos, $leadPos, "Lead must appear before functional control on {$route}");
        }
    }

    public function test_required_disclaimers_and_start_actions_remain(): void
    {
        $user = $this->member('t39-safety@example.com');

        $bmi = $this->actingAs($user, 'user')->get(route('tools.bmi-bmr'))->assertOk()->getContent();
        $this->assertStringContainsString('not medical advice', $bmi);
        $this->assertStringContainsString('Calculate Results', $bmi);

        $debt = $this->actingAs($user, 'user')->get(route('tools.pfm.debt-emi'))->assertOk()->getContent();
        $this->assertStringContainsString('not financial advice', $debt);
        $this->assertStringContainsString('data-pfm-form', $debt);

        $tarot = $this->actingAs($user, 'user')->get(route('tools.tarot.intro'))->assertOk()->getContent();
        $this->assertStringContainsString('yk-tarot__disclaimer', $tarot);
        $this->assertStringContainsString('yk-tarot__steps', $tarot);
        $this->assertStringContainsString('Start a Reading', $tarot);

        $career = $this->actingAs($user, 'user')->get(route('tools.career-interest'))->assertOk()->getContent();
        $this->assertStringContainsString('does not measure ability', $career);
        $this->assertStringContainsString('Start Assessment', $career);

        $fg = $this->actingAs($user, 'user')->get(route('tools.pfm.financial-goal'))->assertOk()->getContent();
        $this->assertStringContainsString('Educational estimates only', $fg);
        $this->assertStringContainsString('name="goal_type"', $fg);
        $this->assertStringContainsString('data-goal-years-slider', $fg);
    }

    public function test_redundant_career_and_skills_promos_are_removed(): void
    {
        $user = $this->member('t39-trim@example.com');

        $career = $this->actingAs($user, 'user')->get(route('tools.career-interest'))->assertOk()->getContent();
        $this->assertStringNotContainsString('yk-cit__stats', $career);
        $this->assertStringNotContainsString('How it works', $career);
        $this->assertStringNotContainsString('career-interest-3d.webp', $career);
        $this->assertStringContainsString('Start Assessment', $career);

        $skills = $this->actingAs($user, 'user')->get(route('tools.skills-strengths'))->assertOk()->getContent();
        $this->assertStringNotContainsString('yk-ssa__stats', $skills);
        $this->assertStringContainsString('Start Assessment', $skills);

        $mood = $this->actingAs($user, 'user')->get(route('tools.psychology.mood'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Build a healthier and happier you', $mood);
        $this->assertStringContainsString('data-mood-form', $mood);
    }

    public function test_guest_water_nutrition_weight_welcomes_use_short_leads(): void
    {
        $water = $this->get(route('tools.water-intake'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-page-lead', $water);
        $this->assertStringContainsString(ToolPageLeads::for('water-intake-tracker'), $water);
        $this->assertStringContainsString('Log in to start tracking', $water);
        $this->assertStringNotContainsString('yk-water__perks', $water);

        $nutrition = $this->get(route('tools.nutrition'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-page-lead', $nutrition);
        $this->assertStringContainsString(ToolPageLeads::for('calorie-nutrition-tracker'), $nutrition);

        $weight = $this->get(route('tools.weight-journey'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-page-lead', $weight);
        $this->assertStringContainsString(ToolPageLeads::for('weight-journey'), $weight);
        $this->assertStringContainsString('Log in to start', $weight);
    }

    public function test_banner_removal_and_shell_layout_remain_intact(): void
    {
        $user = $this->member('t39-regress@example.com');

        $debt = $this->actingAs($user, 'user')->get(route('tools.pfm.debt-emi'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-tool-shell-ref="debt-emi"', $debt);
        $this->assertStringContainsString('yk-pfm__hero--compact', $debt);
        $this->assertStringNotContainsString('id="pfmHeroBar"', $debt);
        $this->assertStringContainsString('yuktione-tool-shell.css', $debt);

        $mood = $this->actingAs($user, 'user')->get(route('tools.psychology.mood'))->assertOk()->getContent();
        $this->assertStringNotContainsString('yk-mood__hero-art', $mood);
        $this->assertStringContainsString('data-yk-tool-shell="1"', $mood);

        $fg = $this->actingAs($user, 'user')->get(route('tools.pfm.financial-goal'))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-goal-compact-header', $fg);
        $this->assertStringContainsString('yk-goal--single', $fg);
        $this->assertStringNotContainsString('data-yk-tool-shell-ref="debt-emi"', $fg);
    }

    public function test_catalogue_and_homepage_descriptions_are_not_sourced_from_tool_page_leads(): void
    {
        $catalog = file_get_contents(app_path('Services/ToolsCatalog.php'));
        $this->assertIsString($catalog);
        $this->assertStringNotContainsString('ToolPageLeads', $catalog);

        $hero = file_get_contents(resource_path('views/frontend/home/partials/hero.blade.php'));
        $this->assertStringNotContainsString('ToolPageLeads', $hero);
        $this->assertStringNotContainsString('data-yk-tool-page-lead', $hero);

        $toolsPartial = file_get_contents(resource_path('views/frontend/sections/partials/tools-catalog.blade.php'));
        $this->assertStringNotContainsString('ToolPageLeads', $toolsPartial);

        $leadsSource = file_get_contents(app_path('Support/Tools/ToolPageLeads.php'));
        $this->assertStringContainsString('Intentionally separate from ToolsCatalog', $leadsSource);
    }

    public function test_pfm_page_leads_append_educational_disclaimer(): void
    {
        $lead = ToolPageLeads::forPfm('debt-emi-calculator');
        $this->assertStringContainsString('Estimate your monthly loan repayments', $lead);
        $this->assertStringContainsString(ToolPageLeads::PFM_DISCLAIMER, $lead);

        $bmi = ToolPageLeads::forBmi();
        $this->assertStringContainsString('not medical advice', $bmi);
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
            'customer_profiles', 'users', 'nutrition_foods',
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
            'name' => 'Task 39 Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
