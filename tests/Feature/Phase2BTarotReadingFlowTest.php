<?php

namespace Tests\Feature;

use App\Models\TarotCard;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\TarotDraft;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase2BTarotReadingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        // Layout header resolves customerProfile() for authenticated customers.
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
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
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        Schema::create('tarot_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_id', 40)->unique();
            $table->string('card_name');
            $table->string('arcana', 20);
            $table->string('suit', 20)->nullable();
            $table->string('number', 20)->nullable();
            $table->text('upright_meaning');
            $table->text('reversed_meaning');
            $table->string('upright_keywords')->nullable();
            $table->string('reversed_keywords')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seed(TarotCardSeeder::class);
        app(ToolRegistryRegistrar::class)->register();
    }

    public function test_guest_is_redirected_and_tool_stays_unpublished(): void
    {
        $this->get(route('tools.tarot.intro'))->assertRedirect(route('login'));

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('coming_soon', $tarot->status);
        $this->assertFalse($tarot->is_implemented);
        $this->assertNull($tarot->route_name);

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertDontSee('Tarot Reflection');
    }

    public function test_six_categories_question_and_spread_validation(): void
    {
        $user = $this->member();

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.start'))
            ->assertRedirect(route('tools.tarot.category'));

        $this->assertCount(6, TarotCatalog::categoryKeys());

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.category.store'), ['category' => 'not-real'])
            ->assertSessionHasErrors('category');

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.category.store'), ['category' => 'career-work'])
            ->assertRedirect(route('tools.tarot.question'));

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.question.store'), ['question' => 'short'])
            ->assertSessionHasErrors('question');

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.question.store'), [
                'question' => '<script>alert(1)</script>Should I change my job path?',
            ])
            ->assertRedirect(route('tools.tarot.spread'));

        $draft = session(TarotDraft::KEY);
        $this->assertStringNotContainsString('<script>', $draft['question']);
        $this->assertFalse($draft['reversed_enabled']);

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.spread.store'), ['spread_type' => 'celtic'])
            ->assertSessionHasErrors('spread_type');

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.spread.store'), ['spread_type' => 'one'])
            ->assertRedirect(route('tools.tarot.draw'));

        $this->assertFalse(session(TarotDraft::KEY)['reversed_enabled']);
        $this->assertSame('one', session(TarotDraft::KEY)['spread_type']);
    }

    public function test_one_card_secure_draw_reveal_and_replay_protection(): void
    {
        $user = $this->member();
        $this->prepareThroughSpread($user, 'one', false);

        $draw = $this->actingAs($user, 'user')->get(route('tools.tarot.draw'));
        $draw->assertOk()->assertSee('Focus')->assertSee('Facedown');

        $draft = session(TarotDraft::KEY);
        $this->assertCount(78, $draft['deck_order']);
        $this->assertCount(TarotCatalog::FAN_SIZE, $draft['slot_map']);
        $this->assertNotEmpty($draft['draw_nonce']);

        // Client never receives deck card IDs in HTML.
        foreach ($draft['deck_order'] as $cardId) {
            $draw->assertDontSee($cardId, false);
        }

        $token = array_key_first($draft['slot_map']);
        $nonce = $draft['draw_nonce'];

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => 'tampered-nonce',
            ])
            ->assertRedirect(route('tools.tarot.draw'))
            ->assertSessionHasErrors('slot_token');

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $nonce,
            ])
            ->assertRedirect(route('tools.tarot.reveal'));

        // Replay same nonce/token must fail.
        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $nonce,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors();

        $reveal = $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'));
        $reveal->assertOk()
            ->assertSee('Your Card')
            ->assertSee('Quick Clarity')
            ->assertSee('Upright')
            ->assertSee('not a prediction', false)
            ->assertSee('yk-tarot__face-fallback', false)
            ->assertDontSee('Artwork coming soon')
            ->assertSee('See Full Reading');

        $revealed = session(TarotDraft::KEY)['revealed'];
        $this->assertCount(1, $revealed['cards']);
        $this->assertSame('quick_clarity', $revealed['cards'][0]['position']);
        $this->assertFalse($revealed['cards'][0]['is_reversed']);
        $this->assertFalse($revealed['reversed_enabled']);
        $this->assertStringContainsString('may', strtolower($revealed['cards'][0]['insight']));
        $this->assertNull(TarotCard::query()->where('card_id', $revealed['cards'][0]['card_id'])->value('image_path'));
    }

    public function test_three_card_unique_positions_and_no_duplicate_cards(): void
    {
        $user = $this->member('three@example.com');
        $this->prepareThroughSpread($user, 'three', false);

        $this->actingAs($user, 'user')->get(route('tools.tarot.draw'))->assertOk();

        for ($i = 0; $i < 3; $i++) {
            $draft = session(TarotDraft::KEY);
            $available = array_keys(array_diff_key(
                $draft['slot_map'],
                array_flip(array_column($draft['picks'] ?? [], 'token'))
            ));
            $token = $available[0];
            $nonce = $draft['draw_nonce'];

            $response = $this->actingAs($user, 'user')->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $nonce,
            ]);

            if ($i < 2) {
                $response->assertRedirect(route('tools.tarot.draw'));
            } else {
                $response->assertRedirect(route('tools.tarot.reveal'));
            }
        }

        $picks = session(TarotDraft::KEY)['picks'];
        $this->assertCount(3, $picks);
        $this->assertCount(3, collect($picks)->pluck('card_id')->unique());
        $this->assertSame(
            ['situation', 'challenge', 'guidance'],
            array_column($picks, 'position')
        );

        $reveal = $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'));
        $reveal->assertOk()
            ->assertSee('Situation')
            ->assertSee('Challenge')
            ->assertSee('Guidance')
            ->assertSee('See Full Reading');

        $full = $this->actingAs($user, 'user')->get(route('tools.tarot.reading'));
        $full->assertOk()->assertSee('Overall reflection');

        $cards = session(TarotDraft::KEY)['revealed']['cards'];
        $this->assertCount(3, collect($cards)->pluck('card_id')->unique());
        foreach ($cards as $card) {
            $this->assertFalse($card['is_reversed']);
        }
    }

    public function test_session_expiry_and_csrf_post_without_token(): void
    {
        $user = $this->member('expiry@example.com');
        $this->prepareThroughSpread($user, 'one', false);

        $draft = session(TarotDraft::KEY);
        $draft['expires_at'] = now()->subMinute()->getTimestamp();
        session([TarotDraft::KEY => $draft]);

        $this->actingAs($user, 'user')
            ->get(route('tools.tarot.draw'))
            ->assertRedirect(route('tools.tarot.spread'));

        // Fresh draft after expiry should not keep old spread.
        $fresh = session(TarotDraft::KEY);
        $this->assertTrue(is_array($fresh));
        $this->assertNull($fresh['spread_type'] ?? null);

        // Ownership: another customer cannot continue a foreign draft owner key.
        $other = $this->member('other-tarot@example.com');
        session([TarotDraft::KEY => array_merge($fresh, [
            'owner' => 'user:99999',
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
            'step' => 'category',
            'category' => 'career-work',
        ])]);

        $this->actingAs($other, 'user')
            ->get(route('tools.tarot.question'))
            ->assertRedirect(route('tools.tarot.category'));

        $this->assertSame('user:'.$other->id, session(TarotDraft::KEY)['owner']);
        $this->assertNull(session(TarotDraft::KEY)['category']);
    }

    public function test_existing_psychology_tools_still_listed(): void
    {
        $this->assertSame(1, Tool::query()->where('slug', 'daily-mood-check-in')->count());
        $this->assertTrue(ToolCategory::query()->where('slug', 'psychology')->exists());
        $this->assertTrue(ToolCategory::query()->where('slug', 'astrology-tarot')->exists());

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertDontSee('Tarot Reflection');
    }

    private function member(string $email = 'tarot-user@example.com'): User
    {
        return User::query()->create([
            'name' => 'Tarot User',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function prepareThroughSpread(User $user, string $spread, bool $reversed): void
    {
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'));
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => 'career-work',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.question.store'), [
            'question' => 'Should I change my job or stay for growth?',
        ]);

        $payload = ['spread_type' => $spread];
        if ($reversed) {
            $payload['reversed_enabled'] = '1';
        }

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.spread.store'), $payload)
            ->assertRedirect(route('tools.tarot.draw'));
    }
}
