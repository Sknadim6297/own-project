<?php

namespace Tests\Feature;

use App\Models\PsychologyGratitudeEntry;
use App\Models\User;
use App\Services\GratitudeJournal;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class GratitudeJournalCompleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_g2_shows_saved_answers_skipped_cards_and_history_link(): void
    {
        $this->storage();
        $member = $this->member('g2-owner@example.com');
        $entry = $this->entry(
            $member,
            'A warm cup of tea.',
            null,
            'I kept my promise to rest.',
            '2026-11-05 09:15:00'
        );

        $response = $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.complete', $entry->completion_key));

        $response->assertOk()
            ->assertSee('Beautifully done!')
            ->assertSee('You took a moment to appreciate the good in your day.')
            ->assertSee("Today's Gratitude", false)
            ->assertSee('Thursday, 5 November 2026')
            ->assertSee('Something good today')
            ->assertSee('A warm cup of tea.')
            ->assertSee('Someone I appreciate')
            ->assertSee('Skipped')
            ->assertSee('Something about myself')
            ->assertSee('I kept my promise to rest.')
            ->assertSee('Keep growing, one day at a time.')
            ->assertSee('Every little moment of gratitude counts.')
            ->assertSee('Go to Gratitude Home')
            ->assertSee(route('tools.psychology.gratitude'), false)
            ->assertSee('View My Gratitude History')
            ->assertSee(route('tools.psychology.gratitude.history'), false)
            ->assertDontSee('Coming with the next update')
            ->assertSee('yuktione-gratitude.css', false)
            ->assertDontSee('Sunshine example', false);

        $this->assertSame(1, PsychologyGratitudeEntry::query()->count());

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.complete', $entry->completion_key))
            ->assertOk()
            ->assertSee('A warm cup of tea.');

        $this->assertSame(1, PsychologyGratitudeEntry::query()->count());
    }

    public function test_g2_blocks_guests_cross_users_and_missing_entries(): void
    {
        $this->storage();
        $owner = $this->member('g2-private@example.com');
        $other = $this->member('g2-other@example.com');
        $entry = $this->entry($owner, 'Private joy', null, null, '2026-11-04 18:00:00');
        $key = $entry->completion_key;

        $this->get(route('tools.psychology.gratitude.complete', $key))
            ->assertRedirect(route('login'));
        $this->assertSame(route('tools.psychology.gratitude.complete', $key), session('url.intended'));

        $this->actingAs($other, 'user')
            ->get(route('tools.psychology.gratitude.complete', $key))
            ->assertRedirect(route('tools.psychology.gratitude'));

        $this->actingAs($owner, 'user')
            ->get(route('tools.psychology.gratitude.complete', (string) Str::uuid()))
            ->assertRedirect(route('tools.psychology.gratitude'));

        $entry->delete();

        $this->actingAs($owner, 'user')
            ->get(route('tools.psychology.gratitude.complete', $key))
            ->assertRedirect(route('tools.psychology.gratitude'));
    }

    public function test_g2_escapes_user_content(): void
    {
        $this->storage();
        $member = $this->member('g2-xss@example.com');
        $entry = $this->entry(
            $member,
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            null,
            '2026-11-03 12:00:00'
        );

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.complete', $entry->completion_key))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
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
            'name' => 'Gratitude Complete Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }

    private function entry(
        User $user,
        ?string $good,
        ?string $someone,
        ?string $myself,
        string $writtenAt
    ): PsychologyGratitudeEntry {
        $record = new PsychologyGratitudeEntry([
            'completion_key' => (string) Str::uuid(),
            'something_good_today' => $good,
            'someone_i_appreciate' => $someone,
            'something_about_myself' => $myself,
            'written_at' => Carbon::parse($writtenAt, GratitudeJournal::TIMEZONE),
        ]);
        $record->user_id = $user->id;
        $record->save();

        return $record;
    }
}
