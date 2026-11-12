<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Services\ExploreCatalog;
use App\Services\ExpertsDiscovery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryIntegrityFilteringTest extends TestCase
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

        DB::table('services')->insert([
            ['id' => 3, 'name' => 'Career Counsellor', 'slug' => 'best-career-counsellor-online-india', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Psychologists', 'slug' => 'best-online-psychologists-india', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Nutritionists', 'slug' => 'best-dieticians-nutritionists-online-india', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'Physical Trainers', 'slug' => 'best-physical-trainers-online-india', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Astrologers', 'slug' => 'top-online-astrologers-india', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => 'Influencers', 'slug' => 'top-online-influencer-marketing-platfrom-india', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Estate and Will Planners', 'slug' => 'estate-and-will-planners', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedExpert(1, 'Career Counsellor Ann', 'Career Counsellor', 3, 'Career Counsellor');
        $this->seedExpert(2, 'Psychologist Bea', 'Psychologists', 6, 'Psychologists');
        $this->seedExpert(3, 'Trainer Cam', 'Physical Trainers', 9, 'Physical Trainers');
        $this->seedExpert(4, 'Influencer Deb', 'Influencers', 12, 'Influencers');
        $this->seedExpert(5, 'Estate Planner Eli', 'Estate and Will Planners', 5, 'Estate and Will Planners');
        $this->seedExpert(6, 'Conflict Pro', 'Psychologists', 3, 'Career Counsellor');
        $this->seedExpert(7, 'Spec Only Fay', 'Psychologists', null, null);
    }

    private function seedExpert(int $id, string $name, string $specialization, ?int $serviceId, ?string $serviceName): void
    {
        DB::table('professionals')->insert([
            'id' => $id,
            'name' => $name,
            'email' => "expert{$id}@example.com",
            'status' => 'accepted',
            'active' => 1,
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

    public function test_catalogue_services_map_to_expected_explore_categories(): void
    {
        $catalog = app(ExploreCatalog::class);

        $this->assertSame('career-business', $catalog->categoryKeyForText('Career Counsellor best-career-counsellor-online-india'));
        $this->assertSame('mental-health', $catalog->categoryKeyForText('Psychologists best-online-psychologists-india'));
        $this->assertSame('nutrition-fitness', $catalog->categoryKeyForText('Physical Trainers best-physical-trainers-online-india'));
        $this->assertSame('lifestyle', $catalog->categoryKeyForText('Influencers top-online-influencer-marketing-platfrom-india'));
        $this->assertSame('money-finance', $catalog->categoryKeyForText('Estate and Will Planners estate-and-will-planners'));
    }

    public function test_each_category_filter_excludes_unrelated_service_professionals(): void
    {
        $matrix = [
            'mental-health' => ['Psychologist Bea', 'Spec Only Fay'],
            'career-business' => ['Career Counsellor Ann', 'Conflict Pro'],
            'nutrition-fitness' => ['Trainer Cam'],
            'lifestyle' => ['Influencer Deb'],
            'money-finance' => ['Estate Planner Eli'],
            'astrology-tarot' => [],
        ];

        foreach ($matrix as $category => $expectedNames) {
            $response = $this->get(route('professionals', [
                'type' => 'experts',
                'category' => $category,
            ]));
            $response->assertOk();

            foreach ($expectedNames as $name) {
                $response->assertSee($name, false);
            }

            $forbidden = [
                'mental-health' => ['Career Counsellor Ann', 'Trainer Cam', 'Influencer Deb', 'Estate Planner Eli'],
                'career-business' => ['Psychologist Bea', 'Spec Only Fay', 'Trainer Cam'],
                'nutrition-fitness' => ['Career Counsellor Ann', 'Psychologist Bea', 'Influencer Deb'],
                'lifestyle' => ['Career Counsellor Ann', 'Psychologist Bea', 'Trainer Cam'],
                'money-finance' => ['Career Counsellor Ann', 'Psychologist Bea', 'Influencer Deb'],
                'astrology-tarot' => ['Career Counsellor Ann', 'Psychologist Bea', 'Trainer Cam', 'Influencer Deb', 'Estate Planner Eli'],
            ][$category];

            foreach ($forbidden as $name) {
                $response->assertDontSee($name, false);
            }
        }
    }

    public function test_conflicting_specialization_defers_to_linked_service_category(): void
    {
        $this->get(route('professionals', ['category' => 'mental-health']))
            ->assertOk()
            ->assertDontSee('Conflict Pro', false);

        $this->get(route('professionals', ['category' => 'career-business']))
            ->assertOk()
            ->assertSee('Conflict Pro', false);
    }

    public function test_experts_discovery_uses_shared_explore_catalog_mapping(): void
    {
        $discovery = app(ExpertsDiscovery::class)->career();

        $this->assertSame('career-business', $discovery['category']);
        $this->assertNotNull($discovery['service']);
        $this->assertSame('best-career-counsellor-online-india', $discovery['service']);
    }

    public function test_admin_service_update_syncs_matching_specialization(): void
    {
        $professional = Professional::with('profile')->findOrFail(2);
        $this->assertSame('Psychologists', $professional->profile->specialization);

        $controller = app(\App\Http\Controllers\Admin\ManageProfessionalController::class);
        $method = new \ReflectionMethod($controller, 'syncProfileSpecializationFromService');
        $method->setAccessible(true);
        $method->invoke($controller, $professional, 'Nutritionists', 'Psychologists');

        $this->assertSame('Nutritionists', $professional->fresh('profile')->profile->specialization);
    }

    public function test_admin_does_not_overwrite_custom_multi_topic_specialization(): void
    {
        $professional = Professional::with('profile')->findOrFail(6);
        $custom = 'Psychologists';
        $this->assertSame($custom, $professional->profile->specialization);

        $controller = app(\App\Http\Controllers\Admin\ManageProfessionalController::class);
        $method = new \ReflectionMethod($controller, 'syncProfileSpecializationFromService');
        $method->setAccessible(true);
        // Previous service name was Career Counsellor, but specialization is Psychologists (conflict).
        $method->invoke($controller, $professional, 'Nutritionists', 'Career Counsellor');

        $this->assertSame($custom, $professional->fresh('profile')->profile->specialization);
    }
}
