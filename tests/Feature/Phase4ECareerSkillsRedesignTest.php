<?php

namespace Tests\Feature;

use App\Models\CareerInterestAttempt;
use App\Models\SkillsStrengthsAttempt;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\CareerInterestDraft;
use App\Services\CareerInterestScorer;
use App\Services\SkillsStrengthsDraft;
use App\Services\SkillsStrengthsScorer;
use App\Support\CareerInterest\CareerInterestCatalog;
use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4E — Career Interest + Skills & Strengths premium redesign
 * (UI + scoring integrity; backends unchanged).
 */
class Phase4ECareerSkillsRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_career_and_skills_opt_into_tools_ds_and_drop_lavender(): void
    {
        $this->storageCareer(true);
        $this->storageSkills(true);

        $citCss = public_path('frontend/assets/css/yuktione-career-interest.css');
        $ssaCss = public_path('frontend/assets/css/yuktione-skills-strengths.css');
        $citJs = public_path('frontend/assets/js/yuktione-career-interest.js');
        $ssaJs = public_path('frontend/assets/js/yuktione-skills-strengths.js');

        $this->assertFileExists($citCss);
        $this->assertFileExists($ssaCss);
        $this->assertFileExists($citJs);
        $this->assertFileExists($ssaJs);

        foreach ([$citCss, $ssaCss] as $path) {
            $css = file_get_contents($path);
            $this->assertStringNotContainsString('--yk-lavender', $css);
            $this->assertStringNotContainsString('157, 129, 219', $css);
            $this->assertStringContainsString('--yk-gold', $css);
            $this->assertStringContainsString('prefers-reduced-motion', $css);
            $this->assertStringContainsString('grid-template-columns: 1fr', $css);
        }

        $this->get(route('tools.career-interest'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-cit', false)
            ->assertSee('Career Interest Test')
            ->assertDontSee('YuktiPoints', false);

        $this->get(route('tools.skills-strengths'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-ssa', false)
            ->assertSee('Skills &amp; Strengths Assessment', false)
            ->assertDontSee('YuktiPoints', false);

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('data-yk-mood', false)
            ->assertDontSee('data-yk-cit', false)
            ->assertDontSee('data-yk-ssa', false);

        $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->assertSee('data-yk-bmi', false)
            ->assertDontSee('data-yk-cit', false);
    }

    public function test_career_interest_renders_six_riasec_categories_and_scale_values(): void
    {
        $this->storageCareer(true);

        $this->post(route('tools.career-interest.start'))
            ->assertRedirect(route('tools.career-interest.about'));

        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ])->assertRedirect(route('tools.career-interest.category', ['category' => 'realistic']));

        $this->assertSame(
            ['realistic', 'investigative', 'artistic', 'social', 'enterprising', 'conventional'],
            CareerInterestCatalog::CATEGORY_ORDER
        );
        $this->assertSame([
            1 => 'Not at all',
            2 => 'A little',
            3 => 'Somewhat',
            4 => 'Very much',
            5 => 'Love it',
        ], CareerInterestCatalog::SCALE);
        $this->assertCount(18, CareerInterestCatalog::questions());

        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            $response = $this->get(route('tools.career-interest.category', ['category' => $category]));
            $response->assertOk()
                ->assertSee('data-yk-tools-ds', false)
                ->assertSee('yuktione-career-interest.js', false)
                ->assertSee('role="radiogroup"', false)
                ->assertSee('fieldset', false)
                ->assertSee(CareerInterestCatalog::categories()[$category]['label']);

            foreach (CareerInterestCatalog::questionIdsFor($category) as $id) {
                $response->assertSee('name="answers['.$id.']"', false);
            }
            foreach ([1, 2, 3, 4, 5] as $value) {
                $response->assertSee('value="'.$value.'"', false);
            }
            $response->assertSee('Not at all')
                ->assertSee('Love it')
                ->assertSee('yk-cit__option-label', false)
                ->assertSee('yk-cit__option-value', false);

            $payload = ['answers' => []];
            foreach (CareerInterestCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = 3;
            }
            $this->post(route('tools.career-interest.category.store', ['category' => $category]), $payload)
                ->assertRedirect();
        }
    }

    public function test_skills_strengths_renders_eight_categories_and_likert_values(): void
    {
        $this->storageSkills(true);

        $this->post(route('tools.skills-strengths.start'))
            ->assertRedirect(route('tools.skills-strengths.intro'));

        $this->get(route('tools.skills-strengths.intro'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Communication')
            ->assertSee('Business Skills');

        $this->post(route('tools.skills-strengths.intro.store'))
            ->assertRedirect(route('tools.skills-strengths.category', ['category' => 'communication']));

        $this->assertSame([
            'communication',
            'analytical',
            'problem-solving',
            'digital',
            'leadership',
            'organisation',
            'creativity',
            'business',
        ], SkillsStrengthsCatalog::CATEGORY_ORDER);
        $this->assertSame([
            1 => 'Strongly Disagree',
            2 => 'Disagree',
            3 => 'Neutral',
            4 => 'Agree',
            5 => 'Strongly Agree',
        ], SkillsStrengthsCatalog::SCALE);
        $this->assertCount(24, SkillsStrengthsCatalog::questions());

        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $response = $this->get(route('tools.skills-strengths.category', ['category' => $category]));
            $response->assertOk()
                ->assertSee('data-yk-tools-ds', false)
                ->assertSee('yuktione-skills-strengths.js', false)
                ->assertSee('role="radiogroup"', false)
                ->assertSee(SkillsStrengthsCatalog::categories()[$category]['label']);

            foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
                $response->assertSee('name="answers['.$id.']"', false);
            }
            foreach ([1, 2, 3, 4, 5] as $value) {
                $response->assertSee('value="'.$value.'"', false);
            }
            $response->assertSee('Strongly Disagree')
                ->assertSee('Strongly Agree')
                ->assertSee('yk-ssa__option-label', false)
                ->assertSee('yk-ssa__option-value', false);

            $payload = ['answers' => []];
            foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = 3;
            }
            $this->post(route('tools.skills-strengths.category.store', ['category' => $category]), $payload)
                ->assertRedirect();
        }
    }

    public function test_career_scoring_convention_unchanged(): void
    {
        $answers = array_fill_keys(CareerInterestCatalog::questionIds(), 3);
        $answers = array_merge($answers, [
            'i1' => 5, 'i2' => 5, 'i3' => 4,
            'e1' => 5, 'e2' => 5, 'e3' => 4,
            's1' => 4, 's2' => 4, 's3' => 3,
            'c1' => 4, 'c2' => 3, 'c3' => 4,
            'a1' => 3, 'a2' => 4, 'a3' => 3,
            'r1' => 2, 'r2' => 3, 'r3' => 2,
        ]);

        $scored = (new CareerInterestScorer)->score($answers);
        $this->assertSame(CareerInterestCatalog::SCORING_VERSION, $scored['scoring_version']);
        $this->assertSame(93, $scored['percentages']['investigative']);
        $this->assertSame(93, $scored['percentages']['enterprising']);
        $this->assertSame(73, $scored['percentages']['social']);
        $this->assertSame(73, $scored['percentages']['conventional']);
        $this->assertSame(67, $scored['percentages']['artistic']);
        $this->assertSame(47, $scored['percentages']['realistic']);
        $this->assertSame(['investigative', 'enterprising'], $scored['top']);
    }

    public function test_skills_scoring_convention_unchanged(): void
    {
        $min = array_fill_keys(SkillsStrengthsCatalog::questionIds(), 1);
        $max = array_fill_keys(SkillsStrengthsCatalog::questionIds(), 5);
        $scorer = new SkillsStrengthsScorer;

        $this->assertSame(0, $scorer->score($min)['percentages']['communication']);
        $this->assertSame(100, $scorer->score($max)['percentages']['communication']);
        $this->assertSame(SkillsStrengthsCatalog::SCORING_VERSION, $scorer->score($max)['scoring_version']);
    }

    public function test_career_guest_flow_auth_save_ownership_and_idempotency(): void
    {
        $this->storageCareer(true);

        $this->post(route('tools.career-interest.start'));
        $this->post(route('tools.career-interest.about.store'), [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ]);

        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            $payload = ['answers' => []];
            foreach (CareerInterestCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = 4;
            }
            $this->post(route('tools.career-interest.category.store', ['category' => $category]), $payload);
        }

        $this->get(route('tools.career-interest.profile'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Your Interest Profile');

        $this->post(route('tools.career-interest.save'))
            ->assertRedirect(route('login'));

        $member = $this->member('cit4e@example.com');
        $other = $this->member('cit4e-other@example.com');
        $this->actingAs($member, 'user');
        Event::dispatch(new Login('user', $member, false));
        app(CareerInterestDraft::class)->claimForCurrentUser((int) $member->id);

        $key = session(CareerInterestDraft::KEY)['completion_key'];
        $this->get(route('tools.career-interest.finish'))->assertRedirect();
        $this->assertSame(1, CareerInterestAttempt::query()->count());
        $attempt = CareerInterestAttempt::query()->first();
        $this->assertSame($member->id, $attempt->user_id);
        $this->assertSame($key, $attempt->completion_key);

        $this->withSession([
            CareerInterestDraft::KEY => [
                'step' => 'profile',
                'about' => [
                    'age_group' => '19-24',
                    'education' => 'undergraduate',
                    'situation' => 'student',
                    'geography' => 'india',
                ],
                'answers' => array_fill_keys(CareerInterestCatalog::questionIds(), 4),
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ])->get(route('tools.career-interest.finish'))->assertRedirect();

        $this->assertSame(1, CareerInterestAttempt::query()->count());

        $this->actingAs($other, 'user')
            ->get(route('tools.career-interest.result', $attempt))
            ->assertForbidden();

        $this->actingAs($member, 'user')
            ->get(route('tools.career-interest.history'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false);
    }

    public function test_skills_guest_flow_auth_save_ownership_and_idempotency(): void
    {
        $this->storageSkills(true);

        $this->post(route('tools.skills-strengths.start'));
        $this->post(route('tools.skills-strengths.intro.store'));
        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $payload = ['answers' => []];
            foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
                $payload['answers'][$id] = 4;
            }
            $this->post(route('tools.skills-strengths.category.store', ['category' => $category]), $payload);
        }

        $this->get(route('tools.skills-strengths.profile'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Your Skills Profile');

        $this->post(route('tools.skills-strengths.save'))
            ->assertRedirect(route('login'));

        $member = $this->member('ssa4e@example.com');
        $other = $this->member('ssa4e-other@example.com');
        $this->actingAs($member, 'user');
        Event::dispatch(new Login('user', $member, false));
        app(SkillsStrengthsDraft::class)->claimForCurrentUser((int) $member->id);

        $key = session(SkillsStrengthsDraft::KEY)['completion_key'];
        $this->get(route('tools.skills-strengths.finish'))->assertRedirect();
        $this->assertSame(1, SkillsStrengthsAttempt::query()->count());
        $attempt = SkillsStrengthsAttempt::query()->first();
        $this->assertSame($member->id, $attempt->user_id);

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

        $this->actingAs($other, 'user')
            ->get(route('tools.skills-strengths.result', $attempt))
            ->assertForbidden();
    }

    public function test_reward_engine_remains_disabled_and_prior_tools_untouched(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));

        $this->storageCareer(true);
        $this->storageSkills(true);

        $this->get(route('tools.psychology.journal'))
            ->assertOk()
            ->assertDontSee('data-yk-cit', false)
            ->assertDontSee('data-yk-ssa', false);

        $this->get(route('tools.psychology.gratitude'))
            ->assertOk()
            ->assertDontSee('data-yk-cit', false);
    }

    private function storageCareer(bool $open): void
    {
        foreach (['career_interest_attempts', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

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

        $category = ToolCategory::query()->create(['name' => 'Career Guidance', 'slug' => 'career-guidance']);
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
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Daily Mood Check-in',
            'slug' => 'daily-mood-check-in',
            'module_key' => 'psychology',
            'description' => 'Mood',
            'status' => 'active',
            'route_name' => 'tools.psychology.mood',
            'is_implemented' => true,
            'display_order' => 2,
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'BMI & BMR Calculator',
            'slug' => 'bmi-bmr-calculator',
            'module_key' => 'nutrition',
            'description' => 'BMI',
            'status' => 'active',
            'route_name' => 'tools.bmi-bmr',
            'is_implemented' => true,
            'display_order' => 3,
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Digital Journal',
            'slug' => 'digital-journal',
            'module_key' => 'psychology',
            'description' => 'Journal',
            'status' => 'active',
            'route_name' => 'tools.psychology.journal',
            'is_implemented' => true,
            'display_order' => 4,
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Gratitude Journal',
            'slug' => 'gratitude-journal',
            'module_key' => 'psychology',
            'description' => 'Gratitude',
            'status' => 'active',
            'route_name' => 'tools.psychology.gratitude',
            'is_implemented' => true,
            'display_order' => 5,
        ]);
    }

    private function storageSkills(bool $open): void
    {
        Schema::dropIfExists('skills_strengths_attempts');

        if (! Schema::hasTable('users')) {
            $this->storageCareer($open);
        }

        if (! Schema::hasTable('tools')) {
            $this->storageCareer($open);
        }

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

        if (! Tool::query()->where('slug', 'skills-strengths-assessment')->exists()) {
            $categoryId = ToolCategory::query()->value('id');
            Tool::query()->create([
                'tool_category_id' => $categoryId,
                'name' => 'Skills & Strengths Assessment',
                'slug' => 'skills-strengths-assessment',
                'module_key' => 'career-guidance',
                'description' => 'Map your skills.',
                'status' => $open ? 'active' : 'coming_soon',
                'route_name' => $open ? 'tools.skills-strengths' : null,
                'is_implemented' => $open,
                'display_order' => 6,
            ]);
        }
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase 4E Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
