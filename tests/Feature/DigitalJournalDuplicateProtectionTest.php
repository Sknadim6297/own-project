<?php

namespace Tests\Feature;

use App\Models\PsychologyJournalEntry;
use App\Models\User;
use App\Services\JournalDraft;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalJournalDuplicateProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_compose_form_embeds_a_completion_key_and_disables_resubmit_hooks(): void
    {
        $html = $this->get(route('tools.psychology.journal'))
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertSee('name="completion_key"', false)
            ->assertSee('data-journal-save', false)
            ->assertSee('yuktione-journal.js', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'name="completion_key"'));
        $this->assertMatchesRegularExpression(
            '/name="completion_key" value="[0-9a-f-]{36}"/',
            $html
        );
        $this->assertStringContainsString('data-submitting', file_get_contents(public_path('frontend/assets/js/yuktione-journal.js')));
        $this->assertStringContainsString('Saving…', file_get_contents(public_path('frontend/assets/js/yuktione-journal.js')));
    }

    public function test_repeated_posts_with_the_same_completion_key_create_one_entry(): void
    {
        $this->storage();
        $member = $this->member('dup-once@example.com');
        $key = (string) Str::uuid();

        $this->actingAs($member, 'user');
        $payload = [
            'completion_key' => $key,
            'prompt_key' => 'my-thoughts',
            'title' => 'Synthetic note',
            'body' => 'Synthetic journal body for duplicate protection.',
        ];

        $this->post(route('tools.psychology.journal.store'), $payload)
            ->assertRedirect(route('tools.psychology.journal.history'));
        $this->post(route('tools.psychology.journal.store'), $payload)
            ->assertRedirect(route('tools.psychology.journal.history'));
        $this->post(route('tools.psychology.journal.store'), $payload)
            ->assertRedirect(route('tools.psychology.journal.history'));

        $this->assertSame(1, PsychologyJournalEntry::query()->where('user_id', $member->id)->count());
        $this->assertSame(1, PsychologyJournalEntry::query()->where('completion_key', $key)->count());
    }

    public function test_intentional_entries_with_identical_content_remain_allowed(): void
    {
        $this->storage();
        $member = $this->member('dup-two@example.com');
        $this->actingAs($member, 'user');

        $shared = [
            'prompt_key' => 'personal-growth',
            'title' => 'Same title on purpose',
            'body' => 'Same synthetic body on purpose.',
        ];

        $this->post(route('tools.psychology.journal.store'), $shared + ['completion_key' => (string) Str::uuid()])
            ->assertRedirect(route('tools.psychology.journal.history'));
        $this->post(route('tools.psychology.journal.store'), $shared + ['completion_key' => (string) Str::uuid()])
            ->assertRedirect(route('tools.psychology.journal.history'));

        $this->assertSame(2, PsychologyJournalEntry::query()->where('user_id', $member->id)->count());
    }

    public function test_guest_finish_saves_once_even_when_finish_is_requested_again(): void
    {
        $this->storage();
        Event::fake([Login::class]);

        $key = (string) Str::uuid();
        $this->post(route('tools.psychology.journal.store'), [
            'completion_key' => $key,
            'prompt_key' => 'relationships',
            'title' => '',
            'body' => 'Guest synthetic draft body.',
        ])->assertRedirect(route('login'));

        $member = $this->member('dup-guest@example.com');
        $this->actingAs($member, 'user');
        app(\App\Listeners\ClaimJournalDraft::class)->handle(new Login('user', $member, false));

        $this->get(route('tools.psychology.journal.finish'))
            ->assertRedirect(route('tools.psychology.journal.history'));
        $this->get(route('tools.psychology.journal.finish'))
            ->assertRedirect(route('tools.psychology'));

        $this->assertSame(1, PsychologyJournalEntry::query()->where('user_id', $member->id)->count());
        $this->assertSame(1, PsychologyJournalEntry::query()->where('completion_key', $key)->count());
        $this->assertNull(session(JournalDraft::KEY));
    }

    public function test_history_lists_each_saved_entry_once(): void
    {
        $this->storage();
        $member = $this->member('dup-history@example.com');
        $this->actingAs($member, 'user');

        foreach (['one', 'two', 'three'] as $label) {
            $this->post(route('tools.psychology.journal.store'), [
                'completion_key' => (string) Str::uuid(),
                'prompt_key' => 'my-aspirations',
                'title' => 'History '.$label,
                'body' => 'Synthetic history body '.$label,
            ])->assertRedirect(route('tools.psychology.journal.history'));
        }

        $page = $this->get(route('tools.psychology.journal.history'))->assertOk();
        $html = $page->getContent();
        $this->assertSame(1, substr_count($html, 'History one'));
        $this->assertSame(1, substr_count($html, 'History two'));
        $this->assertSame(1, substr_count($html, 'History three'));
        $this->assertSame(3, PsychologyJournalEntry::query()->where('user_id', $member->id)->count());
    }

    public function test_missing_completion_key_is_rejected(): void
    {
        $this->storage();
        $member = $this->member('dup-missing-key@example.com');

        $this->actingAs($member, 'user')
            ->post(route('tools.psychology.journal.store'), [
                'prompt_key' => 'my-thoughts',
                'title' => 'No key',
                'body' => 'Should not save without a completion key.',
            ])
            ->assertSessionHasErrors('completion_key');

        $this->assertSame(0, PsychologyJournalEntry::query()->count());
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
            'name' => 'Journal Dup Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
