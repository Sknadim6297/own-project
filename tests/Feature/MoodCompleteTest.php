<?php

namespace Tests\Feature;

use App\Models\PsychologyMoodCheckin;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\MoodCheckinDraft;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodCompleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_save_and_skip_create_one_private_check_in_each(): void
    {
        $this->storage();
        $member = $this->member('member@example.com');
        $other = $this->member('other@example.com');

        $this->actingAs($member, 'user');
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['calm', 'grateful']]);
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => "A quiet morning.\nनमस्ते",
        ])->assertRedirect();

        $saved = PsychologyMoodCheckin::query()->first();
        $this->assertNotNull($saved);
        $this->assertSame($member->id, $saved->user_id);
        $this->assertSame('good', $saved->mood_key);
        $this->assertSame(['calm', 'grateful'], $saved->emotion_keys);
        $this->assertSame("A quiet morning.\nनमस्ते", $saved->reflection);
        $this->assertNull(session(MoodCheckinDraft::KEY));

        $this->get(route('tools.psychology.mood.complete', $saved->completion_key))
            ->assertOk()
            ->assertSee('Check-in complete!')
            ->assertSee('Thank you for taking a moment for yourself.')
            ->assertSee('Your mood today')
            ->assertSee('Good')
            ->assertSee('Your emotions')
            ->assertSee('Calm')
            ->assertSee('Grateful')
            ->assertSee('Your reflection')
            ->assertSee('A quiet morning.', false)
            ->assertSee('नमस्ते', false)
            ->assertSee('Would you like to talk to someone?')
            ->assertSee('Connect with a Psychologist')
            ->assertSee('Go to Psychology Home')
            ->assertSee('View Mood History')
            ->assertSee(route('tools.psychology.mood.history'), false)
            ->assertDontSee('Coming soon', false);

        $this->get(route('tools.psychology.mood.complete', $saved->completion_key))
            ->assertOk();
        $this->assertSame(1, PsychologyMoodCheckin::query()->count());

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertRedirect(route('tools.psychology.mood'));

        $this->actingAs($other, 'user')
            ->get(route('tools.psychology.mood.complete', $saved->completion_key))
            ->assertRedirect(route('tools.psychology'));

        $this->actingAs($member, 'user');
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'sad']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['lonely']]);
        $this->post(route('tools.psychology.mood.reflection.store'), ['action' => 'skip'])
            ->assertRedirect();

        $skipped = PsychologyMoodCheckin::query()->where('mood_key', 'sad')->first();
        $this->assertNotNull($skipped);
        $this->assertNull($skipped->reflection);
        $this->assertSame(['lonely'], $skipped->emotion_keys);
        $this->get(route('tools.psychology.mood.complete', $skipped->completion_key))
            ->assertOk()
            ->assertSee('Sad')
            ->assertSee('No reflection added.');
        $this->assertSame(2, PsychologyMoodCheckin::query()->count());
    }

    public function test_a_leftover_draft_from_another_owner_does_not_reuse_their_check_in(): void
    {
        $this->storage();
        $first = $this->member('first-key@example.com');
        $second = $this->member('second-key@example.com');

        $this->actingAs($first, 'user');
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['calm']]);
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'First private note',
        ])->assertRedirect();

        $saved = PsychologyMoodCheckin::query()->first();
        $key = $saved->completion_key;

        $this->actingAs($second, 'user')->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'sad',
                'emotions' => ['lonely'],
                'reflection' => 'First private note',
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => true,
                'owner' => 'user:'.$first->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.mood.store'), ['mood' => 'sad'])
            ->assertRedirect(route('tools.psychology.mood.emotions'));

        $draft = session(MoodCheckinDraft::KEY);
        $this->assertNotSame($key, $draft['completion_key']);
        $this->assertSame('user:'.$second->id, $draft['owner']);
        $this->assertFalse($draft['awaiting_login']);
        $this->assertNull($draft['reflection']);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['lonely']]);
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Second note',
        ])->assertRedirect();

        $this->assertSame(2, PsychologyMoodCheckin::query()->count());
        $this->assertSame('First private note', $saved->fresh()->reflection);
        $this->assertSame($first->id, $saved->fresh()->user_id);
        $secondSaved = PsychologyMoodCheckin::query()->where('user_id', $second->id)->first();
        $this->assertNotNull($secondSaved);
        $this->assertSame('Second note', $secondSaved->reflection);
        $this->assertNotSame($key, $secondSaved->completion_key);
    }

    public function test_a_repeated_save_does_not_create_a_second_row(): void
    {
        $this->storage();
        $member = $this->member('repeat@example.com');
        $this->actingAs($member, 'user');
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'stressed']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['worried']]);
        $key = session(MoodCheckinDraft::KEY)['completion_key'];

        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Once',
        ])->assertRedirect(route('tools.psychology.mood.complete', $key));

        $this->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'stressed',
                'emotions' => ['worried'],
                'reflection' => 'Once',
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Once',
        ])->assertRedirect(route('tools.psychology.mood.complete', $key));

        $this->assertSame(1, PsychologyMoodCheckin::query()->count());
    }

    public function test_invalid_drafts_are_rejected_and_a_failed_save_keeps_the_note(): void
    {
        $this->storage();
        $member = $this->member('invalid@example.com');
        $this->actingAs($member, 'user')->withSession([
            'psychology.mood_guest' => 'guest-token',
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'not-a-mood',
                'emotions' => ['calm', 'calm', 'tired', 'quiet', 'unsure', 'worried', 'angry', 'sad'],
                'reflection' => 'Keep me',
                'reflection_held' => true,
                'completion_key' => '11111111-1111-4111-8111-111111111111',
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Keep me',
        ])->assertRedirect(route('tools.psychology.mood'));

        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
        Schema::drop('psychology_mood_checkins');

        $this->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => 'Still here',
                'reflection_held' => true,
                'completion_key' => '22222222-2222-4222-8222-222222222222',
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Still here',
        ])->assertRedirect(route('tools.psychology.mood.reflection'))
            ->assertSessionHasErrors('reflection');

        $this->assertSame('Still here', session(MoodCheckinDraft::KEY)['reflection']);
    }

    public function test_a_guest_must_sign_in_and_the_draft_is_saved_once(): void
    {
        $this->storage();
        $member = $this->member('guest-flow@example.com');

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'neutral']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['thoughtful']]);
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => '<script>alert(1)</script>',
        ])->assertRedirect(route('login'));

        $this->assertSame(route('tools.psychology.mood.finish'), session('url.intended'));
        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
        $this->assertTrue(session(MoodCheckinDraft::KEY)['awaiting_login']);

        Event::dispatch(new Login('user', $member, false));
        $this->actingAs($member, 'user');

        $this->get(route('tools.psychology.mood.finish'))
            ->assertRedirect();
        $this->assertSame(1, PsychologyMoodCheckin::query()->count());

        $record = PsychologyMoodCheckin::query()->first();
        $this->assertSame($member->id, $record->user_id);
        $this->assertSame('<script>alert(1)</script>', $record->reflection);

        $this->get(route('tools.psychology.mood.complete', $record->completion_key))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get(route('tools.psychology.mood.finish'))
            ->assertRedirect(route('tools.psychology'));
        $this->assertSame(1, PsychologyMoodCheckin::query()->count());

        $this->get(route('tools.psychology.mood.complete', '33333333-3333-4333-8333-333333333333'))
            ->assertRedirect(route('tools.psychology'));
    }

    public function test_a_closed_tool_does_not_save(): void
    {
        $this->storage();
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
            $table->string('status');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });
        $category = ToolCategory::query()->create(['name' => 'Psychology', 'slug' => 'psychology']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Daily Mood Check-in',
            'slug' => 'daily-mood-check-in',
            'module_key' => 'psychology',
            'status' => 'unavailable',
            'is_implemented' => false,
        ]);

        $member = $this->member('closed@example.com');
        $this->actingAs($member, 'user')->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => 'Private',
                'reflection_held' => true,
                'completion_key' => '44444444-4444-4444-8444-444444444444',
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Private',
        ])->assertForbidden();

        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
    }

    private function storage(): void
    {
        $this->membersOnly();
        Schema::create('psychology_mood_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('mood_key');
            $table->json('emotion_keys');
            $table->text('reflection')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });
    }

    private function membersOnly(): void
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
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Mood Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
