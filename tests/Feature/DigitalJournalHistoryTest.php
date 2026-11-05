<?php

namespace Tests\Feature;

use App\Models\PsychologyJournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalJournalHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_history_requires_auth_and_shows_empty_state(): void
    {
        $this->storage();

        $this->get(route('tools.psychology.journal.history'))
            ->assertRedirect(route('login'));
        $this->assertSame(route('tools.psychology.journal.history'), session('url.intended'));

        $member = $this->member('history-empty@example.com');
        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history'))
            ->assertOk()
            ->assertSee('My Journal History')
            ->assertSee('No journal entries yet')
            ->assertSee('Write Journal')
            ->assertSee(route('tools.psychology.journal'), false)
            ->assertSee('yuktione-journal.css', false)
            ->assertDontSee('View Older Entries');
    }

    public function test_history_lists_only_owned_entries_grouped_by_month(): void
    {
        $this->storage();
        $member = $this->member('history-owner@example.com');
        $other = $this->member('history-other@example.com');

        $this->entry($member, 'personal-growth', 'Quiet wins', 'I felt steady today.', '2026-10-12 10:00:00');
        $this->entry($member, 'relationships', 'Coffee talk', 'We caught up after years.', '2026-09-05 09:00:00');
        $this->entry($other, 'my-thoughts', 'Hidden title', 'Someone else wrote this.', '2026-10-20 12:00:00');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history'))
            ->assertOk()
            ->assertSee('Quiet wins')
            ->assertSee('Personal growth')
            ->assertSee('I felt steady today.')
            ->assertSee('Coffee talk')
            ->assertSee('October 2026')
            ->assertSee('September 2026')
            ->assertSee('Open Entry')
            ->assertDontSee('Someone else wrote this')
            ->assertDontSee('Hidden title');

        $owned = PsychologyJournalEntry::query()->where('user_id', $member->id)->first();
        $foreign = PsychologyJournalEntry::query()->where('user_id', $other->id)->first();

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.show', $owned->id))
            ->assertOk()
            ->assertSee('Quiet wins')
            ->assertSee('I felt steady today.')
            ->assertSee('Private · Only you can see this entry');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.show', $foreign->id))
            ->assertRedirect(route('tools.psychology.journal.history'));
    }

    public function test_history_supports_search_month_prompt_filters_and_pagination(): void
    {
        $this->storage();
        $member = $this->member('history-filters@example.com');
        $other = $this->member('history-filters-other@example.com');
        $this->entry($other, 'stressed-key-ignored', 'Other', 'Hidden forever', '2026-10-30 12:00:00');

        foreach (range(1, 9) as $day) {
            $this->entry(
                $member,
                $day % 2 === 0 ? 'relationships' : 'my-thoughts',
                'Entry '.$day,
                'Body for entry '.$day,
                sprintf('2026-10-%02d 12:00:00', $day)
            );
        }

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history'))
            ->assertOk()
            ->assertSee('Entry 9')
            ->assertSee('Entry 2')
            ->assertDontSee('Entry 1')
            ->assertSee('Page 1 of 2')
            ->assertSee('View Older Entries')
            ->assertDontSee('Hidden forever');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history', ['page' => 2, 'q' => 'Body for entry']))
            ->assertOk()
            ->assertSee('Entry 1')
            ->assertSee('q=Body%20for%20entry', false)
            ->assertDontSee('Hidden forever');

        $this->entry($member, 'personal-growth', 'November note', 'Growth in November', '2026-11-02 08:00:00');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history', ['q' => 'November note']))
            ->assertOk()
            ->assertSee('November note')
            ->assertDontSee('Entry 9');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history', ['filter' => 'month', 'month' => '2026-11']))
            ->assertOk()
            ->assertSee('November note')
            ->assertDontSee('Entry 9');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history', ['filter' => 'prompt', 'prompt' => 'relationships']))
            ->assertOk()
            ->assertSee('Entry 8')
            ->assertDontSee('Entry 9')
            ->assertDontSee('November note');
        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history', ['page' => '0']))
            ->assertRedirect();

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history', ['filter' => 'prompt', 'prompt' => 'not-a-real-prompt']))
            ->assertRedirect();
    }

    public function test_history_escapes_unsafe_content_and_keeps_tool_unpublished_on_home(): void
    {
        $this->storage();
        $member = $this->member('history-xss@example.com');
        $this->entry($member, 'my-thoughts', '<script>alert(1)</script>', '<b>bold</b> thought', '2026-10-15 10:00:00');

        $this->actingAs($member, 'user')
            ->get(route('tools.psychology.journal.history'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;b&gt;bold&lt;/b&gt; thought', false);

        $this->get(route('tools.psychology'))
            ->assertOk()
            ->assertSee('Digital Journal')
            ->assertSee('Coming soon')
            ->assertDontSee('Open journal');
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
            'name' => 'Journal History Member',
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
