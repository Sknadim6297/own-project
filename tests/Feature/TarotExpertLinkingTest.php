<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ExploreCatalog;
use App\Services\ExpertsDiscovery;
use App\Services\TarotDraft;
use App\Services\ToolRegistryRegistrar;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 19 — Tarot Reflection expert CTA links to the Tarot field/service,
 * never theme-remapped Explore categories, and never leaks reading content.
 */
class TarotExpertLinkingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
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
        Schema::create('tarot_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_id', 40)->unique();
            $table->string('card_name');
            $table->string('arcana', 20);
            $table->string('suit', 20)->nullable();
            $table->string('number', 20)->nullable();
            $table->text('upright_meaning');
            $table->text('reversed_meaning');
            $table->string('upright_keywords')->nullable();
            $table->string('reversed_keywords')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('tarot_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('category', 80);
            $table->text('question');
            $table->string('spread_type', 20);
            $table->boolean('reversed_enabled')->default(false);
            $table->text('overall_reflection')->nullable();
            $table->timestamps();
        });
        Schema::create('tarot_reading_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarot_reading_id')->constrained('tarot_readings')->cascadeOnDelete();
            $table->foreignId('tarot_card_id')->constrained('tarot_cards')->restrictOnDelete();
            $table->string('position', 40);
            $table->unsignedTinyInteger('position_order');
            $table->boolean('is_reversed')->default(false);
            $table->text('insight_text')->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->string('specialization')->nullable();
            $table->string('experience')->nullable();
            $table->decimal('starting_price', 10, 2)->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('professional_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->string('category')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
        Schema::create('prof_service_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_service_id');
            $table->unsignedBigInteger('sub_service_id');
        });
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->decimal('final_rate', 10, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->timestamps();
        });
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('availability_id');
            $table->timestamps();
        });
        Schema::create('all_events', function (Blueprint $table) {
            $table->id();
            $table->string('heading')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('event_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->timestamps();
        });
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('category')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        $this->seed(TarotCardSeeder::class);
        app(ToolRegistryRegistrar::class)->register();
    }

    public function test_without_tarot_service_links_to_astrology_tarot_category_only(): void
    {
        DB::table('services')->insert([
            'id' => 10,
            'name' => 'Astrologers',
            'slug' => 'top-online-astrologers-india',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $discovery = app(ExpertsDiscovery::class)->forTarotCategory('love-relationships');

        $this->assertSame('astrology-tarot', $discovery['category']);
        $this->assertNull($discovery['service']);
        $this->assertSame('astrology_tarot_category', $discovery['resolved_via']);
        $this->assertSame('Talk to an Astrology & Tarot Expert', $discovery['label']);
        $this->assertSame(
            route('professionals', ['category' => 'astrology-tarot']),
            $discovery['url']
        );
        $this->assertStringNotContainsString('service=', $discovery['url']);
        $this->assertStringNotContainsString('question=', $discovery['url']);
    }

    public function test_tarot_named_service_is_preferred_and_filters_listing(): void
    {
        DB::table('services')->insert([
            [
                'id' => 10,
                'name' => 'Astrologers',
                'slug' => 'top-online-astrologers-india',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'name' => 'Tarot Readers',
                'slug' => 'online-tarot-reading-consultation',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Career Counsellor',
                'slug' => 'best-career-counsellor-online-india',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->seedExpert(1, 'Tarot Reader Tess', 'Tarot Readers', 20, 'Tarot Readers');
        $this->seedExpert(2, 'Astrologer Ash', 'Astrologers', 10, 'Astrologers');
        $this->seedExpert(3, 'Career Counsellor Cam', 'Career Counsellor', 3, 'Career Counsellor');
        $this->seedExpert(4, 'Inactive Tarot', 'Tarot Readers', 20, 'Tarot Readers', accepted: false);
        $this->seedExpert(5, 'Hidden Tarot', 'Tarot Readers', 20, 'Tarot Readers', active: false);

        $discovery = app(ExpertsDiscovery::class)->forTarotCategory('career-work');
        $this->assertSame('astrology-tarot', $discovery['category']);
        $this->assertSame('online-tarot-reading-consultation', $discovery['service']);
        $this->assertSame('tarot_service_and_category', $discovery['resolved_via']);
        $this->assertSame('Talk to a Tarot Expert', $discovery['label']);
        $this->assertStringContainsString('category=astrology-tarot', $discovery['url']);
        $this->assertStringContainsString('service=online-tarot-reading-consultation', $discovery['url']);
        $this->assertStringNotContainsString('career', strtolower(parse_url($discovery['url'], PHP_URL_QUERY) ?? ''));

        $this->assertSame(
            'online-tarot-reading-consultation',
            app(ExploreCatalog::class)->resolveTarotService()?->slug
        );

        $filtered = $this->get(route('professionals', [
            'category' => 'astrology-tarot',
            'service' => 'online-tarot-reading-consultation',
        ]));
        $filtered->assertOk()
            ->assertSee('Tarot Reader Tess', false)
            ->assertDontSee('Astrologer Ash', false)
            ->assertDontSee('Career Counsellor Cam', false)
            ->assertDontSee('Inactive Tarot', false)
            ->assertDontSee('Hidden Tarot', false);

        $categoryOnly = $this->get(route('professionals', ['category' => 'astrology-tarot']));
        $categoryOnly->assertOk()
            ->assertSee('Tarot Reader Tess', false)
            ->assertSee('Astrologer Ash', false)
            ->assertDontSee('Career Counsellor Cam', false);
    }

    public function test_reading_page_cta_uses_discovery_label_and_preserves_draft_privacy(): void
    {
        DB::table('services')->insert([
            'id' => 20,
            'name' => 'Tarot Readers',
            'slug' => 'online-tarot-reading-consultation',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'Task19 Member',
            'email' => 'task19-cta@example.com',
            'password' => 'password',
        ]);

        $this->completeOneCard($user, 'What private habit may support my growth right now?');

        $page = $this->actingAs($user, 'user')->get(route('tools.tarot.reading'));
        $page->assertOk()
            ->assertSee('Talk to a Tarot Expert')
            ->assertSee('data-tarot-cta="expert"', false)
            ->assertSee('category=astrology-tarot', false)
            ->assertSee('service=online-tarot-reading-consultation', false)
            ->assertDontSee('question=', false)
            ->assertDontSee(rawurlencode('What private habit may support my growth right now?'), false);

        $html = $page->getContent();
        $this->assertSame(1, substr_count($html, 'data-tarot-cta="expert"'));

        $beforeKey = session(TarotDraft::KEY)['completion_key'];
        $this->actingAs($user, 'user')
            ->get(route('professionals', [
                'category' => 'astrology-tarot',
                'service' => 'online-tarot-reading-consultation',
            ]))
            ->assertOk();

        $this->actingAs($user, 'user')->get(route('tools.tarot.reading'))->assertOk();
        $this->assertSame($beforeKey, session(TarotDraft::KEY)['completion_key']);
        $this->assertSame(0, \App\Models\TarotReading::query()->count());
    }

    public function test_empty_tarot_listing_uses_existing_empty_state(): void
    {
        $response = $this->get(route('professionals', [
            'category' => 'astrology-tarot',
            'service' => 'online-tarot-reading-consultation',
        ]));

        // Invalid/missing service slug is cleared by ExploreCatalog filters.
        $response->assertOk();

        $browse = app(ExploreCatalog::class)->browse(
            Request::create('/professionals', 'GET', ['category' => 'astrology-tarot']),
            false
        );
        $this->assertSame(0, $browse['items']->total());
    }

    private function seedExpert(
        int $id,
        string $name,
        string $specialization,
        ?int $serviceId,
        ?string $serviceName,
        bool $accepted = true,
        bool $active = true,
    ): void {
        DB::table('professionals')->insert([
            'id' => $id,
            'name' => $name,
            'email' => "t19-expert{$id}@example.com",
            'status' => $accepted ? 'accepted' : 'pending',
            'active' => $active ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('profiles')->insert([
            'professional_id' => $id,
            'specialization' => $specialization,
            'experience' => '2-4',
            'starting_price' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($serviceId !== null) {
            DB::table('professional_services')->insert([
                'professional_id' => $id,
                'service_id' => $serviceId,
                'service_name' => $serviceName,
                'category' => $serviceName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function completeOneCard(User $user, string $question): void
    {
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'), ['restart' => '1']);
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => 'personal-growth',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.question.store'), [
            'question' => $question,
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.spread.store'), [
            'spread_type' => 'one',
        ]);
        $this->actingAs($user, 'user')->get(route('tools.tarot.draw'))->assertOk();

        $draft = session(TarotDraft::KEY);
        $token = array_key_first($draft['slot_map']);
        $this->actingAs($user, 'user')->post(route('tools.tarot.draw.pick'), [
            'slot_token' => $token,
            'draw_nonce' => $draft['draw_nonce'],
        ]);
        $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'))->assertOk();
    }
}
