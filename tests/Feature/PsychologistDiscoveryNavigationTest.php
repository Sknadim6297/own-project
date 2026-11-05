<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use App\Services\ExploreCatalog;
use App\Services\PsychologyHome;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PsychologistDiscoveryNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_discovery_uses_experts_route_with_mental_health_when_service_missing(): void
    {
        $this->servicesTable();

        $discovery = app(PsychologyHome::class)->psychologistDiscovery();

        $this->assertSame(route('professionals', ['category' => 'mental-health']), $discovery['url']);
        $this->assertSame('mental-health', $discovery['category']);
        $this->assertNull($discovery['service']);
        $this->assertTrue($discovery['mapping_missing']);
        $this->assertSame('category', $discovery['resolved_via']);
        $this->assertStringNotContainsString('gridlisting', $discovery['url']);
    }

    public function test_discovery_prefers_psychologist_service_slug_and_keeps_category(): void
    {
        $this->servicesTable();

        Service::query()->create([
            'name' => 'Career Coaching',
            'slug' => 'qa-career-guidance',
        ]);
        Service::query()->create([
            'name' => 'Best Online Psychologists India',
            'slug' => 'best-online-psychologists-india',
        ]);

        $discovery = app(PsychologyHome::class)->psychologistDiscovery();

        $this->assertSame('best-online-psychologists-india', $discovery['service']);
        $this->assertSame('mental-health', $discovery['category']);
        $this->assertSame('service_and_category', $discovery['resolved_via']);
        $this->assertFalse($discovery['mapping_missing']);
        $this->assertSame(
            route('professionals', [
                'category' => 'mental-health',
                'service' => 'best-online-psychologists-india',
            ]),
            $discovery['url']
        );
    }

    public function test_discovery_does_not_select_unrelated_service(): void
    {
        $this->servicesTable();

        Service::query()->create([
            'name' => 'QA Career Guidance',
            'slug' => 'qa-career-guidance',
        ]);

        $discovery = app(PsychologyHome::class)->psychologistDiscovery();

        $this->assertNull($discovery['service']);
        $this->assertSame('mental-health', $discovery['category']);
        $this->assertSame(
            route('professionals', ['category' => 'mental-health']),
            $discovery['url']
        );
    }

    public function test_experts_listing_marks_mental_health_selected_from_query(): void
    {
        $this->servicesTable();

        $request = \Illuminate\Http\Request::create('/professionals', 'GET', [
            'category' => 'mental-health',
            'type' => 'experts',
        ]);

        $filters = (new \ReflectionClass(ExploreCatalog::class))
            ->getMethod('filters')
            ->invoke(app(ExploreCatalog::class), $request);

        $this->assertSame('mental-health', $filters['category']);
        $this->assertSame('experts', $filters['type']);
        $this->assertSame(
            'Mental Health',
            ExploreCatalog::CATEGORIES[$filters['category']]['label']
        );
    }

    public function test_mood_complete_cta_points_to_filtered_experts(): void
    {
        $this->servicesTable();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
        });
        Schema::create('psychology_mood_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('mood_key', 40);
            $table->json('emotion_keys')->nullable();
            $table->text('reflection')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });

        $member = User::query()->create([
            'name' => 'Mood Completer',
            'email' => 'mood-discover@example.test',
            'password' => 'password-123',
        ]);

        $checkin = new \App\Models\PsychologyMoodCheckin();
        $checkin->forceFill([
            'user_id' => $member->id,
            'completion_key' => '11111111-1111-4111-8111-111111111111',
            'mood_key' => 'good',
            'emotion_keys' => ['calm'],
            'reflection' => 'Feeling steady.',
            'checked_in_at' => now(),
        ])->save();

        $expected = route('professionals', ['category' => 'mental-health']);

        $html = $this->actingAs($member, 'user')
            ->get(route('tools.psychology.mood.complete', $checkin->completion_key))
            ->assertOk()
            ->assertSee('Connect with a Psychologist')
            ->assertSee('yk-psych-connect__cta', false)
            ->assertSee($expected, false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/yk-psych-connect__cta"[^>]*href="[^"]*professionals\?category=mental-health/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/yk-psych-connect__cta"[^>]*href="[^"]*gridlisting/',
            $html
        );
    }

    public function test_explore_catalog_resolves_psychology_category_key(): void
    {
        $this->assertSame('mental-health', app(ExploreCatalog::class)->psychologyCategoryKey());
    }

    private function servicesTable(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    private function professionalsTable(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->default('accepted');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->string('specialization')->nullable();
            $table->string('photo')->nullable();
        });
        Schema::create('professional_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->text('tags')->nullable();
        });
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->decimal('final_rate', 10, 2)->nullable();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->decimal('rating', 3, 1)->nullable();
        });
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id')->nullable();
        });
        Schema::create('all_events', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('approved');
            $table->timestamps();
        });
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('active');
            $table->string('title')->nullable();
            $table->string('description_short')->nullable();
        });
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_id')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }
}
