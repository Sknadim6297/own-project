<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExplorePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('mini_heading')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('starting_fees', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('card_image')->nullable();
            $table->timestamps();
        });
        Schema::create('event_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->text('banner_image')->nullable();
            $table->timestamps();
        });
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description_short')->nullable();
            $table->string('image')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('image')->nullable();
            $table->string('category')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        DB::table('professionals')->insert([
            ['id' => 1, 'name' => 'Visible Expert', 'email' => 'visible@example.com', 'status' => 'accepted', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Hidden Expert', 'email' => 'hidden@example.com', 'status' => 'pending', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('profiles')->insert([
            'professional_id' => 1,
            'specialization' => 'Astrology',
            'starting_price' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('all_events')->insert([
            ['id' => 1, 'heading' => 'Visible Programme', 'mini_heading' => 'Career', 'short_description' => 'A published session', 'starting_fees' => 999, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'heading' => 'Hidden Programme', 'mini_heading' => 'Career', 'short_description' => 'Not public', 'starting_fees' => 100, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('blogs')->insert([
            ['id' => 1, 'title' => 'Visible Article', 'description_short' => 'Published guidance', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'title' => 'Hidden Article', 'description_short' => 'Inactive guidance', 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('blog_posts')->insert([
            ['id' => 1, 'blog_id' => 1, 'category' => 'Career', 'content' => 'One two three four', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'blog_id' => 2, 'category' => 'Career', 'content' => 'Hidden words', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_explore_page_shows_only_public_records_and_uses_dm_sans(): void
    {
        $response = $this->get(route('gridlisting'));

        $response->assertOk()
            ->assertSee('Explore what')
            ->assertSee('speaks to you.')
            ->assertSee('Visible Expert')
            ->assertSee('Visible Article')
            ->assertDontSee('Hidden Expert')
            ->assertDontSee('Hidden Programme')
            ->assertDontSee('Hidden Article')
            ->assertSee('family=DM+Sans', false)
            ->assertSee('Tools are listed separately.')
            ->assertSee('Open the Tools catalogue');

        // Programmes stay hidden while config('yuktione.programmes_enabled') is false.
        if (! config('yuktione.programmes_enabled')) {
            $response->assertDontSee('Visible Programme')
                ->assertDontSee('Programmes', false);
        } else {
            $response->assertSee('Visible Programme');
        }
    }

    public function test_explore_search_and_tools_tab_stay_honest(): void
    {
        $this->get(route('gridlisting', ['q' => 'Visible Expert']))
            ->assertOk()
            ->assertSee('Visible Expert')
            ->assertDontSee('Visible Programme');

        $this->get(route('gridlisting', ['type' => 'tools']))
            ->assertOk()
            ->assertSee('Tools live in the dedicated catalogue')
            ->assertSee('Browse Tools');

        $this->get(route('gridlisting', ['category' => 'astrology-tarot']))
            ->assertOk()
            ->assertSee('Visible Expert')
            ->assertDontSee('Visible Programme');
    }
}
