<?php

namespace Tests\Feature;

use App\Services\ExploreCatalog;
use App\Services\ToolRegistryRegistrar;
use App\Support\Explore\CategoryImages;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Image fields and the category filter the mobile Home screen reads.
 * All additions are optional fields/params; existing consumers keep working.
 */
class MobileHomeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_tools_carry_the_website_illustration_url(): void
    {
        app(ToolRegistryRegistrar::class)->register();

        $tools = collect($this->getJson('/api/v1/tools')->assertOk()->json('tools'))->keyBy('slug');

        $this->assertNotEmpty($tools);
        $this->assertStringEndsWith(
            '/frontend/assets/img/tools-illustrations/daily-mood-3d.webp',
            $tools['daily-mood-check-in']['image_url'],
        );
        $this->assertStringEndsWith(
            '/frontend/assets/img/tools-illustrations/digital-journal-3d.webp',
            $tools['digital-journal']['image_url'],
        );
        foreach ($tools as $slug => $tool) {
            $this->assertArrayHasKey('image_url', $tool, $slug);
            $this->assertArrayHasKey('icon', $tool, $slug);
        }

        $this->getJson('/api/v1/tools/daily-mood-check-in')
            ->assertOk()
            ->assertJsonPath('tool.image_url', $tools['daily-mood-check-in']['image_url']);
    }

    public function test_expert_categories_carry_the_website_home_photos(): void
    {
        $categories = collect($this->getJson('/api/v1/experts')->assertOk()->json('categories'))->keyBy('slug');

        $this->assertSame(array_keys(ExploreCatalog::CATEGORIES), $categories->keys()->all());
        $this->assertStringEndsWith('/frontend/assets/img/home-cats/cat-stones.jpg', $categories['mental-health']['image_url']);
        foreach ($categories as $slug => $category) {
            $this->assertSame(CategoryImages::url($slug), $category['image_url'], $slug);
            $this->assertNotNull($category['image_url'], $slug);
        }
        $this->assertNull(CategoryImages::url('not-a-category'));
    }

    public function test_experts_can_be_filtered_by_explore_category(): void
    {
        $this->expert('Career Coach', 'Career Guidance');
        $this->expert('Calm Therapist', 'Psychologist');
        $this->expert('Hidden Pending', 'Career Guidance', status: 'pending');

        $names = fn (string $query) => collect($this->getJson('/api/v1/experts'.$query)->assertOk()->json('experts'))
            ->pluck('name')->sort()->values()->all();

        $this->assertSame(['Calm Therapist', 'Career Coach'], $names(''));
        $this->assertSame(['Career Coach'], $names('?category=career-business'));
        $this->assertSame(['Calm Therapist'], $names('?category=mental-health'));
        $this->assertSame([], $names('?category=not-a-category'));
        $this->assertSame(['Career Coach'], $names('?category=career-business&q=coach'));
    }

    public function test_programme_card_image_url_is_null_when_the_file_is_missing(): void
    {
        DB::table('all_events')->insert([
            'heading' => 'Evening calm circle',
            'mini_heading' => 'Workshop',
            'short_description' => 'A guided group session.',
            'date' => '2026-12-01',
            'status' => 'approved',
            'show_on_homepage' => true,
            'card_image' => 'events/missing-file.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/programme-events?homepage=1')
            ->assertOk()
            ->assertJsonPath('events.0.card_image', 'events/missing-file.jpg')
            ->assertJsonPath('events.0.card_image_url', null);
    }

    public function test_programme_events_can_be_filtered_by_explore_category(): void
    {
        $event = fn (string $heading, string $mini, string $status = 'approved') => DB::table('all_events')->insert([
            'heading' => $heading,
            'mini_heading' => $mini,
            'short_description' => 'An approved session.',
            'date' => '2026-12-01',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $event('Career clarity workshop', 'Workshop');
        $event('Evening circle', 'Mindset therapy');
        $event('Draft career talk', 'Workshop', status: 'pending');

        $headings = fn (string $query) => collect($this->getJson('/api/v1/programme-events'.$query)->assertOk()->json('events'))
            ->pluck('heading')->sort()->values()->all();

        $this->assertSame(['Career clarity workshop', 'Evening circle'], $headings(''));
        $this->assertSame(['Career clarity workshop'], $headings('?category=career-business'));
        $this->assertSame(['Evening circle'], $headings('?category=mental-health'));
        $this->assertSame([], $headings('?category=parenting'));
        $this->assertSame([], $headings('?category=not-a-category'));
    }

    private function expert(string $name, string $specialization, string $status = 'accepted'): int
    {
        $id = DB::table('professionals')->insertGetId([
            'name' => $name,
            'status' => $status,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('profiles')->insert([
            'professional_id' => $id,
            'specialization' => $specialization,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function createSchema(): void
    {
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
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->string('specialization')->nullable();
            $table->string('experience')->nullable();
            $table->string('education')->nullable();
            $table->string('education2')->nullable();
            $table->text('bio')->nullable();
            $table->string('starting_price')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });
        Schema::create('professional_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->unsignedTinyInteger('rating');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->decimal('final_rate', 10, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('all_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id')->nullable();
            $table->string('heading')->nullable();
            $table->string('mini_heading')->nullable();
            $table->text('short_description')->nullable();
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->string('starting_fees')->nullable();
            $table->string('status')->nullable();
            $table->boolean('show_on_homepage')->default(false);
            $table->string('card_image')->nullable();
            $table->timestamps();
        });
        Schema::create('event_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->timestamps();
        });
    }
}
