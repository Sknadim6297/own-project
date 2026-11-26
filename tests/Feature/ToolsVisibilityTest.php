<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Services\ExploreCatalog;
use App\Services\ToolRegistryRegistrar;
use App\Services\ToolsCatalog;
use App\Support\Tools\ToolsSpotlight;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 53 — Tools visibility on category hubs and catalogue navigation.
 */
class ToolsVisibilityTest extends TestCase
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

    public function test_hub_category_tools_match_hub_counts_and_show_intended_cards(): void
    {
        $catalog = app(ToolsCatalog::class);
        $hubCounts = $catalog->hubCategoryCounts();

        $astrologyHtml = $this->renderCategoryToolsPanel('astrology-tarot');
        $this->assertStringContainsString('Tarot Reflection', $astrologyHtml);
        $this->assertStringNotContainsString('No tools yet', $astrologyHtml);
        $this->assertStringContainsString('>'.$hubCounts['astrology-tarot'].'<', $astrologyHtml);
        $this->assertSame(1, $catalog->forExploreCategory('astrology-tarot')->count());
        $this->assertSame(
            ['tarot-reflection'],
            $catalog->forExploreCategory('astrology-tarot')->pluck('slug')->all()
        );

        $parentingHtml = $this->renderCategoryToolsPanel('parenting');
        $this->assertStringContainsString('Parenting Stress Check', $parentingHtml);
        $this->assertStringContainsString('Child Routine Planner', $parentingHtml);
        $this->assertStringContainsString('Parent–Child Conversation Guide', $parentingHtml);
        $this->assertStringNotContainsString('Daily Mood Check-in', $parentingHtml);
        $this->assertStringNotContainsString('Daily Mindfulness &amp; Calm', $parentingHtml);
        $this->assertStringNotContainsString('Daily Mindfulness & Calm', $parentingHtml);
        $this->assertSame(3, $catalog->forExploreCategory('parenting')->count());
        $this->assertSame(3, $hubCounts['parenting']);

        $nutritionHtml = $this->renderCategoryToolsPanel('nutrition-fitness');
        $this->assertStringContainsString('Water Intake Tracker', $nutritionHtml);
        $this->assertStringContainsString('BMI &amp; BMR Calculator', $nutritionHtml);
        $this->assertStringContainsString('Weight Journey', $nutritionHtml);
        $this->assertSame(4, $catalog->forExploreCategory('nutrition-fitness')->count());
        $this->assertSame(4, $hubCounts['nutrition-fitness']);
    }

    public function test_empty_hub_categories_remain_truthful(): void
    {
        $relationships = $this->renderCategoryToolsPanel('relationships');
        $this->assertStringContainsString('No tools yet', $relationships);
        $this->assertStringContainsString('Browse all tools', $relationships);

        $health = $this->renderCategoryToolsPanel('health-wellness');
        $this->assertStringContainsString('No tools yet', $health);

        $this->assertSame(0, app(ToolsCatalog::class)->forExploreCategory('relationships')->count());
        $this->assertSame(0, app(ToolsCatalog::class)->forExploreCategory('health-wellness')->count());
    }

    /**
     * Render the category detail tools panel without expert DB tables.
     * Mirrors CategoryHub::show tools payload + category-show Blade.
     */
    private function renderCategoryToolsPanel(string $slug): string
    {
        $catalog = app(ToolsCatalog::class);
        $tools = $catalog->forExploreCategory($slug);
        $meta = ExploreCatalog::CATEGORIES[$slug];

        return view('frontend.sections.category-show', [
            'category' => [
                'slug' => $slug,
                'label' => \App\Services\CategoryHub::DISPLAY_LABELS[$slug] ?? $meta['label'],
                'tone' => $meta['tone'] ?? 'mint',
                'icon' => $meta['icon'] ?? 'leaf',
                'tagline' => \App\Services\CategoryHub::TAGLINES[$slug] ?? '',
            ],
            'tab' => 'tools',
            'tabs' => [
                'tools' => 'Tools',
                'experts' => 'Experts',
                'programmes' => 'Programmes',
            ],
            'counts' => [
                'tools' => $tools->count(),
                'experts' => 0,
                'programmes' => 0,
            ],
            'tools' => $tools,
            'experts' => collect(),
            'programmes' => collect(),
            'programmesEnabled' => false,
        ])->render();
    }

    public function test_nine_category_catalogue_and_tool_destinations_remain_unchanged(): void
    {
        $this->assertCount(9, ToolsCatalog::CATEGORIES);
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
        ], array_values(array_map(fn (array $meta) => $meta['label'], ToolsCatalog::CATEGORIES)));

        $page = $this->get(route('tools.index'));
        $page->assertOk()
            ->assertSee('Showing 23 tools')
            ->assertSee('Daily Mood Check-in');

        $html = $page->getContent();
        $this->assertStringContainsString('yk-tools__tiles--catalog', $html);
        $this->assertGreaterThanOrEqual(12, substr_count($html, 'yk-tool-tile'));

        $mood = Tool::query()->where('slug', 'daily-mood-check-in')->firstOrFail();
        $card = app(ToolsCatalog::class)->cardFor($mood);
        $this->assertNotNull($card);
        $this->assertSame(route('tools.psychology.mood'), $card['url']);
        $this->assertSame('mental-wellness', $card['category']);

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $tarotCard = app(ToolsCatalog::class)->cardFor($tarot);
        $this->assertNotNull($tarotCard);
        $this->assertSame(route('tools.tarot.intro'), $tarotCard['url']);
    }

    public function test_tools_catalogue_aliases_still_work_for_tools_index(): void
    {
        $this->get(route('tools.index', ['category' => 'parenting']))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Parenting Stress Check');

        $this->get(route('tools.index', ['category' => 'tarot']))
            ->assertOk()
            ->assertSee('Tarot Reflection');

        $this->get(route('tools.index', ['category' => 'business-consulting']))
            ->assertOk()
            ->assertSee('No tools match these filters');
    }

    public function test_homepage_tools_strip_and_solutions_rail_remain_intact(): void
    {
        $toolsHtml = view('frontend.home.partials.tools')->render();
        $this->assertStringContainsString('data-tools-carousel', $toolsHtml);
        $this->assertStringContainsString('data-tools-track', $toolsHtml);
        $this->assertStringContainsString('View All Tools', $toolsHtml);
        $this->assertStringContainsString(route('tools.index'), $toolsHtml);

        $spotlight = ToolsSpotlight::cards(12);
        $this->assertNotEmpty($spotlight);
        $this->assertLessThanOrEqual(12, count($spotlight));
        foreach ($spotlight as $card) {
            $this->assertNotSame('', $card['label']);
            $this->assertNotSame('', $card['url']);
        }

        $catsHtml = view('frontend.home.partials.categories')->render();
        $this->assertStringContainsString('data-yk-solutions-rail', $catsHtml);
        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $catsHtml, $track);
        $this->assertNotEmpty($track[1] ?? null);
        $this->assertSame(11, substr_count($track[1], '<li>'));
        $this->assertCount(11, ExploreCatalog::CATEGORIES);
    }

    public function test_hub_explore_mapping_does_not_use_tools_aliases(): void
    {
        $catalog = app(ToolsCatalog::class);

        // Explore hub keys must not be remapped to tools-catalogue buckets.
        $this->assertSame(
            ['parenting-stress-check', 'child-routine-planner', 'parent-child-conversation-guide'],
            $catalog->forExploreCategory('parenting')->pluck('slug')->values()->all()
        );
        $this->assertSame(
            ['tarot-reflection'],
            $catalog->forExploreCategory('astrology-tarot')->pluck('slug')->values()->all()
        );
        $this->assertSame(
            ['daily-mood-check-in', 'digital-journal', 'gratitude-journal'],
            $catalog->forExploreCategory('mental-health')->pluck('slug')->values()->all()
        );

        // Direct tools-catalogue keys still resolve by tools category.
        $this->assertGreaterThanOrEqual(8, $catalog->forExploreCategory('mental-wellness')->count());
        $this->assertSame(1, $catalog->forExploreCategory('tarot')->count());
    }
}
