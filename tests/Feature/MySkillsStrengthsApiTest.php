<?php

namespace Tests\Feature;

use App\Models\SkillsStrengthsAttempt;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MySkillsStrengthsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureApiActiveSession::class);
        $this->storage(true);
        Carbon::setTestNow(Carbon::parse('2026-03-15 06:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guests_can_load_options_and_preview_but_cannot_save(): void
    {
        $this->getJson('/api/v1/skills-strengths/options')
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('question_count', 24)
            ->assertJsonCount(24, 'questions')
            ->assertJsonCount(8, 'categories');

        $this->postJson('/api/v1/skills-strengths/preview', ['answers' => $this->answers()])
            ->assertOk()
            ->assertJsonPath('saved', false)
            ->assertJsonPath('result.scoring_version', SkillsStrengthsCatalog::SCORING_VERSION)
            ->assertJsonPath('result.percentages.communication', 100)
            ->assertJsonPath('result.percentages.analytical', 0);

        $this->postJson('/api/v1/my/skills-strengths/complete', [
            'completion_key' => (string) Str::uuid(),
            'answers' => $this->answers(),
        ])->assertUnauthorized();
    }

    public function test_complete_is_idempotent_and_history_is_owned(): void
    {
        $owner = $this->member('ssa-owner@example.com');
        $other = $this->member('ssa-other@example.com');
        Sanctum::actingAs($owner);

        $key = (string) Str::uuid();
        $payload = ['completion_key' => $key, 'answers' => $this->answers()];

        $this->postJson('/api/v1/my/skills-strengths/complete', $payload)
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('attempt.percentages.communication', 100);

        $this->postJson('/api/v1/my/skills-strengths/complete', $payload)
            ->assertOk()
            ->assertJsonPath('created', false);

        $this->assertSame(1, SkillsStrengthsAttempt::query()->count());
        $id = (int) SkillsStrengthsAttempt::query()->value('id');

        $this->getJson('/api/v1/my/skills-strengths/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/my/skills-strengths/attempts/'.$id)->assertForbidden();
        $this->deleteJson('/api/v1/my/skills-strengths/attempts/'.$id)->assertForbidden();

        Sanctum::actingAs($owner);
        $this->deleteJson('/api/v1/my/skills-strengths/attempts/'.$id)
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertSame(0, SkillsStrengthsAttempt::query()->count());
    }

    public function test_validation_and_closed_tool(): void
    {
        Sanctum::actingAs($this->member('ssa-rules@example.com'));

        $bad = $this->answers();
        $bad['c1'] = 9;
        $this->postJson('/api/v1/skills-strengths/preview', ['answers' => $bad])->assertUnprocessable();

        Tool::query()->where('slug', 'skills-strengths-assessment')->update([
            'status' => 'inactive',
            'route_name' => null,
            'is_implemented' => false,
        ]);

        $this->getJson('/api/v1/skills-strengths/options')->assertForbidden();
    }

    /** Communication max (5), all other categories min (1). */
    private function answers(): array
    {
        $answers = [];
        foreach (SkillsStrengthsCatalog::questionIds() as $id) {
            $answers[$id] = str_starts_with($id, 'c') && ! str_starts_with($id, 'cr') ? 5 : 1;
        }

        return $answers;
    }

    private function storage(bool $open): void
    {
        foreach (['skills_strengths_attempts', 'tools', 'tool_categories', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
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
            $table->index(['user_id', 'completed_at']);
        });

        $category = ToolCategory::query()->create(['name' => 'Career', 'slug' => 'career-guidance']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Skills & Strengths Assessment',
            'slug' => 'skills-strengths-assessment',
            'module_key' => 'career',
            'description' => 'Skills self-rating.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.skills-strengths' : null,
            'is_implemented' => $open,
            'display_order' => 2,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Skills API Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
