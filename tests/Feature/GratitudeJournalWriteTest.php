<?php

namespace Tests\Feature;

use App\Models\PsychologyGratitudeEntry;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\GratitudeDraft;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GratitudeJournalWriteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_g1_compose_shows_three_cards_and_carousel_controls(): void
    {
        $response = $this->get(route('tools.psychology.gratitude'));

        $response->assertOk()
            ->assertSee('Gratitude Journal')
            ->assertSee('Pause. Reflect. Appreciate.')
            ->assertSee('What are you grateful for today?')
            ->assertSee('Fill in whichever cards you like. Skip the rest.')
            ->assertSee('Private · Only you can see this entry')
            ->assertSee('Something good today')
            ->assertSee('A little moment that made you smile.')
            ->assertSee('What was one good thing about your day?')
            ->assertSee('Someone I appreciate')
            ->assertSee('Who is someone you appreciate?')
            ->assertSee('This can be someone you know, or someone you noticed.')
            ->assertSee('Something about myself')
            ->assertSee('What is something you appreciate about yourself?')
            ->assertSee('Notice one quality, effort, or choice of your own.')
            ->assertSee('Card 1 of 3')
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('Skip')
            ->assertSee('Finish &amp; Save', false)
            ->assertSee('data-gratitude-carousel', false)
            ->assertSee('data-gratitude-answer', false)
            ->assertSee('name="answers[something-good-today]"', false)
            ->assertSee('name="answers[someone-i-appreciate]"', false)
            ->assertSee('name="answers[something-about-myself]"', false)
            ->assertSee('yuktione-gratitude.css', false)
            ->assertDontSee('Coming soon', false);

        $this->get('/tools/gratitude-journal')->assertNotFound();
    }

    public function test_authenticated_member_can_save_one_or_more_cards_once(): void
    {
        $this->storage();
        $member = $this->member('gratitude@example.com');
        $other = $this->member('other-gratitude@example.com');

        $this->actingAs($member, 'user');
        $response = $this->post(route('tools.psychology.gratitude.store'), [
            'answers' => [
                'something-good-today' => 'Sunshine on the walk.',
                'someone-i-appreciate' => '',
                'something-about-myself' => 'I stayed patient.',
            ],
        ]);

        $saved = PsychologyGratitudeEntry::query()->first();
        $this->assertNotNull($saved);
        $response->assertRedirect(route('tools.psychology.gratitude.complete', $saved->completion_key));
        $this->assertSame($member->id, $saved->user_id);
        $this->assertSame('Sunshine on the walk.', $saved->something_good_today);
        $this->assertNull($saved->someone_i_appreciate);
        $this->assertSame('I stayed patient.', $saved->something_about_myself);
        $this->assertNull(session(GratitudeDraft::KEY));

        $this->withSession([
            GratitudeDraft::KEY => [
                'step' => 'compose',
                'answers' => [
                    'something-good-today' => 'Sunshine on the walk.',
                    'something-about-myself' => 'I stayed patient.',
                ],
                'completion_key' => $saved->completion_key,
                'awaiting_login' => false,
                'owner' => 'user:'.$member->id,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->post(route('tools.psychology.gratitude.store'), [
            'answers' => [
                'something-good-today' => 'Sunshine on the walk.',
                'someone-i-appreciate' => '',
                'something-about-myself' => 'I stayed patient.',
            ],
        ])->assertRedirect(route('tools.psychology.gratitude.complete', $saved->completion_key));

        $this->assertSame(1, PsychologyGratitudeEntry::query()->count());
        $this->assertSame(0, PsychologyGratitudeEntry::query()->where('user_id', $other->id)->count());
    }

    public function test_guest_draft_is_claimed_after_login_and_saved_privately(): void
    {
        $this->storage();
        Event::fake([Login::class]);

        $this->post(route('tools.psychology.gratitude.store'), [
            'answers' => [
                'someone-i-appreciate' => 'My neighbour who waved.',
            ],
        ])->assertRedirect(route('login'));

        $this->assertNotNull(session(GratitudeDraft::KEY));
        $this->assertTrue((bool) session(GratitudeDraft::KEY)['awaiting_login']);
        $this->assertSame(0, PsychologyGratitudeEntry::query()->count());

        $member = $this->member('guest-gratitude@example.com');
        $this->actingAs($member, 'user');
        app(\App\Listeners\ClaimGratitudeDraft::class)->handle(new Login('user', $member, false));

        $finish = $this->get(route('tools.psychology.gratitude.finish'));

        $saved = PsychologyGratitudeEntry::query()->first();
        $this->assertNotNull($saved);
        $finish->assertRedirect(route('tools.psychology.gratitude.complete', $saved->completion_key));
        $this->assertSame($member->id, $saved->user_id);
        $this->assertNull($saved->something_good_today);
        $this->assertSame('My neighbour who waved.', $saved->someone_i_appreciate);
        $this->assertNull($saved->something_about_myself);
        $this->assertNull(session(GratitudeDraft::KEY));
    }

    public function test_validation_requires_at_least_one_answer_and_respects_limits(): void
    {
        $this->storage();
        $member = $this->member('invalid-gratitude@example.com');

        $this->actingAs($member, 'user')
            ->from(route('tools.psychology.gratitude'))
            ->post(route('tools.psychology.gratitude.store'), [
                'answers' => [
                    'something-good-today' => '   ',
                    'someone-i-appreciate' => '',
                    'something-about-myself' => '',
                ],
            ])
            ->assertRedirect(route('tools.psychology.gratitude'))
            ->assertSessionHasErrors('answers');

        $this->actingAs($member, 'user')
            ->post(route('tools.psychology.gratitude.store'), [
                'answers' => [
                    'something-good-today' => str_repeat('a', PsychologyCatalog::GRATITUDE_ANSWER_MAX + 1),
                ],
            ])
            ->assertSessionHasErrors('answers.something-good-today');

        $this->assertSame(0, PsychologyGratitudeEntry::query()->count());
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
            'name' => 'Gratitude Journal',
            'slug' => 'gratitude-journal',
            'module_key' => 'psychology',
            'status' => 'unavailable',
            'is_implemented' => false,
        ]);

        $member = $this->member('closed-gratitude@example.com');
        $this->actingAs($member, 'user')
            ->post(route('tools.psychology.gratitude.store'), [
                'answers' => [
                    'something-about-myself' => 'Should not save',
                ],
            ])
            ->assertForbidden();

        $this->assertSame(0, PsychologyGratitudeEntry::query()->count());

        Tool::query()->where('slug', 'gratitude-journal')->update([
            'status' => 'coming_soon',
            'is_implemented' => false,
            'route_name' => null,
        ]);

        $this->get(route('tools.psychology'))
            ->assertOk()
            ->assertSee('Gratitude Journal')
            ->assertSee('Coming soon')
            ->assertDontSee('Open gratitude');
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
            'name' => 'Gratitude Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
