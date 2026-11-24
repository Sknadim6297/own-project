<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Services\ToolRegistryRegistrar;
use App\Services\ToolsCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NineToolsCategoriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        // Mirror production: implemented tools need launch routes to appear in the catalogue.
        $routes = [
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

        foreach ($routes as $slug => $routeName) {
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

    public function test_tools_catalogue_exposes_exactly_nine_client_categories_in_order(): void
    {
        $labels = array_values(array_map(
            fn (array $meta) => $meta['label'],
            ToolsCatalog::CATEGORIES
        ));

        $this->assertSame([
            'Mental Wellness',
            'Career Counselling',
            'Nutrition',
            'Fitness',
            'Astrology',
            'Tarot',
            'Business Consulting',
            'Tax Matters',
            'Investments',
        ], $labels);
        $this->assertCount(9, ToolsCatalog::CATEGORIES);

        $html = $this->get(route('tools.index'))->assertOk()->getContent();
        $this->assertTrue(preg_match('/<nav[^>]*yk-tools__cats[^>]*>(.*?)<\/nav>/s', $html, $chipNav) === 1);
        $chipsHtml = $chipNav[1];
        $positions = [];
        foreach ($labels as $label) {
            $pos = strpos($chipsHtml, $label);
            $this->assertNotFalse($pos, $label.' missing from tools category chips');
            $positions[] = $pos;
        }
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Category chips must appear in client order');
    }

    public function test_published_tools_map_into_requested_categories_without_loss(): void
    {
        $catalog = app(ToolsCatalog::class);
        $counts = $catalog->categoryCounts();
        $listed = array_sum($counts);

        $this->assertGreaterThanOrEqual(9, $listed);

        $this->assertGreaterThan(0, $counts['mental-wellness']);
        $this->assertGreaterThan(0, $counts['career-counselling']);
        $this->assertSame(0, $counts['astrology']);
        $this->assertSame(0, $counts['business-consulting']);
        $this->assertSame(0, $counts['tax-matters']);

        $mood = Tool::query()->where('slug', 'daily-mood-check-in')->firstOrFail();
        $card = $catalog->cardFor($mood);
        $this->assertNotNull($card);
        $this->assertSame('mental-wellness', $card['category']);
        $this->assertSame(route('tools.psychology.mood'), $card['url']);

        foreach (['digital-journal', 'gratitude-journal', 'career-interest-test', 'tarot-reflection'] as $slug) {
            $tool = Tool::query()->where('slug', $slug)->first();
            if (! $tool) {
                continue;
            }
            $toolCard = $catalog->cardFor($tool);
            $this->assertNotNull($toolCard, $slug.' should remain catalogue-visible');
            $this->assertArrayHasKey($toolCard['category'], ToolsCatalog::CATEGORIES);
        }
    }

    public function test_empty_categories_remain_visible_with_truthful_zero_counts(): void
    {
        $page = $this->get(route('tools.index', ['category' => 'business-consulting']));
        $page->assertOk()
            ->assertSee('Business Consulting')
            ->assertSee('(0)', false)
            ->assertSee('No tools match these filters')
            ->assertDontSee('Daily Mood Check-in');

        $this->get(route('tools.index', ['category' => 'astrology']))
            ->assertOk()
            ->assertSee('Astrology')
            ->assertSee('No tools match these filters');

        $this->get(route('tools.index', ['category' => 'tax-matters']))
            ->assertOk()
            ->assertSee('Tax Matters')
            ->assertSee('No tools match these filters');
    }

    public function test_legacy_compound_category_aliases_still_return_tools(): void
    {
        $catalog = app(ToolsCatalog::class);

        foreach ([
            'water-intake-tracker' => 'tools.water-intake',
            'bmi-bmr-calculator' => 'tools.bmi-bmr',
            'tarot-reflection' => 'tools.tarot.intro',
        ] as $slug => $routeName) {
            Tool::query()->where('slug', $slug)->update([
                'status' => 'active',
                'is_implemented' => true,
                'route_name' => $routeName,
            ]);
        }

        $request = \Illuminate\Http\Request::create('/tools', 'GET', ['category' => 'nutrition-fitness']);
        $names = collect($catalog->browse($request)['items']->items())->pluck('name')->all();
        $this->assertContains('Water Intake Tracker', $names);
        $this->assertContains('BMI & BMR Calculator', $names);

        $tarotNames = collect($catalog->browse(
            \Illuminate\Http\Request::create('/tools', 'GET', ['category' => 'astrology-tarot'])
        )['items']->items())->pluck('name')->all();
        $this->assertContains('Tarot Reflection', $tarotNames);
    }

    public function test_solutions_nav_lists_nine_tool_categories(): void
    {
        $html = $this->get(route('tools.index'))->assertOk()->getContent();

        foreach (ToolsCatalog::CATEGORIES as $slug => $meta) {
            $this->assertStringContainsString(
                'category='.$slug,
                $html,
                'Missing tools filter link for '.$slug
            );
            $this->assertStringContainsString($meta['label'], $html);
        }

        $this->assertStringNotContainsString('Mental Health tools', $html);
        $this->assertStringNotContainsString('Nutrition &amp; Fitness tools', $html);
    }
}
