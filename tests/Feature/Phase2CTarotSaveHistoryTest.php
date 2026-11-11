<?php

namespace Tests\Feature;

use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\Tool;
use App\Models\User;
use App\Services\ExpertsDiscovery;
use App\Services\TarotDraft;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase2CTarotSaveHistoryTest extends TestCase
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
        Schema::create('tarot_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('category', 80);
            $table->text('question');
            $table->string('spread_type', 20);
            $table->boolean('reversed_enabled')->default(false);
            $table->text('overall_reflection')->nullable();
            $table->timestamps();
        });
        Schema::create('tarot_reading_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarot_reading_id')->constrained('tarot_readings')->cascadeOnDelete();
            $table->foreignId('tarot_card_id')->constrained('tarot_cards')->restrictOnDelete();
            $table->string('position', 40);
            $table->unsignedTinyInteger('position_order');
            $table->boolean('is_reversed')->default(false);
            $table->text('insight_text')->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
            $table->unique(['tarot_reading_id', 'position_order']);
            $table->unique(['tarot_reading_id', 'tarot_card_id']);
        });

        $this->seed(TarotCardSeeder::class);
        app(ToolRegistryRegistrar::class)->register();
    }

    public function test_one_card_full_reading_has_exactly_two_primary_ctas(): void
    {
        $user = $this->member();
        $this->completeDraw($user, 'one');

        $page = $this->actingAs($user, 'user')->get(route('tools.tarot.reading'));
        $page->assertOk()
            ->assertSee('Your Tarot Reading')
            ->assertSee('Quick Clarity')
            ->assertSee('Sit with this:')
            ->assertSee('Talk to an Expert')
            ->assertSee('Save Reading')
            ->assertSee('not prediction', false);

        $html = $page->getContent();
        $this->assertSame(1, substr_count($html, 'data-tarot-cta="expert"'));
        $this->assertSame(1, substr_count($html, 'data-tarot-cta="save"'));
        $this->assertStringNotContainsString('Start another reading', $html);
    }

    public function test_three_card_synthesis_save_idempotency_and_snapshot(): void
    {
        $user = $this->member('three-save@example.com');
        $this->completeDraw($user, 'three');

        $readingPage = $this->actingAs($user, 'user')->get(route('tools.tarot.reading'));
        $readingPage->assertOk()
            ->assertSee('Situation')
            ->assertSee('Challenge')
            ->assertSee('Guidance')
            ->assertSee('Overall reflection');

        $draftBefore = session(TarotDraft::KEY);
        $completionKey = $draftBefore['completion_key'];
        $insightSnapshot = $draftBefore['revealed']['cards'][0]['insight'];
        $cardIdSnapshot = $draftBefore['revealed']['cards'][0]['card_id'];

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.save'))
            ->assertRedirect();

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.save'))
            ->assertRedirect();

        $this->assertSame(1, TarotReading::query()->count());
        $this->assertSame(1, TarotReading::query()->where('completion_key', $completionKey)->count());
        $this->assertSame(3, TarotReadingCard::query()->count());

        $saved = TarotReading::query()->with('cards.card')->firstOrFail();
        $this->assertSame($user->id, $saved->user_id);
        $this->assertSame('career-work', $saved->category);
        $this->assertSame('three', $saved->spread_type);
        $this->assertFalse($saved->reversed_enabled);
        $this->assertNotEmpty($saved->overall_reflection);
        $this->assertSame(
            ['situation', 'challenge', 'guidance'],
            $saved->cards->pluck('position')->all()
        );
        $this->assertSame($cardIdSnapshot, $saved->cards->first()->card->card_id);
        $this->assertSame($insightSnapshot, $saved->cards->first()->insight_text);

        $show = $this->actingAs($user, 'user')->get(route('tools.tarot.history.show', $saved));
        $show->assertOk()
            ->assertSee($insightSnapshot)
            ->assertSee('Talk to an Expert')
            ->assertSee($saved->question);
    }

    public function test_history_ownership_and_unauthorized_access(): void
    {
        $owner = $this->member('owner@example.com');
        $intruder = $this->member('intruder@example.com');
        $this->completeDraw($owner, 'one');
        $this->actingAs($owner, 'user')->post(route('tools.tarot.save'));
        $reading = TarotReading::query()->firstOrFail();

        $this->actingAs($owner, 'user')
            ->get(route('tools.tarot.history'))
            ->assertOk()
            ->assertSee('My Readings')
            ->assertSee('Open Reading')
            ->assertSee('Career & Work');

        $this->actingAs($intruder, 'user')
            ->get(route('tools.tarot.history.show', $reading))
            ->assertForbidden();

        $this->actingAs($intruder, 'user')
            ->get(route('tools.tarot.history'))
            ->assertOk()
            ->assertDontSee($reading->question);
    }

    public function test_expired_draft_save_fails_safely_and_catalogue_stays_clean(): void
    {
        $user = $this->member('expire-save@example.com');
        $this->completeDraw($user, 'one');

        $draft = session(TarotDraft::KEY);
        $draft['expires_at'] = now()->subMinute()->getTimestamp();
        session([TarotDraft::KEY => $draft]);

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.save'))
            ->assertRedirect(route('tools.tarot.intro'))
            ->assertSessionHasErrors('session');

        $this->assertSame(0, TarotReading::query()->count());

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('coming_soon', $tarot->status);
        $this->assertFalse($tarot->is_implemented);
        $this->get(route('tools.index'))->assertOk()->assertDontSee('Tarot Reflection');
    }

    public function test_expert_category_routing_uses_real_explore_filters(): void
    {
        $discovery = app(ExpertsDiscovery::class);

        $love = $discovery->forTarotCategory('love-relationships');
        $this->assertSame('relationships', $love['category']);
        $this->assertStringContainsString('category=relationships', $love['url']);

        $career = $discovery->forTarotCategory('career-work');
        $this->assertSame('career-business', $career['category']);

        $money = $discovery->forTarotCategory('money-finances');
        $this->assertSame('money-finance', $money['category']);

        $general = $discovery->forTarotCategory('general-guidance');
        $this->assertSame('astrology-tarot', $general['category']);
        $this->assertStringContainsString('category=astrology-tarot', $general['url']);

        $fallback = $discovery->forTarotCategory('unknown-key');
        $this->assertSame('astrology-tarot', $fallback['category']);

        foreach (TarotCatalog::categoryKeys() as $key) {
            $this->assertArrayHasKey($discovery->forTarotCategory($key)['category'], \App\Services\ExploreCatalog::CATEGORIES);
        }
    }

    private function member(string $email = 'phase2c@example.com'): User
    {
        return User::query()->create([
            'name' => 'Phase 2C User',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function completeDraw(User $user, string $spread): void
    {
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'));
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => 'career-work',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.question.store'), [
            'question' => 'Should I change my job or stay for growth?',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.spread.store'), [
            'spread_type' => $spread,
        ]);

        $needed = TarotCatalog::spreads()[$spread]['count'];
        $this->actingAs($user, 'user')->get(route('tools.tarot.draw'))->assertOk();

        for ($i = 0; $i < $needed; $i++) {
            $draft = session(TarotDraft::KEY);
            $used = array_column($draft['picks'] ?? [], 'token');
            $token = collect(array_keys($draft['slot_map']))
                ->first(fn ($t) => ! in_array($t, $used, true));
            $this->actingAs($user, 'user')->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $draft['draw_nonce'],
            ]);
        }

        $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'))->assertOk();
    }
}
