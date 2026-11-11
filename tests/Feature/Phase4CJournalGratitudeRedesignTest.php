<?php

namespace Tests\Feature;

use App\Models\PsychologyGratitudeEntry;
use App\Models\PsychologyJournalEntry;
use App\Models\User;
use App\Services\GratitudeDraft;
use App\Services\JournalDraft;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4C — Digital Journal + Gratitude Journal premium redesign (UI only).
 */
class Phase4CJournalGratitudeRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_journal_and_gratitude_opt_into_tools_design_system(): void
    {
        foreach (['yuktione-journal.css', 'yuktione-gratitude.css'] as $file) {
            $path = public_path('frontend/assets/css/'.$file);
            $this->assertFileExists($path);
            $css = file_get_contents($path);
            $this->assertStringNotContainsString('--yk-lavender', $css);
            $this->assertStringContainsString('--yk-gold', $css);
            $this->assertStringContainsString('prefers-reduced-motion', $css);
        }

        $this->assertFileExists(public_path('frontend/assets/js/yuktione-journal.js'));
        $this->assertFileExists(public_path('frontend/assets/js/yuktione-gratitude.js'));

        $this->get(route('tools.psychology.journal'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-journal', false)
            ->assertSee('yuktione-journal.js', false)
            ->assertSee('name="prompt_key"', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="body"', false)
            ->assertSee('Save Entry')
            ->assertDontSee('YuktiPoints', false);

        $this->get(route('tools.psychology.gratitude'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-gratitude', false)
            ->assertSee('data-gratitude-steps', false)
            ->assertSee('data-gratitude-carousel', false)
            ->assertSee('yuktione-gratitude.js', false)
            ->assertSee('Card 1 of 3')
            ->assertSee('name="answers[something-good-today]"', false)
            ->assertSee('name="answers[someone-i-appreciate]"', false)
            ->assertSee('name="answers[something-about-myself]"', false)
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('Skip')
            ->assertSee('Finish &amp; Save', false);
    }

    public function test_journal_guest_draft_auth_save_and_history_ownership(): void
    {
        $this->journalStorage();

        $this->post(route('tools.psychology.journal.store'), [
            'prompt_key' => 'my-thoughts',
            'title' => 'Phase 4C note',
            'body' => 'Private journal writing for redesign QA.',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, PsychologyJournalEntry::query()->count());
        $this->assertTrue(session(JournalDraft::KEY)['awaiting_login'] ?? false);

        $member = $this->member('journal4c@example.com');
        $other = $this->member('journal4c-other@example.com');
        $this->actingAs($member, 'user');
        app(\App\Listeners\ClaimJournalDraft::class)->handle(new Login('user', $member, false));
        $this->get(route('tools.psychology.journal.finish'))
            ->assertRedirect(route('tools.psychology.journal.history'));

        $this->assertSame(1, PsychologyJournalEntry::query()->count());
        $entry = PsychologyJournalEntry::query()->first();
        $this->assertSame($member->id, $entry->user_id);
        $this->assertSame('Phase 4C note', $entry->title);

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Phase 4C note');

        $this->actingAs($other, 'user')
            ->get(route('tools.psychology.journal.show', $entry->id))
            ->assertRedirect(route('tools.psychology.journal.history'));
    }

    public function test_gratitude_guided_steps_preserve_three_answers_and_idempotency(): void
    {
        $this->gratitudeStorage();
        $member = $this->member('gratitude4c@example.com');

        $this->actingAs($member, 'user');
        $this->post(route('tools.psychology.gratitude.store'), [
            'answers' => [
                'something-good-today' => 'Sunshine on the balcony.',
                'someone-i-appreciate' => '',
                'something-about-myself' => 'I stayed kind today.',
            ],
        ])->assertRedirect();

        $this->assertSame(1, PsychologyGratitudeEntry::query()->count());
        $record = PsychologyGratitudeEntry::query()->first();
        $this->assertSame('Sunshine on the balcony.', $record->something_good_today);
        $this->assertNull($record->someone_i_appreciate);
        $this->assertSame('I stayed kind today.', $record->something_about_myself);

        $key = $record->completion_key;
        $this->get(route('tools.psychology.gratitude.complete', $key))
            ->assertOk()
            ->assertSee('Beautifully done!')
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Sunshine on the balcony.')
            ->assertSee('Skipped')
            ->assertDontSee('YuktiPoints', false);

        $this->withSession([
            GratitudeDraft::KEY => [
                'answers' => [
                    'something-good-today' => 'Sunshine on the balcony.',
                    'something-about-myself' => 'I stayed kind today.',
                ],
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.gratitude.store'), [
            'answers' => [
                'something-good-today' => 'Sunshine on the balcony.',
                'something-about-myself' => 'I stayed kind today.',
            ],
        ])->assertRedirect(route('tools.psychology.gratitude.complete', $key));

        $this->assertSame(1, PsychologyGratitudeEntry::query()->count());
    }

    public function test_validation_and_mood_regression_remain_intact(): void
    {
        $this->post(route('tools.psychology.journal.store'), [
            'prompt_key' => 'my-thoughts',
            'body' => '',
        ])->assertSessionHasErrors('body');

        $this->post(route('tools.psychology.gratitude.store'), [
            'answers' => [
                'something-good-today' => '',
                'someone-i-appreciate' => '',
                'something-about-myself' => '',
            ],
        ])->assertSessionHasErrors('answers');

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('data-yk-mood', false)
            ->assertSee('How are you feeling today?');

        $this->get(route('tools.psychology.journal'))
            ->assertOk()
            ->assertDontSee('data-yk-mood', false);
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

    private function journalStorage(): void
    {
        $this->membersOnly();
        Schema::create('psychology_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('prompt_key');
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamp('written_at');
            $table->timestamps();
        });
    }

    private function gratitudeStorage(): void
    {
        $this->membersOnly();
        Schema::create('psychology_gratitude_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->text('something_good_today')->nullable();
            $table->text('someone_i_appreciate')->nullable();
            $table->text('something_about_myself')->nullable();
            $table->timestamp('written_at');
            $table->timestamps();
        });
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase 4C Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
