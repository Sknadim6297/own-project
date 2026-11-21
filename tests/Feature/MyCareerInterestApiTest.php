<?php

namespace Tests\Feature;

use App\Models\CareerInterestAttempt;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Support\CareerInterest\CareerInterestCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyCareerInterestApiTest extends TestCase
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
        $this->getJson('/api/v1/career-interest/options')
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('question_count', 18)
            ->assertJsonCount(18, 'questions')
            ->assertJsonCount(6, 'categories');

        $this->postJson('/api/v1/career-interest/preview', $this->assessmentPayload())
            ->assertOk()
            ->assertJsonPath('saved', false)
            ->assertJsonPath('result.scoring_version', CareerInterestCatalog::SCORING_VERSION)
            ->assertJsonPath('result.percentages.investigative', 100);

        $this->postJson('/api/v1/my/career-interest/complete', $this->assessmentPayload(true))
            ->assertUnauthorized();
        $this->getJson('/api/v1/my/career-interest/history')->assertUnauthorized();
    }

    public function test_complete_is_idempotent_and_history_is_owned(): void
    {
        $owner = $this->member('cit-owner@example.com');
        $other = $this->member('cit-other@example.com');
        Sanctum::actingAs($owner);

        $key = (string) Str::uuid();
        $payload = $this->assessmentPayload(true, $key);

        $this->postJson('/api/v1/my/career-interest/complete', $payload)
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('attempt.percentages.investigative', 100)
            ->assertJsonPath('attempt.top_categories.0', 'investigative');

        $this->postJson('/api/v1/my/career-interest/complete', $payload)
            ->assertOk()
            ->assertJsonPath('created', false);

        $this->assertSame(1, CareerInterestAttempt::query()->count());
        $id = (int) CareerInterestAttempt::query()->value('id');

        $this->getJson('/api/v1/my/career-interest/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('attempts.0.id', $id);

        $this->getJson('/api/v1/my/career-interest/attempts/'.$id)
            ->assertOk()
            ->assertJsonPath('attempt.id', $id);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/my/career-interest/attempts/'.$id)->assertForbidden();
        $this->deleteJson('/api/v1/my/career-interest/attempts/'.$id)->assertForbidden();
        $this->assertSame(1, CareerInterestAttempt::query()->count());

        Sanctum::actingAs($owner);
        $this->deleteJson('/api/v1/my/career-interest/attempts/'.$id)
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertSame(0, CareerInterestAttempt::query()->count());
    }

    public function test_validation_and_closed_tool(): void
    {
        Sanctum::actingAs($this->member('cit-rules@example.com'));

        $bad = $this->assessmentPayload(true);
        $bad['answers']['r1'] = 9;
        $this->postJson('/api/v1/my/career-interest/complete', $bad)->assertUnprocessable();

        unset($bad['answers']['r1']);
        $bad['answers']['r1'] = 5;
        $bad['age_group'] = 'not-a-group';
        $this->postJson('/api/v1/career-interest/preview', $bad)->assertUnprocessable();

        Tool::query()->where('slug', 'career-interest-test')->update([
            'status' => 'inactive',
            'route_name' => null,
            'is_implemented' => false,
        ]);

        $this->getJson('/api/v1/career-interest/options')->assertForbidden();
        $this->assertSame(0, CareerInterestAttempt::query()->count());
    }

    private function assessmentPayload(bool $withKey = false, ?string $key = null): array
    {
        $answers = [];
        foreach (CareerInterestCatalog::questionIds() as $id) {
            $answers[$id] = str_starts_with($id, 'i') ? 5 : 1;
        }

        $payload = [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
            'answers' => $answers,
        ];
        if ($withKey) {
            $payload['completion_key'] = $key ?? (string) Str::uuid();
        }

        return $payload;
    }

    private function storage(bool $open): void
    {
        foreach (['career_interest_attempts', 'tools', 'tool_categories', 'users'] as $table) {
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
            $table->index(['user_id', 'completed_at']);
        });

        $category = ToolCategory::query()->create(['name' => 'Career', 'slug' => 'career-guidance']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Career Interest Test',
            'slug' => 'career-interest-test',
            'module_key' => 'career',
            'description' => 'RIASEC interests.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.career-interest' : null,
            'is_implemented' => $open,
            'display_order' => 1,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Career API Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
