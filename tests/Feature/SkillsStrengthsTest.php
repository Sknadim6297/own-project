<?php

namespace Tests\Feature;

use App\Models\SkillsStrengthsAttempt;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\SkillsStrengthsDraft;
use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SkillsStrengthsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_guest_completes_flow_saves_after_login_and_sees_profile(): void
    {
        $this->storage(true);

        $this->post(route('tools.skills-strengths.start'))
            ->assertRedirect(route('tools.skills-strengths.intro'));

        $this->get(route('tools.skills-strengths.intro'))
            ->assertOk()
            ->assertSee('Eight areas')
            ->assertSee('Communication')
            ->assertSee('Business Skills');

        $this->post(route('tools.skills-strengths.intro.store'))
            ->assertRedirect(route('tools.skills-strengths.category', ['category' => 'communication']));

        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $payload = ['answers' => []];
            foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = $this->sampleAnswer($id);
            }
            $response = $this->post(route('tools.skills-strengths.category.store', ['category' => $category]), $payload);
            if ($category === 'business') {
                $response->assertRedirect(route('tools.skills-strengths.profile'));
            } else {
                $response->assertRedirect();
            }
        }

        $this->get(route('tools.skills-strengths.profile'))
            ->assertOk()
            ->assertSee('Your Skills Profile')
            ->assertSee('Key strengths')
            ->assertSee('Areas to build')
            ->assertSee('Retake Assessment')
            ->assertSee('Connect to an Expert')
            ->assertSee('Explore Career Insights')
            ->assertSee(route('professionals'), false)
            ->assertSee(route('blog.index'), false)
            ->assertSee('implementation convention', false)
            ->assertSee('Log in to save');

        $this->post(route('tools.skills-strengths.save'))
            ->assertRedirect(route('login'));
        $this->assertTrue(session(SkillsStrengthsDraft::KEY)['awaiting_login']);

        $member = $this->member('ssa-guest@example.com');
        $this->actingAs($member, 'user');
        Event::dispatch(new Login('user', $member, false));
        app(SkillsStrengthsDraft::class)->claimForCurrentUser((int) $member->id);

        $this->get(route('tools.skills-strengths.finish'))->assertRedirect();

        $attempt = SkillsStrengthsAttempt::query()->first();
        $this->assertNotNull($attempt);
        $this->assertSame($member->id, $attempt->user_id);
        $this->assertCount(24, $attempt->answers);
        $this->assertNull(session(SkillsStrengthsDraft::KEY));

        $this->get(route('tools.skills-strengths.result', $attempt))
            ->assertOk()
            ->assertSee('Your Skills Profile');

        $this->get(route('tools.skills-strengths.history'))
            ->assertOk()
            ->assertSee('Assessment history');
    }

    public function test_validation_and_back_navigation_preserve_answers(): void
    {
        $this->storage(true);
        $this->post(route('tools.skills-strengths.start'));
        $this->post(route('tools.skills-strengths.intro.store'));

        $this->post(route('tools.skills-strengths.category.store', ['category' => 'communication']), [
            'answers' => ['c1' => 5],
        ])->assertSessionHasErrors();

        $this->post(route('tools.skills-strengths.category.store', ['category' => 'communication']), [
            'answers' => ['c1' => 5, 'c2' => 4, 'c3' => 3],
        ])->assertRedirect(route('tools.skills-strengths.category', ['category' => 'analytical']));

        $this->get(route('tools.skills-strengths.category', ['category' => 'communication']))
            ->assertOk()
            ->assertSee('checked', false);

        $this->assertSame(5, session(SkillsStrengthsDraft::KEY)['answers']['c1']);
    }

    public function test_ownership_delete_and_history_privacy(): void
    {
        $this->storage(true);
        $owner = $this->member('ssa-owner@example.com');
        $other = $this->member('ssa-other@example.com');
        $attempt = $this->seedAttempt($owner);

        $this->actingAs($other, 'user')
            ->get(route('tools.skills-strengths.result', $attempt))
            ->assertForbidden();

        $this->actingAs($other, 'user')
            ->delete(route('tools.skills-strengths.destroy', $attempt))
            ->assertForbidden();
        $this->assertSame(1, SkillsStrengthsAttempt::query()->count());

        $this->actingAs($owner, 'user')
            ->delete(route('tools.skills-strengths.destroy', $attempt))
            ->assertRedirect(route('tools.skills-strengths.history'));
        $this->assertSame(0, SkillsStrengthsAttempt::query()->count());
    }

    public function test_closed_tool_blocks_start(): void
    {
        $this->storage(false);
        $this->get(route('tools.skills-strengths'))
            ->assertOk()
            ->assertSee('unavailable right now');
        $this->post(route('tools.skills-strengths.start'))->assertForbidden();
    }

    public function test_draft_ownership_and_resume(): void
    {
        $this->storage(true);
        $this->post(route('tools.skills-strengths.start'));
        $this->post(route('tools.skills-strengths.intro.store'));
        $this->post(route('tools.skills-strengths.category.store', ['category' => 'communication']), [
            'answers' => ['c1' => 4, 'c2' => 3, 'c3' => 5],
        ]);

        $this->post(route('tools.skills-strengths.start'))
            ->assertRedirect(route('tools.skills-strengths.category', ['category' => 'analytical']));
        $this->assertSame(4, session(SkillsStrengthsDraft::KEY)['answers']['c1']);

        $foreign = session(SkillsStrengthsDraft::KEY);
        $foreign['owner'] = 'guest:someone-else';
        session([SkillsStrengthsDraft::KEY => $foreign]);
        $this->get(route('tools.skills-strengths.intro'))
            ->assertRedirect(route('tools.skills-strengths'));
    }

    public function test_retake_resets_draft(): void
    {
        $this->storage(true);
        $this->post(route('tools.skills-strengths.start'));
        $this->post(route('tools.skills-strengths.intro.store'));
        $this->assertTrue(session(SkillsStrengthsDraft::KEY)['intro_seen']);

        $this->post(route('tools.skills-strengths.retake'))
            ->assertRedirect(route('tools.skills-strengths.intro'));
        $this->assertFalse(session(SkillsStrengthsDraft::KEY)['intro_seen']);
    }

    public function test_duplicate_completion_key_returns_same_attempt(): void
    {
        $this->storage(true);
        $member = $this->member('ssa-dup@example.com');
        $this->actingAs($member, 'user');
        $this->completeAssessment();

        $key = session(SkillsStrengthsDraft::KEY)['completion_key'];
        $this->post(route('tools.skills-strengths.save'))->assertRedirect();
        $this->assertSame(1, SkillsStrengthsAttempt::query()->count());

        $this->withSession([
            SkillsStrengthsDraft::KEY => [
                'step' => 'questions',
                'intro_seen' => true,
                'answers' => array_fill_keys(SkillsStrengthsCatalog::questionIds(), 4),
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.skills-strengths.save'))->assertRedirect();

        $this->assertSame(1, SkillsStrengthsAttempt::query()->count());
    }

    public function test_publish_migration_activates_without_duplicating_registry(): void
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
        $this->assertSame(1, Tool::query()->where('slug', 'skills-strengths-assessment')->count());
        $this->assertFalse(Tool::query()->where('slug', 'skills-strengths-assessment')->first()->canLaunch());
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'bmi-bmr-calculator')->value('status'));

        (require database_path('migrations/2026_11_03_150000_publish_skills_strengths_assessment.php'))->up();

        $tool = Tool::query()->where('slug', 'skills-strengths-assessment')->firstOrFail();
        $this->assertTrue($tool->canLaunch());
        $this->assertSame('tools.skills-strengths', $tool->route_name);
        $this->assertSame(1, Tool::query()->where('slug', 'skills-strengths-assessment')->count());
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'bmi-bmr-calculator')->value('status'));

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Skills & Strengths Assessment');
    }

    public function test_programmes_remain_disabled(): void
    {
        $this->assertFalse(config('yuktione.programmes_enabled'));
    }

    private function completeAssessment(): void
    {
        $this->post(route('tools.skills-strengths.start'));
        $this->post(route('tools.skills-strengths.intro.store'));
        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $payload = ['answers' => []];
            foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = 4;
            }
            $this->post(route('tools.skills-strengths.category.store', ['category' => $category]), $payload);
        }
    }

    private function sampleAnswer(string $id): int
    {
        return match (true) {
            str_starts_with($id, 'c') && ! str_starts_with($id, 'cr') => 5,
            str_starts_with($id, 'cr') => 5,
            str_starts_with($id, 'p') => 4,
            str_starts_with($id, 'l'), str_starts_with($id, 'b') => 2,
            default => 3,
        };
    }

    private function seedAttempt(User $user): SkillsStrengthsAttempt
    {
        $record = new SkillsStrengthsAttempt([
            'completion_key' => (string) \Illuminate\Support\Str::uuid(),
            'assessment_version' => SkillsStrengthsCatalog::ASSESSMENT_VERSION,
            'scoring_version' => SkillsStrengthsCatalog::SCORING_VERSION,
            'answers' => array_fill_keys(SkillsStrengthsCatalog::questionIds(), 3),
            'category_totals' => array_fill_keys(SkillsStrengthsCatalog::CATEGORY_ORDER, 9),
            'category_percentages' => array_fill_keys(SkillsStrengthsCatalog::CATEGORY_ORDER, 50),
            'strengths' => [['key' => 'communication', 'label' => 'Communication', 'percent' => 50]],
            'develop' => [['key' => 'leadership', 'label' => 'Leadership', 'percent' => 50]],
            'summary' => 'Sample summary.',
            'meaning' => 'Sample meaning.',
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
        Schema::create('skills_strengths_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('assessment_version', 40);
            $table->string('scoring_version', 40);
            $table->json('answers');
            $table->json('category_totals');
            $table->json('category_percentages');
            $table->json('strengths');
            $table->json('develop');
            $table->text('summary')->nullable();
            $table->text('meaning')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Career Guidance',
            'slug' => 'career-guidance',
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Skills & Strengths Assessment',
            'slug' => 'skills-strengths-assessment',
            'module_key' => 'career-guidance',
            'description' => 'Map your strengths and transferable skills.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.skills-strengths' : null,
            'is_implemented' => $open,
            'display_order' => 2,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Skills Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
