<?php

namespace Tests\Feature;

use App\Models\PsychologyJournalEntry;
use App\Models\User;
use App\Support\Psychology\PsychologyCatalog;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalJournalManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_j3_show_displays_entry_details_edit_and_delete_actions(): void
    {
        $this->storage();
        $member = $this->member('manage-show@example.com');
        $entry = $this->entry(
            $member,
            'personal-growth',
            'Quiet wins',
            "Today I noticed progress.\nAnd space to breathe.",
            '2026-10-12 10:00:00'
        );

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.show', $entry->id))
            ->assertOk()
            ->assertSee('Quiet wins')
            ->assertSee('Personal growth')
            ->assertSee('How are you growing or changing lately?')
            ->assertSee('12 Oct 2026', false)
            ->assertSee('Today I noticed progress.')
            ->assertSee('And space to breathe.')
            ->assertSee('Private · Only you can see this entry')
            ->assertSee('Private entry')
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee(route('tools.psychology.journal.edit', $entry->id), false)
            ->assertSee(route('tools.psychology.journal.destroy', $entry->id), false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="DELETE"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('yuktione-journal.css', false);
    }

    public function test_j3_edit_prefills_and_updates_while_preserving_date_and_key(): void
    {
        $this->storage();
        $member = $this->member('manage-edit@example.com');
        $entry = $this->entry(
            $member,
            'my-thoughts',
            'Original title',
            'Original thoughts for the day.',
            '2026-09-01 08:30:00'
        );
        $key = $entry->completion_key;
        $writtenAt = $entry->written_at->toDateTimeString();
        $createdAt = $entry->created_at?->toDateTimeString();

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.edit', $entry->id))
            ->assertOk()
            ->assertSee('Edit journal entry')
            ->assertSee('Save changes')
            ->assertSee('value="my-thoughts"', false)
            ->assertSee('Original title')
            ->assertSee('Original thoughts for the day.')
            ->assertSee('name="_method"', false)
            ->assertSee('value="PUT"', false);

        $this->travel(5)->minutes();

        $this->actingAs($member, 'user')
            ->put(route('tools.psychology.journal.update', $entry->id), [
                'prompt_key' => 'relationships',
                'title' => 'Updated title',
                'body' => 'Updated private thoughts with more detail.',
            ])
            ->assertRedirect(route('tools.psychology.journal.show', $entry->id))
            ->assertSessionHas('status', 'Your journal entry was updated.');

        $entry->refresh();
        $this->assertSame('relationships', $entry->prompt_key);
        $this->assertSame('Updated title', $entry->title);
        $this->assertSame('Updated private thoughts with more detail.', $entry->body);
        $this->assertSame($key, $entry->completion_key);
        $this->assertSame($writtenAt, $entry->written_at->toDateTimeString());
        $this->assertSame($createdAt, $entry->created_at?->toDateTimeString());
        $this->assertTrue($entry->updated_at->greaterThan($entry->created_at));

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.show', $entry->id))
            ->assertOk()
            ->assertSee('Updated title')
            ->assertSee('Relationships')
            ->assertSee('Updated private thoughts with more detail.')
            ->assertSee('Your journal entry was updated.');
    }

    public function test_j3_edit_allows_optional_title_and_requires_thoughts(): void
    {
        $this->storage();
        $member = $this->member('manage-validate@example.com');
        $entry = $this->entry(
            $member,
            'work-career',
            'Career note',
            'Thinking about the week ahead.',
            '2026-10-01 09:00:00'
        );

        $this->actingAs($member, 'user')
            ->from(route('tools.psychology.journal.edit', $entry->id))
            ->put(route('tools.psychology.journal.update', $entry->id), [
                'prompt_key' => 'work-career',
                'title' => '',
                'body' => 'Still thinking about the week ahead.',
            ])
            ->assertRedirect(route('tools.psychology.journal.show', $entry->id));

        $entry->refresh();
        $this->assertNull($entry->title);
        $this->assertSame('Still thinking about the week ahead.', $entry->body);

        $this->actingAs($member, 'user')
            ->from(route('tools.psychology.journal.edit', $entry->id))
            ->put(route('tools.psychology.journal.update', $entry->id), [
                'prompt_key' => 'work-career',
                'title' => 'Kept in form',
                'body' => '',
            ])
            ->assertRedirect(route('tools.psychology.journal.edit', $entry->id))
            ->assertSessionHasErrors('body')
            ->assertSessionHasInput('title', 'Kept in form');

        $this->actingAs($member, 'user')
            ->from(route('tools.psychology.journal.edit', $entry->id))
            ->put(route('tools.psychology.journal.update', $entry->id), [
                'prompt_key' => 'work-career',
                'title' => str_repeat('t', PsychologyCatalog::JOURNAL_TITLE_MAX + 1),
                'body' => str_repeat('b', PsychologyCatalog::JOURNAL_BODY_MAX + 1),
            ])
            ->assertSessionHasErrors(['title', 'body']);

        $entry->refresh();
        $this->assertNull($entry->title);
        $this->assertSame('Still thinking about the week ahead.', $entry->body);
    }

    public function test_j3_delete_requires_confirmation_post_and_redirects_to_history(): void
    {
        $this->storage();
        $member = $this->member('manage-delete@example.com');
        $entry = $this->entry(
            $member,
            'difficult-moments',
            'Hard day',
            'This can go.',
            '2026-08-20 18:00:00'
        );
        $id = $entry->id;

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.show', $id))
            ->assertOk()
            ->assertSee('Delete this entry?')
            ->assertSee('data-journal-delete-open', false)
            ->assertSee('method="post"', false);

        $this->actingAs($member, 'user')
            ->delete(route('tools.psychology.journal.destroy', $id))
            ->assertRedirect(route('tools.psychology.journal.history'))
            ->assertSessionHas('status', 'Your journal entry was deleted.');

        $this->assertNull(PsychologyJournalEntry::query()->find($id));
        $this->assertSame(0, PsychologyJournalEntry::query()->where('user_id', $member->id)->count());

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history'))
            ->assertOk()
            ->assertSee('Your journal entry was deleted.')
            ->assertDontSee('Hard day');

        $this->actingAs($member, 'user')
            ->delete(route('tools.psychology.journal.destroy', $id))
            ->assertRedirect(route('tools.psychology.journal.history'));
    }

    public function test_j3_blocks_cross_user_and_guest_access(): void
    {
        $this->storage();
        $owner = $this->member('manage-owner@example.com');
        $other = $this->member('manage-other@example.com');
        $entry = $this->entry(
            $owner,
            'my-aspirations',
            'Secret hope',
            'Only the owner should read this.',
            '2026-07-04 12:00:00'
        );

        $this->get(route('tools.psychology.journal.show', $entry->id))
            ->assertRedirect(route('login'));
        $this->assertSame(route('tools.psychology.journal.show', $entry->id), session('url.intended'));

        $this->get(route('tools.psychology.journal.edit', $entry->id))
            ->assertRedirect(route('login'));

        $this->put(route('tools.psychology.journal.update', $entry->id), [
            'prompt_key' => 'my-thoughts',
            'title' => 'Hacked',
            'body' => 'Should not save',
        ])->assertRedirect(route('login'));

        $this->delete(route('tools.psychology.journal.destroy', $entry->id))
            ->assertRedirect(route('login'));

        $this->actingAs($other, 'user')
            ->get(route('tools.psychology.journal.show', $entry->id))
            ->assertRedirect(route('tools.psychology.journal.history'));

        $this->actingAs($other, 'user')
            ->get(route('tools.psychology.journal.edit', $entry->id))
            ->assertRedirect(route('tools.psychology.journal.history'));

        $this->actingAs($other, 'user')
            ->put(route('tools.psychology.journal.update', $entry->id), [
                'prompt_key' => 'my-thoughts',
                'title' => 'Hacked',
                'body' => 'Should not save',
            ])
            ->assertRedirect(route('tools.psychology.journal.history'));

        $this->actingAs($other, 'user')
            ->delete(route('tools.psychology.journal.destroy', $entry->id))
            ->assertRedirect(route('tools.psychology.journal.history'));

        $entry->refresh();
        $this->assertSame('Secret hope', $entry->title);
        $this->assertSame('Only the owner should read this.', $entry->body);
        $this->assertSame($owner->id, $entry->user_id);

        $this->actingAs($owner, 'user')
            ->get(route('tools.psychology.journal.show', $entry->id))
            ->assertOk()
            ->assertSee('Secret hope')
            ->assertSee('Only the owner should read this.');
    }

    public function test_j3_detail_escapes_xss_content(): void
    {
        $this->storage();
        $member = $this->member('manage-xss@example.com');
        $entry = $this->entry(
            $member,
            'my-thoughts',
            '<img src=x onerror=alert(1)>',
            '<script>alert("xss")</script>',
            '2026-06-01 10:00:00'
        );

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.show', $entry->id))
            ->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false)
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
        Schema::create('psychology_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('prompt_key', 40)->nullable();
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->timestamp('written_at');
            $table->timestamps();
        });
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Journal Manage Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }

    private function entry(User $user, string $promptKey, ?string $title, string $body, string $writtenAt): PsychologyJournalEntry
    {
        $record = new PsychologyJournalEntry([
            'completion_key' => (string) Str::uuid(),
            'prompt_key' => $promptKey,
            'title' => $title,
            'body' => $body,
            'written_at' => Carbon::parse($writtenAt),
        ]);
        $record->user_id = $user->id;
        $record->save();

        return $record;
    }
}
