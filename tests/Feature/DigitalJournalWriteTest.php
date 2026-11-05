<?php

namespace Tests\Feature;

use App\Models\PsychologyJournalEntry;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\JournalDraft;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DigitalJournalWriteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_j1_compose_shows_six_prompts_and_private_copy(): void
    {
        $response = $this->get(route('tools.psychology.journal'));

        $response->assertOk()
            ->assertSee('Digital Journal')
            ->assertSee('Express your thoughts in your own words.')
            ->assertSee('Private · Only you can see this entry')
            ->assertSee('Choose a prompt')
            ->assertSee('My thoughts')
            ->assertSee('Personal growth')
            ->assertSee('Work &amp; career', false)
            ->assertSee('Relationships')
            ->assertSee('Difficult moments')
            ->assertSee('My aspirations')
            ->assertSee('Your thoughts')
            ->assertSee('Save Entry')
            ->assertSee('name="prompt_key"', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="body"', false)
            ->assertSee('yuktione-journal.css', false)
            ->assertDontSee('Coming soon', false);

        $this->get('/tools/digital-journal')->assertNotFound();
    }

    public function test_authenticated_member_can_save_a_private_entry_once(): void
    {
        $this->storage();
        $member = $this->member('journal@example.com');
        $other = $this->member('other-journal@example.com');

        $this->actingAs($member, 'user');
        $this->post(route('tools.psychology.journal.store'), [
            'prompt_key' => 'personal-growth',
            'title' => 'Growing quietly',
            'body' => "Today I noticed progress.\nनमस्ते",
        ])->assertRedirect(route('tools.psychology.journal.history'));

        $saved = PsychologyJournalEntry::query()->first();
        $this->assertNotNull($saved);
        $this->assertSame($member->id, $saved->user_id);
        $this->assertSame('personal-growth', $saved->prompt_key);
        $this->assertSame('Growing quietly', $saved->title);
        $this->assertSame("Today I noticed progress.\nनमस्ते", $saved->body);
        $this->assertNull(session(JournalDraft::KEY));

        $this->withSession([
            JournalDraft::KEY => [
                'step' => 'compose',
                'prompt_key' => 'personal-growth',
                'title' => 'Growing quietly',
                'body' => "Today I noticed progress.\nनमस्ते",
                'completion_key' => $saved->completion_key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.journal.store'), [
            'prompt_key' => 'personal-growth',
            'title' => 'Growing quietly',
            'body' => "Today I noticed progress.\nनमस्ते",
        ])->assertRedirect(route('tools.psychology.journal.history'));

        $this->assertSame(1, PsychologyJournalEntry::query()->count());

        $this->actingAs($other, 'user');
        $this->assertSame(0, PsychologyJournalEntry::query()->where('user_id', $other->id)->count());
        $this->assertSame(1, PsychologyJournalEntry::query()->where('user_id', $member->id)->count());
    }

    public function test_guest_draft_is_claimed_after_login_and_saved_privately(): void
    {
        $this->storage();
        Event::fake([Login::class]);

        $this->post(route('tools.psychology.journal.store'), [
            'prompt_key' => 'my-thoughts',
            'title' => '',
            'body' => 'A private thought from a guest.',
        ])->assertRedirect(route('login'));

        $this->assertNotNull(session(JournalDraft::KEY));
        $this->assertTrue((bool) session(JournalDraft::KEY)['awaiting_login']);
        $this->assertSame(0, PsychologyJournalEntry::query()->count());

        $member = $this->member('guest-journal@example.com');
        $this->actingAs($member, 'user');
        app(\App\Listeners\ClaimJournalDraft::class)->handle(new Login('user', $member, false));

        $this->get(route('tools.psychology.journal.finish'))
            ->assertRedirect(route('tools.psychology.journal.history'));

        $saved = PsychologyJournalEntry::query()->first();
        $this->assertNotNull($saved);
        $this->assertSame($member->id, $saved->user_id);
        $this->assertSame('my-thoughts', $saved->prompt_key);
        $this->assertNull($saved->title);
        $this->assertSame('A private thought from a guest.', $saved->body);
        $this->assertNull(session(JournalDraft::KEY));
    }

    public function test_validation_requires_prompt_and_body(): void
    {
        $this->storage();
        $member = $this->member('invalid-journal@example.com');

        $this->actingAs($member, 'user')
            ->post(route('tools.psychology.journal.store'), [
                'prompt_key' => '',
                'title' => str_repeat('t', PsychologyCatalog::JOURNAL_TITLE_MAX + 1),
                'body' => '',
            ])
            ->assertSessionHasErrors(['prompt_key', 'title', 'body']);

        $this->assertSame(0, PsychologyJournalEntry::query()->count());
    }

    public function test_closed_tool_does_not_save_and_home_stays_unpublished(): void
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
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });
        $category = ToolCategory::query()->create(['name' => 'Psychology', 'slug' => 'psychology']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Digital Journal',
            'slug' => 'digital-journal',
            'module_key' => 'psychology',
            'status' => 'unavailable',
            'is_implemented' => false,
        ]);

        $member = $this->member('closed-journal@example.com');
        $this->actingAs($member, 'user')
            ->post(route('tools.psychology.journal.store'), [
                'prompt_key' => 'relationships',
                'title' => 'Hidden',
                'body' => 'Should not save',
            ])
            ->assertForbidden();

        $this->assertSame(0, PsychologyJournalEntry::query()->count());

        Tool::query()->where('slug', 'digital-journal')->update([
            'status' => 'coming_soon',
            'is_implemented' => false,
            'route_name' => null,
        ]);

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
            'name' => 'Journal Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
