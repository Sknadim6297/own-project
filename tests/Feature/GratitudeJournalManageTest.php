<?php

namespace Tests\Feature;

use App\Models\PsychologyGratitudeEntry;
use App\Models\User;
use App\Services\GratitudeJournal;
use App\Support\Psychology\PsychologyCatalog;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class GratitudeJournalManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_g3_history_shows_stats_calendar_and_recent_entries(): void
    {
        $this->storage();
        $member = $this->member('g3-history@example.com');
        $other = $this->member('g3-history-other@example.com');

        $this->entry($member, 'Tea', null, 'Rest', '2026-11-05 09:00:00');
        $this->entry($member, 'Walk', 'Neighbour', null, '2026-11-05 18:00:00');
        $this->entry($member, 'October calm', null, null, '2026-10-12 10:00:00');
        $this->entry($other, 'Hidden', null, null, '2026-11-05 12:00:00');

        $this->get(route('tools.psychology.gratitude.history'))
            ->assertRedirect(route('login'));

        $response = $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.history', ['month' => '2026-11']));

        $response->assertOk()
            ->assertSee('My Gratitude History')
            ->assertSee('Your little moments of gratitude')
            ->assertSee('Total entries')
            ->assertSee('Days this month')
            ->assertSee('November 2026')
            ->assertSee('Recent Entries')
            ->assertSee('5 Nov 2026')
            ->assertSee('12 Oct 2026')
            ->assertSee('Something good today')
            ->assertSee('Someone I appreciate')
            ->assertSee('2 responses')
            ->assertSee('Open Entry')
            ->assertSee('is-marked', false)
            ->assertDontSee('Hidden')
            ->assertSee(route('tools.psychology.gratitude.history', ['month' => '2026-10']), false)
            ->assertSee(route('tools.psychology.gratitude.history', ['month' => '2026-12']), false);

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.history', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertSee('12 Oct 2026');
    }

    public function test_g3_history_empty_state_and_pagination(): void
    {
        $this->storage();
        $member = $this->member('g3-empty@example.com');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.history'))
            ->assertOk()
            ->assertSee('No gratitude entries yet')
            ->assertSee('Write Gratitude')
            ->assertDontSee('View Older Entries');

        foreach (range(1, 9) as $day) {
            $this->entry(
                $member,
                'Moment '.$day,
                null,
                null,
                sprintf('2026-11-%02d 12:00:00', $day)
            );
        }

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.history', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('9 Nov 2026')
            ->assertSee('2 Nov 2026')
            ->assertDontSee('1 Nov 2026')
            ->assertSee('Page 1 of 2')
            ->assertSee('View Older Entries');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.gratitude.history', ['month' => '2026-11', 'page' => 2]))
            ->assertOk()
            ->assertSee('1 Nov 2026');
    }

    public function test_g3_show_edit_update_delete_and_ownership(): void
    {
        $this->storage();
        $owner = $this->member('g3-owner@example.com');
        $other = $this->member('g3-other@example.com');
        $entry = $this->entry($owner, 'Original good', null, 'Original self', '2026-09-01 08:30:00');
        $key = $entry->completion_key;
        $writtenAt = $entry->written_at->toDateTimeString();
        $id = $entry->id;

        $this->actingAs($owner, 'user')
            ->get(route('tools.psychology.gratitude.show', $id))
            ->assertOk()
            ->assertSee('Original good')
            ->assertSee('Skipped')
            ->assertSee('Original self')
            ->assertSee('Private · Only you can see this entry')
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee('value="DELETE"', false);

        $this->actingAs($owner, 'user')
            ->get(route('tools.psychology.gratitude.edit', $id))
            ->assertOk()
            ->assertSee('Edit gratitude entry')
            ->assertSee('Save changes')
            ->assertSee('Original good')
            ->assertSee('Original self')
            ->assertSee('value="PUT"', false);

        $this->travel(5)->minutes();

        $this->actingAs($owner, 'user')
            ->put(route('tools.psychology.gratitude.update', $id), [
                'answers' => [
                    'something-good-today' => 'Updated good',
                    'someone-i-appreciate' => 'Updated person',
                    'something-about-myself' => '',
                ],
            ])
            ->assertRedirect(route('tools.psychology.gratitude.show', $id))
            ->assertSessionHas('status', 'Your gratitude entry was updated.');

        $entry->refresh();
        $this->assertSame('Updated good', $entry->something_good_today);
        $this->assertSame('Updated person', $entry->someone_i_appreciate);
        $this->assertNull($entry->something_about_myself);
        $this->assertSame($key, $entry->completion_key);
        $this->assertSame($writtenAt, $entry->written_at->toDateTimeString());
        $this->assertTrue($entry->updated_at->greaterThan($entry->created_at));

        $this->actingAs($owner, 'user')
            ->from(route('tools.psychology.gratitude.edit', $id))
            ->put(route('tools.psychology.gratitude.update', $id), [
                'answers' => [
                    'something-good-today' => '',
                    'someone-i-appreciate' => '',
                    'something-about-myself' => '',
                ],
            ])
            ->assertRedirect(route('tools.psychology.gratitude.edit', $id))
            ->assertSessionHasErrors('answers');

        $entry->refresh();
        $this->assertSame('Updated good', $entry->something_good_today);

        $this->actingAs($other, 'user')
            ->get(route('tools.psychology.gratitude.show', $id))
            ->assertRedirect(route('tools.psychology.gratitude.history'));

        $this->actingAs($other, 'user')
            ->put(route('tools.psychology.gratitude.update', $id), [
                'answers' => ['something-good-today' => 'Hacked'],
            ])
            ->assertRedirect(route('tools.psychology.gratitude.history'));

        $this->actingAs($other, 'user')
            ->delete(route('tools.psychology.gratitude.destroy', $id))
            ->assertRedirect(route('tools.psychology.gratitude.history'));

        $entry->refresh();
        $this->assertSame('Updated good', $entry->something_good_today);

        $this->actingAs($owner, 'user')
            ->delete(route('tools.psychology.gratitude.destroy', $id))
            ->assertRedirect(route('tools.psychology.gratitude.history'))
            ->assertSessionHas('status', 'Your gratitude entry was deleted.');

        $this->assertNull(PsychologyGratitudeEntry::query()->find($id));

        $this->actingAs($owner, 'user')
            ->delete(route('tools.psychology.gratitude.destroy', $id))
            ->assertRedirect(route('tools.psychology.gratitude.history'));
    }

    public function test_g3_rejects_oversized_answers_on_update(): void
    {
        $this->storage();
        $member = $this->member('g3-limits@example.com');
        $entry = $this->entry($member, 'Keep me', null, null, '2026-08-01 10:00:00');

        $this->actingAs($member, 'user')
            ->put(route('tools.psychology.gratitude.update', $entry->id), [
                'answers' => [
                    'something-good-today' => str_repeat('a', PsychologyCatalog::GRATITUDE_ANSWER_MAX + 1),
                ],
            ])
            ->assertSessionHasErrors('answers.something-good-today');

        $entry->refresh();
        $this->assertSame('Keep me', $entry->something_good_today);
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
            'name' => 'Gratitude Manage Member',
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
