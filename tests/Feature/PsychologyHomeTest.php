<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PsychologyHomeTest extends TestCase
{
    public function test_psychology_home_follows_the_landing_copy_and_stays_closed(): void
    {
        $response = $this->get(route('tools.psychology'));

        $response->assertOk()
            ->assertSee('Psychology')
            ->assertSee('Take a moment for yourself')
            ->assertSee('Small steps towards understanding yourself and feeling better.')
            ->assertSee('How are you feeling today?')
            ->assertSee('Take a quick moment to check in with yourself.')
            ->assertSee('Start Mood Check-in')
            ->assertSee('Your self-care tools')
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Understand and track your emotions.')
            ->assertSee('Digital Journal')
            ->assertSee('Express your thoughts in your own words.')
            ->assertSee('Gratitude Journal')
            ->assertSee('Notice and appreciate the good in your day.')
            ->assertSee('Coming soon')
            ->assertSee('Need someone to talk to?')
            ->assertSee('Explore Psychologists')
            ->assertSee(route('professionals', ['category' => 'mental-health']), false)
            ->assertSee('family=DM+Sans', false)
            ->assertSee('yuktione-psychology.css', false)
            ->assertSee('Yuktione_logo.png', false)
            ->assertSee('<a class="yk-psych__button" href="'.route('tools.psychology.mood').'">Start Mood Check-in</a>', false)
            ->assertDontSee('name="reflection"', false)
            ->assertDontSee('name="journal_body"', false);

        $this->get('/tools/daily-mood-check-in')->assertNotFound();
        $this->get('/tools/digital-journal')->assertNotFound();
        $this->get('/tools/gratitude-journal')->assertNotFound();
    }

    public function test_tool_cards_follow_registry_availability(): void
    {
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
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Psychology',
            'slug' => 'psychology',
            'display_order' => 1,
        ]);

        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Daily Mood Check-in',
            'slug' => 'daily-mood-check-in',
            'module_key' => 'psychology',
            'status' => 'active',
            'route_name' => 'professionals',
            'is_implemented' => true,
            'display_order' => 1,
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Digital Journal',
            'slug' => 'digital-journal',
            'module_key' => 'psychology',
            'status' => 'inactive',
            'route_name' => 'tools.psychology.journal',
            'is_implemented' => true,
            'display_order' => 2,
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Gratitude Journal',
            'slug' => 'gratitude-journal',
            'module_key' => 'psychology',
            'status' => 'coming_soon',
            'is_implemented' => false,
            'display_order' => 3,
        ]);

        $this->get(route('tools.psychology'))
            ->assertOk()
            ->assertSee('<a class="yk-psych__button" href="'.route('professionals').'">Start Mood Check-in</a>', false)
            ->assertSee('<a class="yk-psych__card-action" href="'.route('professionals').'">Start check-in</a>', false)
            ->assertSee('Unavailable')
            ->assertSee('Coming soon')
            ->assertDontSee('Open journal')
            ->assertDontSee('Open gratitude');
    }

    public function test_published_psychology_tools_open_from_home_cards(): void
    {
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
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Psychology',
            'slug' => 'psychology',
            'display_order' => 1,
        ]);

        foreach ([
            ['daily-mood-check-in', 'Daily Mood Check-in', 'tools.psychology.mood', 1],
            ['digital-journal', 'Digital Journal', 'tools.psychology.journal', 2],
            ['gratitude-journal', 'Gratitude Journal', 'tools.psychology.gratitude', 3],
        ] as [$slug, $name, $route, $order]) {
            Tool::query()->create([
                'tool_category_id' => $category->id,
                'name' => $name,
                'slug' => $slug,
                'module_key' => 'psychology',
                'status' => 'active',
                'route_name' => $route,
                'is_implemented' => true,
                'display_order' => $order,
            ]);
        }

        $this->get(route('tools.psychology'))
            ->assertOk()
            ->assertSee('<a class="yk-psych__card-action" href="'.route('tools.psychology.mood').'">Start check-in</a>', false)
            ->assertSee('<a class="yk-psych__card-action" href="'.route('tools.psychology.journal').'">Open journal</a>', false)
            ->assertSee('<a class="yk-psych__card-action" href="'.route('tools.psychology.gratitude').'">Open gratitude</a>', false)
            ->assertDontSee('Coming soon');
    }

    public function test_tools_page_keeps_unpublished_psychology_out_of_the_catalogue(): void
    {
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('No published tools yet')
            ->assertDontSee(route('tools.psychology'), false);

        $this->get(route('tools.index', ['category' => 'mental-health']))
            ->assertOk()
            ->assertSee('Mental Health')
            ->assertSee('No tools match these filters')
            ->assertDontSee('Mental Health &amp; Mindset', false)
            ->assertDontSee(route('tools.psychology'), false);

        $this->get(route('tools.index', ['q' => 'Sleep', 'category' => 'not-a-category']))
            ->assertOk()
            ->assertSee('No tools match these filters')
            ->assertDontSee(route('tools.psychology'), false);
    }
}
