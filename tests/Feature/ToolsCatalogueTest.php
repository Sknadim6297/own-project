<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\ToolRegistryRegistrar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ToolsCatalogueTest extends TestCase
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
    }

    public function test_psychology_tools_are_published_in_the_catalogue(): void
    {
        foreach ([
            'daily-mood-check-in' => 'tools.psychology.mood',
            'digital-journal' => 'tools.psychology.journal',
            'gratitude-journal' => 'tools.psychology.gratitude',
        ] as $slug => $route) {
            $tool = Tool::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame('active', $tool->status, $slug);
            $this->assertTrue($tool->is_implemented, $slug);
            $this->assertSame($route, $tool->route_name, $slug);
            $this->assertTrue($tool->canLaunch(), $slug);
        }

        $stillComingSoon = [
            'career-interest-test',
            'skills-strengths-assessment',
            'bmi-bmr-calculator',
            'water-intake-tracker',
            'weight-journey',
            'calorie-nutrition-tracker',
        ];
        $this->assertSame(
            count($stillComingSoon),
            Tool::query()
                ->whereIn('slug', $stillComingSoon)
                ->where('status', 'coming_soon')
                ->where('is_implemented', false)
                ->whereNull('route_name')
                ->count()
        );

        $category = ToolCategory::query()->firstOrFail();
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Private History',
            'slug' => 'private-history',
            'module_key' => 'psychology',
            'description' => 'A saved reflection must stay off the catalogue.',
            'access_type' => 'free',
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.psychology.mood.history',
            'display_order' => 9,
        ]);

        $page = $this->get(route('tools.index'));

        $page->assertOk()
            ->assertSee('Showing 3 tools')
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Understand and track your emotions.')
            ->assertSee('Digital Journal')
            ->assertSee('Express your thoughts in your own words.')
            ->assertSee('Gratitude Journal')
            ->assertSee('Notice and appreciate the good in your day.')
            ->assertSee('Free')
            ->assertSee('Start Check-in')
            ->assertSee('Open')
            ->assertSee('href="'.route('tools.psychology.mood').'"', false)
            ->assertSee('href="'.route('tools.psychology.journal').'"', false)
            ->assertSee('href="'.route('tools.psychology.gratitude').'"', false)
            ->assertDontSee('No published tools yet')
            ->assertDontSee('Career Interest Test')
            ->assertDontSee('Skills & Strengths Assessment')
            ->assertDontSee('BMI & BMR Calculator')
            ->assertDontSee('Water Intake Tracker')
            ->assertDontSee('Weight Journey')
            ->assertDontSee('Calorie & Nutrition Tracker')
            ->assertDontSee('Private History')
            ->assertDontSee('saved reflection')
            ->assertDontSee(route('tools.psychology.mood.history'), false)
            ->assertDontSee(route('tools.psychology.mood.complete', '11111111-1111-4111-8111-111111111111'), false)
            ->assertDontSee('name="reflection"', false);

        $this->get(route('tools.psychology.mood'))->assertOk()->assertSee('How are you feeling today?');
        $this->get(route('tools.psychology.journal'))->assertOk()->assertSee('Digital Journal');
        $this->get(route('tools.psychology.gratitude'))->assertOk()->assertSee('Gratitude Journal');

        $this->get('/tools/daily-mood-check-in')->assertNotFound();
        $this->get('/tools/digital-journal')->assertNotFound();
        $this->get('/tools/gratitude-journal')->assertNotFound();
    }

    public function test_search_and_mental_health_category_find_the_published_tools(): void
    {
        $this->get(route('tools.index', ['q' => 'Mood']))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Showing 1 tool');

        $this->get(route('tools.index', ['q' => 'Journal']))
            ->assertOk()
            ->assertSee('Digital Journal')
            ->assertSee('Gratitude Journal')
            ->assertSee('Showing 2 tools');

        $this->get(route('tools.index', ['category' => 'mental-health']))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Digital Journal')
            ->assertSee('Gratitude Journal')
            ->assertSee('(3)', false);

        $this->get(route('tools.index', ['q' => 'Sleep']))
            ->assertOk()
            ->assertSee('No tools match these filters')
            ->assertDontSee('Daily Mood Check-in');

        $this->get(route('tools.index', ['category' => 'astrology-tarot']))
            ->assertOk()
            ->assertSee('No tools match these filters')
            ->assertDontSee('Daily Mood Check-in');
    }
}
