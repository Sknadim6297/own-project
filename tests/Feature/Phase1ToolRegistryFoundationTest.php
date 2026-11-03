<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\ToolRegistryRegistrar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase1ToolRegistryFoundationTest extends TestCase
{
    private const PHASE1_SLUGS = [
        'career-interest-test',
        'skills-strengths-assessment',
        'bmi-bmr-calculator',
        'water-intake-tracker',
        'weight-journey',
        'calorie-nutrition-tracker',
    ];

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
    }

    public function test_phase1_tools_register_idempotently_and_stay_unpublished(): void
    {
        $registrar = app(ToolRegistryRegistrar::class);
        $registrar->register();
        $registrar->register();

        $this->assertSame(3, ToolCategory::query()->count());
        $this->assertSame(9, Tool::query()->count());
        $this->assertSame(1, Tool::query()->where('slug', 'daily-mood-check-in')->count());
        $this->assertSame(count(self::PHASE1_SLUGS), Tool::query()->whereIn('slug', self::PHASE1_SLUGS)->count());

        foreach (self::PHASE1_SLUGS as $slug) {
            $tool = Tool::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame('coming_soon', $tool->status, $slug);
            $this->assertFalse($tool->is_implemented, $slug);
            $this->assertNull($tool->route_name, $slug);
            $this->assertFalse($tool->canLaunch(), $slug);
            $this->assertFalse($tool->publiclyAvailable()->whereKey($tool->id)->exists(), $slug);
        }

        $mood = Tool::query()->where('slug', 'daily-mood-check-in')->firstOrFail();
        $this->assertSame('active', $mood->status);
        $this->assertTrue($mood->is_implemented);
        $this->assertSame('tools.psychology.mood', $mood->route_name);

        $this->assertTrue(ToolCategory::query()->where('slug', 'career-guidance')->exists());
        $this->assertTrue(ToolCategory::query()->where('slug', 'nutrition')->exists());
        $this->assertTrue(ToolCategory::query()->where('slug', 'psychology')->exists());

        $page = $this->get(route('tools.index'));
        $page->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Start Check-in')
            ->assertDontSee('Career Interest Test')
            ->assertDontSee('Skills & Strengths Assessment')
            ->assertDontSee('BMI & BMR Calculator')
            ->assertDontSee('Water Intake Tracker')
            ->assertDontSee('Weight Journey')
            ->assertDontSee('Calorie & Nutrition Tracker');

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('How are you feeling today?');
    }
}
