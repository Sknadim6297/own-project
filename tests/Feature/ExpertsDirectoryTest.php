<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpertsDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);

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
            $table->string('education')->nullable();
            $table->string('education2')->nullable();
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
            $table->string('session_type')->nullable();
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
            $table->text('weekdays')->nullable();
            $table->timestamps();
        });
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('availability_id');
            $table->string('weekday')->nullable();
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

        DB::table('professionals')->insert([
            ['id' => 1, 'name' => 'Career Coach Ava', 'email' => 'ava@example.com', 'status' => 'accepted', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Psych Therapist Bo', 'email' => 'bo@example.com', 'status' => 'accepted', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Pending Expert', 'email' => 'pending@example.com', 'status' => 'pending', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Inactive Expert', 'email' => 'inactive@example.com', 'status' => 'accepted', 'active' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('profiles')->insert([
            [
                'professional_id' => 1,
                'specialization' => 'Career coaching',
                'experience' => '2-4',
                'education' => 'MBA Coaching',
                'starting_price' => 800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'professional_id' => 2,
                'specialization' => 'Clinical psychology',
                'experience' => '10+',
                'education' => 'M.Phil Psychology',
                'starting_price' => 1200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'professional_id' => 3,
                'specialization' => 'Psychology',
                'experience' => '4-6',
                'education' => null,
                'starting_price' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'professional_id' => 4,
                'specialization' => 'Therapy',
                'experience' => '6-8',
                'education' => null,
                'starting_price' => 400,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('services')->insert([
            'id' => 1,
            'name' => 'Career Guidance',
            'slug' => 'qa-career-guidance',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('professional_services')->insert([
            'professional_id' => 1,
            'service_id' => 1,
            'service_name' => 'Career Guidance',
            'tags' => 'career,job',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_professionals_page_shows_premium_shell_and_eligible_experts_only(): void
    {
        $response = $this->get(route('professionals'));

        $response->assertOk()
            ->assertSee('Explore Experts', false)
            ->assertSee('yuktione-experts.css', false)
            ->assertSee('All Experts', false)
            ->assertSee('Years of experience', false)
            ->assertSee('Career Coach Ava', false)
            ->assertSee('Psych Therapist Bo', false)
            ->assertDontSee('Pending Expert', false)
            ->assertDontSee('Inactive Expert', false)
            ->assertDontSee('Find the right', false)
            ->assertDontSee('yk-toolpage__hero', false)
            ->assertSee('View Profile', false)
            ->assertSee('Book Consultation', false)
            ->assertSee('2–4 years', false)
            ->assertSee('10+ years', false);
    }

    public function test_category_mental_health_filter_and_empty_state(): void
    {
        $response = $this->get(route('professionals', ['category' => 'mental-health']));

        $response->assertOk()
            ->assertSee('Psych Therapist Bo', false)
            ->assertDontSee('Career Coach Ava', false)
            ->assertSee('aria-selected="true"', false)
            ->assertSee('Mental Health', false);

        $empty = $this->get(route('professionals', [
            'category' => 'mental-health',
            'experience' => '0-2',
        ]));

        $empty->assertOk()
            ->assertSee('No matching experts', false)
            ->assertSee('View all experts', false)
            ->assertDontSee('Career Coach Ava', false)
            ->assertDontSee('Psych Therapist Bo', false);
    }

    public function test_experience_filter_uses_exact_profile_buckets(): void
    {
        $this->get(route('professionals', ['experience' => '2-4']))
            ->assertOk()
            ->assertSee('Career Coach Ava', false)
            ->assertDontSee('Psych Therapist Bo', false);

        $this->get(route('professionals', ['experience' => '10+']))
            ->assertOk()
            ->assertSee('Psych Therapist Bo', false)
            ->assertDontSee('Career Coach Ava', false);

        $this->get(route('professionals', ['experience' => '3-5']))
            ->assertOk()
            ->assertSee('Career Coach Ava', false)
            ->assertSee('Psych Therapist Bo', false);
    }

    public function test_service_filter_and_combined_filters_persist_in_links(): void
    {
        $response = $this->get(route('professionals', [
            'category' => 'career-business',
            'service' => 'qa-career-guidance',
            'experience' => '2-4',
        ]));

        $response->assertOk()
            ->assertSee('Career Coach Ava', false)
            ->assertDontSee('Psych Therapist Bo', false)
            ->assertSee('experience=2-4', false)
            ->assertSee('Clear filters', false);

        $this->get(route('professionals', [
            'category' => 'career-business',
            'service' => 'qa-career-guidance',
            'experience' => '10+',
        ]))->assertOk()
            ->assertSee('No matching experts', false)
            ->assertDontSee('Career Coach Ava', false);
    }
}
