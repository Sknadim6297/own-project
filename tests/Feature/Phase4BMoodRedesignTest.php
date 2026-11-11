<?php

namespace Tests\Feature;

use App\Models\PsychologyMoodCheckin;
use App\Models\User;
use App\Services\MoodCheckinDraft;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4B — Daily Mood Check-in premium redesign (UI only; backend unchanged).
 */
class Phase4BMoodRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_mood_pages_opt_into_tools_design_system_and_progress(): void
    {
        $css = public_path('frontend/assets/css/yuktione-mood.css');
        $js = public_path('frontend/assets/js/yuktione-mood.js');
        $this->assertFileExists($css);
        $this->assertFileExists($js);
        $moodCss = file_get_contents($css);
        $this->assertIsString($moodCss);
        $this->assertStringNotContainsString('--yk-lavender', $moodCss);
        $this->assertStringContainsString('--yk-navy', $moodCss);
        $this->assertStringContainsString('--yk-gold', $moodCss);
        $this->assertStringContainsString('prefers-reduced-motion', $moodCss);

        $psychCss = file_get_contents(public_path('frontend/assets/css/yuktione-psychology.css'));
        $this->assertIsString($psychCss);
        $this->assertStringNotContainsString('--yk-lavender', $psychCss);
        $this->assertStringNotContainsString('9d81db', $psychCss);
        $this->assertStringContainsString('--yk-gold', $psychCss);

        $page = $this->get(route('tools.psychology.mood'));
        $page->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('yk-mood__progress', false)
            ->assertSee('aria-label="Check-in progress"', false)
            ->assertSee('yuktione-mood.js', false)
            ->assertSee('yuktione-tools-ds.css', false)
            ->assertSee('value="very-happy"', false)
            ->assertSee('value="good"', false)
            ->assertSee('value="neutral"', false)
            ->assertSee('value="sad"', false)
            ->assertSee('value="stressed"', false)
            ->assertSee('<button class="yk-mood__continue" type="submit" data-mood-continue disabled>Continue</button>', false);

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $emotions = $this->get(route('tools.psychology.mood.emotions'));
        $emotions->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('yk-mood__progress', false)
            ->assertSee('Step 2 of 4', false)
            ->assertDontSee('reflection', false)
            ->assertSee('class="yk-emotion__input"', false);
        $this->assertSame(15, substr_count($emotions->getContent(), 'class="yk-emotion__input"'));

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['calm']]);
        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Step 3 of 4', false)
            ->assertSee('name="reflection"', false)
            ->assertSee('Save Check-in')
            ->assertSee('Skip');
    }

    public function test_guest_draft_and_authenticated_save_remain_unchanged(): void
    {
        $this->storage();

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'neutral']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['thoughtful']]);
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'A private guest note',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
        $this->assertTrue(session(MoodCheckinDraft::KEY)['awaiting_login']);
        $this->assertSame('A private guest note', session(MoodCheckinDraft::KEY)['reflection']);

        $member = $this->member('phase4b@example.com');
        $this->actingAs($member, 'user');
        $this->get(route('tools.psychology.mood.finish'))->assertRedirect();

        $this->assertSame(1, PsychologyMoodCheckin::query()->count());
        $record = PsychologyMoodCheckin::query()->first();
        $this->assertSame($member->id, $record->user_id);
        $this->assertSame('neutral', $record->mood_key);
        $this->assertSame(['thoughtful'], $record->emotion_keys);
        $this->assertSame('A private guest note', $record->reflection);

        $this->get(route('tools.psychology.mood.complete', $record->completion_key))
            ->assertOk()
            ->assertSee('Check-in complete!')
            ->assertSee('Explore tools')
            ->assertSee(route('tools.index'), false)
            ->assertDontSee('YuktiPoints', false)
            ->assertDontSee('earned', false);

        $key = $record->completion_key;
        $this->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'neutral',
                'emotions' => ['thoughtful'],
                'reflection' => 'A private guest note',
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'A private guest note',
        ])->assertRedirect(route('tools.psychology.mood.complete', $key));

        $this->assertSame(1, PsychologyMoodCheckin::query()->count());
    }

    public function test_history_stays_owner_scoped_and_other_tools_are_untouched(): void
    {
        $this->storage();
        $owner = $this->member('owner4b@example.com');
        $other = $this->member('other4b@example.com');

        $owned = new PsychologyMoodCheckin([
            'completion_key' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'mood_key' => 'good',
            'emotion_keys' => ['calm'],
            'reflection' => 'Mine only',
            'checked_in_at' => now(),
        ]);
        $owned->user_id = $owner->id;
        $owned->save();

        $foreign = new PsychologyMoodCheckin([
            'completion_key' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'mood_key' => 'sad',
            'emotion_keys' => ['lonely'],
            'reflection' => 'Not for owner',
            'checked_in_at' => now(),
        ]);
        $foreign->user_id = $other->id;
        $foreign->save();

        $this->actingAs($owner, 'user')
            ->get(route('tools.psychology.mood.history'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Mine only')
            ->assertDontSee('Not for owner');

        $this->get(route('tools.psychology.journal'))
            ->assertOk()
            ->assertDontSee('data-yk-mood', false)
            ->assertDontSee('yk-mood__progress', false);

        $journalCss = public_path('frontend/assets/css/yuktione-journal.css');
        if (is_file($journalCss)) {
            $this->assertStringNotContainsString('data-yk-tools-ds', file_get_contents($journalCss) ?: '');
        }
    }

    public function test_validation_still_rejects_invalid_mood_and_over_limit_emotions(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'Feeling great'])
            ->assertSessionHasErrors('mood');

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $this->post(route('tools.psychology.mood.emotions.store'), [
            'emotions' => ['happy', 'calm', 'excited', 'loved', 'grateful', 'tired', 'anxious'],
        ])->assertSessionHasErrors('emotions');
    }

    private function storage(): void
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

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase 4B Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
