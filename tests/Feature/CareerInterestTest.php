<?php

namespace Tests\Feature;

use App\Models\CareerInterestAttempt;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\CareerInterestDraft;
use App\Support\CareerInterest\CareerInterestCatalog;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CareerInterestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_guest_can_complete_flow_and_save_after_login(): void
    {
        $this->storage(true);

        $this->post(route('tools.career-interest.start'))
            ->assertRedirect(route('tools.career-interest.about'));

        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ])->assertRedirect(route('tools.career-interest.category', ['category' => 'realistic']));

        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            $payload = ['answers' => []];
            foreach (CareerInterestCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = $this->sampleAnswer($id);
            }
            $response = $this->post(route('tools.career-interest.category.store', ['category' => $category]), $payload);
            if ($category === 'conventional') {
                $response->assertRedirect(route('tools.career-interest.profile'));
            } else {
                $response->assertRedirect();
            }
        }

        $this->get(route('tools.career-interest.profile'))
            ->assertOk()
            ->assertSee('Your Interest Profile')
            ->assertSee('View Career Suggestions')
            ->assertSee('implementation convention', false);

        $this->get(route('tools.career-interest.suggestions'))
            ->assertOk()
            ->assertSee('Explore career areas that match your interests.')
            ->assertSee('Log in to save');

        $this->post(route('tools.career-interest.save'))
            ->assertRedirect(route('login'));
        $this->assertTrue(session(CareerInterestDraft::KEY)['awaiting_login']);

        $member = $this->member('cit-guest@example.com');
        $this->actingAs($member, 'user');
        Event::dispatch(new Login('user', $member, false));
        app(CareerInterestDraft::class)->claimForCurrentUser((int) $member->id);

        $this->get(route('tools.career-interest.finish'))
            ->assertRedirect();

        $attempt = CareerInterestAttempt::query()->first();
        $this->assertNotNull($attempt);
        $this->assertSame($member->id, $attempt->user_id);
        $this->assertCount(18, $attempt->answers);
        $this->assertNull(session(CareerInterestDraft::KEY));

        $this->get(route('tools.career-interest.result', $attempt))
            ->assertOk()
            ->assertSee('Your Interest Profile');

        $this->get(route('tools.career-interest.history'))
            ->assertOk()
            ->assertSee('Assessment history');
    }

    public function test_validation_rejects_incomplete_about_and_answers(): void
    {
        $this->storage(true);
        $this->post(route('tools.career-interest.start'));

        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
        ])->assertSessionHasErrors(['education', 'situation', 'geography']);

        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ])->assertRedirect();

        $this->post(route('tools.career-interest.category.store', ['category' => 'realistic']), [
            'answers' => ['r1' => 5],
        ])->assertSessionHasErrors();
    }

    public function test_history_is_private_and_delete_is_authorized(): void
    {
        $this->storage(true);
        $owner = $this->member('cit-owner@example.com');
        $other = $this->member('cit-other@example.com');

        $attempt = $this->seedAttempt($owner);

        $this->actingAs($other, 'user')
            ->get(route('tools.career-interest.result', $attempt))
            ->assertForbidden();

        $this->actingAs($other, 'user')
            ->delete(route('tools.career-interest.destroy', $attempt))
            ->assertForbidden();

        $this->assertSame(1, CareerInterestAttempt::query()->count());

        $this->actingAs($owner, 'user')
            ->delete(route('tools.career-interest.destroy', $attempt))
            ->assertRedirect(route('tools.career-interest.history'));

        $this->assertSame(0, CareerInterestAttempt::query()->count());
    }

    public function test_closed_tool_blocks_start(): void
    {
        $this->storage(false);

        $this->get(route('tools.career-interest'))
            ->assertOk()
            ->assertSee('unavailable right now');

        $this->post(route('tools.career-interest.start'))
            ->assertForbidden();
    }

    public function test_draft_ownership_blocks_cross_guest_reuse(): void
    {
        $this->storage(true);
        $this->post(route('tools.career-interest.start'));
        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ]);

        $foreign = session(CareerInterestDraft::KEY);
        $foreign['owner'] = 'guest:someone-else';
        session([CareerInterestDraft::KEY => $foreign]);

        $this->get(route('tools.career-interest.about'))
            ->assertRedirect(route('tools.career-interest'));
    }

    public function test_retake_clears_and_restarts(): void
    {
        $this->storage(true);
        $member = $this->member('cit-retake@example.com');
        $this->actingAs($member, 'user');
        $this->post(route('tools.career-interest.start'));
        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '25-34',
            'education' => 'graduate',
            'situation' => 'working',
            'geography' => 'open-to-both',
        ]);
        $this->assertNotNull(session(CareerInterestDraft::KEY)['about']);

        $this->post(route('tools.career-interest.retake'))
            ->assertRedirect(route('tools.career-interest.about'));
        $this->assertNull(session(CareerInterestDraft::KEY)['about']);
    }

    public function test_start_resumes_existing_draft_without_wiping_answers(): void
    {
        $this->storage(true);
        $this->post(route('tools.career-interest.start'));
        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ]);
        $this->post(route('tools.career-interest.category.store', ['category' => 'realistic']), [
            'answers' => ['r1' => 4, 'r2' => 3, 'r3' => 5],
        ])->assertRedirect(route('tools.career-interest.category', ['category' => 'investigative']));

        $this->post(route('tools.career-interest.start'))
            ->assertRedirect(route('tools.career-interest.category', ['category' => 'investigative']));

        $this->assertSame(4, session(CareerInterestDraft::KEY)['answers']['r1']);
        $this->assertSame('19-24', session(CareerInterestDraft::KEY)['about']['age_group']);
    }

    public function test_publish_migration_makes_tool_launchable_without_duplicating_registry(): void
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

        app(\App\Services\ToolRegistryRegistrar::class)->register();
        $this->assertSame(1, Tool::query()->where('slug', 'career-interest-test')->count());
        $this->assertFalse(Tool::query()->where('slug', 'career-interest-test')->first()->canLaunch());

        (require database_path('migrations/2026_11_03_130000_publish_career_interest_test.php'))->up();

        $tool = Tool::query()->where('slug', 'career-interest-test')->firstOrFail();
        $this->assertTrue($tool->canLaunch());
        $this->assertSame('tools.career-interest', $tool->route_name);
        $this->assertSame(1, Tool::query()->where('slug', 'career-interest-test')->count());

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Career Interest Test');
    }

    private function sampleAnswer(string $id): int
    {
        return match ($id) {
            'i1', 'i2', 'i3', 'e1', 'e2', 'e3' => 5,
            's1', 's2', 's3', 'c1', 'c2', 'c3' => 4,
            'a1', 'a2', 'a3' => 3,
            default => 2,
        };
    }

    private function seedAttempt(User $user): CareerInterestAttempt
    {
        $record = new CareerInterestAttempt([
            'completion_key' => (string) \Illuminate\Support\Str::uuid(),
            'assessment_version' => CareerInterestCatalog::ASSESSMENT_VERSION,
            'scoring_version' => CareerInterestCatalog::SCORING_VERSION,
            'catalogue_version' => 'career-map-v1',
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
            'answers' => array_fill_keys(CareerInterestCatalog::questionIds(), 3),
            'category_totals' => array_fill_keys(CareerInterestCatalog::CATEGORY_ORDER, 9),
            'category_percentages' => array_fill_keys(CareerInterestCatalog::CATEGORY_ORDER, 60),
            'top_categories' => ['investigative'],
            'interpretation' => 'Sample interpretation.',
            'recommendations' => [
                'catalogue_version' => 'career-map-v1',
                'disclaimer' => 'Sample',
                'items' => [
                    [
                        'id' => 'financial-analysis',
                        'title' => 'Financial Analysis',
                        'summary' => 'Sample summary',
                        'why' => 'Sample why',
                        'match_score' => 10,
                    ],
                ],
            ],
            'completed_at' => now(),
        ]);
        $record->user_id = $user->id;
        $record->save();

        return $record;
    }

    private function storage(bool $open): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
        });
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
            $table->text('description')->nullable();
            $table->string('status');
            $table->string('route_name')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });
        Schema::create('career_interest_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('assessment_version', 40);
            $table->string('scoring_version', 40);
            $table->string('catalogue_version', 40);
            $table->string('age_group', 40);
            $table->string('education', 40);
            $table->string('situation', 40);
            $table->string('geography', 40);
            $table->json('answers');
            $table->json('category_totals');
            $table->json('category_percentages');
            $table->json('top_categories');
            $table->text('interpretation')->nullable();
            $table->json('recommendations');
            $table->timestamp('completed_at');
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Career Guidance',
            'slug' => 'career-guidance',
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Career Interest Test',
            'slug' => 'career-interest-test',
            'module_key' => 'career-guidance',
            'description' => 'Discover career directions that match your interests.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.career-interest' : null,
            'is_implemented' => $open,
            'display_order' => 1,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Career Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
