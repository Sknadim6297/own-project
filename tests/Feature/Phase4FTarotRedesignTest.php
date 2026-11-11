<?php

namespace Tests\Feature;

use App\Models\TarotReading;
use App\Models\Tool;
use App\Models\User;
use App\Services\TarotDraft;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4F — Tarot Reflection premium visual redesign (UI + security contract).
 */
class Phase4FTarotRedesignTest extends TestCase
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

        Tool::query()->where('slug', 'tarot-reflection')->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.tarot.intro',
        ]);
        Tool::query()->where('slug', 'daily-mood-check-in')->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.psychology.mood',
        ]);
        Tool::query()->where('slug', 'bmi-bmr-calculator')->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.bmi-bmr',
        ]);
    }

    public function test_tarot_pages_opt_into_tools_ds_and_premium_tokens(): void
    {
        $cssPath = public_path('frontend/assets/css/yuktione-tarot.css');
        $jsPath = public_path('frontend/assets/js/yuktione-tarot.js');
        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);

        $css = file_get_contents($cssPath);
        $this->assertStringNotContainsString('--yk-lavender', $css);
        $this->assertStringNotContainsString('157, 129, 219', $css);
        $this->assertStringContainsString('--yk-gold', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('yk-tarot__flip', $css);

        $js = file_get_contents($jsPath);
        $this->assertStringContainsString('data-tarot-flip', $js);
        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringNotContainsString('random', strtolower($js));
        $this->assertStringNotContainsString('deck_order', $js);

        $user = $this->member();
        $this->actingAs($user, 'user')
            ->get(route('tools.tarot.intro'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('data-yk-tarot', false)
            ->assertSee('One-card clarity')
            ->assertSee('Three-card reflection')
            ->assertSee('without prediction', false)
            ->assertSee('yuktione-tarot.js', false)
            ->assertDontSee('YuktiPoints', false);

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertDontSee('data-yk-tarot', false);
    }

    public function test_one_and_three_card_flows_preserve_secure_draw_contract(): void
    {
        $user = $this->member('tarot4f-one@example.com');
        $this->prepareThroughSpread($user, 'one', false);

        $draw = $this->actingAs($user, 'user')->get(route('tools.tarot.draw'));
        $draw->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('yk-tarot__fan', false)
            ->assertSee('yk-tarot__facedown', false)
            ->assertSee('name="slot_token"', false)
            ->assertSee('name="draw_nonce"', false);

        $draft = session(TarotDraft::KEY);
        $this->assertCount(78, $draft['deck_order']);
        $this->assertCount(TarotCatalog::FAN_SIZE, $draft['slot_map']);
        foreach ($draft['deck_order'] as $cardId) {
            $draw->assertDontSee($cardId, false);
        }

        $token = array_key_first($draft['slot_map']);
        $nonce = $draft['draw_nonce'];

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => 'bad-nonce',
            ])
            ->assertRedirect(route('tools.tarot.draw'))
            ->assertSessionHasErrors('slot_token');

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $nonce,
            ])
            ->assertRedirect(route('tools.tarot.reveal'));

        $reveal = $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'));
        $reveal->assertOk()
            ->assertSee('data-tarot-reveal', false)
            ->assertSee('data-tarot-flip', false)
            ->assertSee('Your Card')
            ->assertSee('not a prediction', false)
            ->assertSee('See Full Reading');

        // Replay protection.
        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $nonce,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors();

        // Three-card spread uniqueness.
        $threeUser = $this->member('tarot4f-three@example.com');
        $this->prepareThroughSpread($threeUser, 'three', false);
        $this->actingAs($threeUser, 'user')->get(route('tools.tarot.draw'))->assertOk();

        for ($i = 0; $i < 3; $i++) {
            $state = session(TarotDraft::KEY);
            $available = array_keys(array_diff_key(
                $state['slot_map'],
                array_flip(array_column($state['picks'] ?? [], 'token'))
            ));
            $response = $this->actingAs($threeUser, 'user')->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $available[0],
                'draw_nonce' => $state['draw_nonce'],
            ]);
            if ($i < 2) {
                $response->assertRedirect(route('tools.tarot.draw'));
            } else {
                $response->assertRedirect(route('tools.tarot.reveal'));
            }
        }

        $picks = session(TarotDraft::KEY)['picks'];
        $this->assertCount(3, collect($picks)->pluck('card_id')->unique());
        $this->assertSame(['situation', 'challenge', 'guidance'], array_column($picks, 'position'));

        $this->actingAs($threeUser, 'user')
            ->get(route('tools.tarot.reveal'))
            ->assertOk()
            ->assertSee('Situation')
            ->assertSee('Challenge')
            ->assertSee('Guidance')
            ->assertSee('yk-tarot__reveal-list--three', false);
    }

    public function test_reading_save_history_ownership_and_disabled_engine(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));

        $owner = $this->member('tarot4f-owner@example.com');
        $other = $this->member('tarot4f-other@example.com');
        $this->completeDraw($owner, 'one');

        $readingPage = $this->actingAs($owner, 'user')->get(route('tools.tarot.reading'));
        $readingPage->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('Save Reading')
            ->assertSee('Talk to an Expert')
            ->assertSee('not prediction', false);

        $key = session(TarotDraft::KEY)['completion_key'];
        $this->actingAs($owner, 'user')->post(route('tools.tarot.save'))->assertRedirect();
        $this->actingAs($owner, 'user')->post(route('tools.tarot.save'))->assertRedirect();
        $this->assertSame(1, TarotReading::query()->where('completion_key', $key)->count());

        $saved = TarotReading::query()->firstOrFail();
        $this->actingAs($other, 'user')
            ->get(route('tools.tarot.history.show', $saved))
            ->assertForbidden();

        $this->actingAs($owner, 'user')
            ->get(route('tools.tarot.history'))
            ->assertOk()
            ->assertSee('data-yk-tools-ds', false)
            ->assertSee('My Readings')
            ->assertSee('One Card');

        $this->get(route('tools.bmi-bmr'))
            ->assertOk()
            ->assertDontSee('data-yk-tarot', false);
    }

    public function test_guest_cannot_access_tarot_routes(): void
    {
        $this->get(route('tools.tarot.intro'))->assertRedirect(route('login'));
        $this->post(route('tools.tarot.start'))->assertRedirect(route('login'));
    }

    private function member(string $email = 'tarot4f@example.com'): User
    {
        return User::query()->create([
            'name' => 'Phase 4F Member',
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

    private function completeDraw(User $user, string $spread): void
    {
        $this->prepareThroughSpread($user, $spread, false);
        $this->actingAs($user, 'user')->get(route('tools.tarot.draw'))->assertOk();

        $needed = TarotCatalog::spreads()[$spread]['count'];
        for ($i = 0; $i < $needed; $i++) {
            $state = session(TarotDraft::KEY);
            $available = array_keys(array_diff_key(
                $state['slot_map'],
                array_flip(array_column($state['picks'] ?? [], 'token'))
            ));
            $this->actingAs($user, 'user')->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $available[0],
                'draw_nonce' => $state['draw_nonce'],
            ]);
        }
    }
}
