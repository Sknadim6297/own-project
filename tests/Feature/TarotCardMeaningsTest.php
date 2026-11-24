<?php

namespace Tests\Feature;

use App\Models\TarotCard;
use App\Models\User;
use App\Services\TarotDraft;
use App\Services\TarotInterpretationService;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use App\Support\Tarot\TarotDeckCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 20 — complete deck meaning coverage and category/position interpretation quality.
 */
class TarotCardMeaningsTest extends TestCase
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
        });

        $this->seed(TarotCardSeeder::class);
        app(ToolRegistryRegistrar::class)->register();
    }

    public function test_every_supported_card_has_complete_meaning_content(): void
    {
        $cards = TarotDeckCatalog::cards();
        $this->assertCount(78, $cards);

        $seenMeanings = [];
        foreach ($cards as $card) {
            $id = $card['card_id'];
            $this->assertMatchesRegularExpression('/^(major|wands|cups|swords|pentacles)_/', $id);
            $this->assertNotSame('', trim($card['card_name']));
            $this->assertGreaterThanOrEqual(3, count(array_filter(array_map('trim', explode(',', $card['upright_keywords'])))));
            $this->assertGreaterThanOrEqual(3, count(array_filter(array_map('trim', explode(',', $card['reversed_keywords'])))));
            $this->assertGreaterThanOrEqual(60, mb_strlen($card['upright_meaning']), $id);
            $this->assertGreaterThanOrEqual(60, mb_strlen($card['reversed_meaning']), $id);
            $this->assertDoesNotMatchRegularExpression('/\b(TODO|lorem|placeholder|coming soon)\b/i', $card['upright_meaning'].' '.$card['reversed_meaning']);
            $this->assertStringContainsString('may invite', mb_strtolower($card['upright_meaning']));
            $seenMeanings[$card['upright_meaning']] = true;
        }

        $this->assertCount(78, $seenMeanings);
        $this->assertSame(78, TarotCard::query()->whereNotNull('upright_keywords')->count());
    }

    public function test_category_and_position_interpretations_are_distinct(): void
    {
        $service = app(TarotInterpretationService::class);
        $insights = [];

        foreach (TarotCatalog::categoryKeys() as $category) {
            foreach (['quick_clarity', 'situation', 'challenge', 'guidance'] as $position) {
                $meta = TarotCatalog::categories()[$category];
                $result = $service->composeReadingContext(
                    categoryKey: $category,
                    categoryLabel: $meta['label'],
                    categoryLens: $meta['lens'],
                    question: 'What habit may support my growth right now?',
                    cardName: 'The Star',
                    orientation: 'upright',
                    positionKey: $position,
                    positionLabel: ucwords(str_replace('_', ' ', $position)),
                    positionPrompt: 'What may be useful to notice?',
                    meaning: 'The Star may invite you to reflect on hope and renewal.',
                    keywords: 'Hope, Renewal, Inspiration',
                );
                $key = $category.'|'.$position;
                $insights[$key] = $result['insight'];
                $this->assertNotSame('', $result['reflection_question']);
                $this->assertNotSame('', $result['suggested_action']);
                $this->assertStringContainsString('may invite', mb_strtolower($result['insight']));
                $this->assertStringNotContainsString('will definitely', mb_strtolower($result['insight']));
            }
        }

        $this->assertCount(24, array_unique($insights));
    }

    public function test_full_reading_shows_structured_interpretation_sections(): void
    {
        $user = User::query()->create([
            'name' => 'Task20 Meanings',
            'email' => 'task20-meanings@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user, 'user')->post(route('tools.tarot.start'), ['restart' => '1']);
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => 'personal-growth',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.question.store'), [
            'question' => 'What habit may support my growth right now?',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.spread.store'), [
            'spread_type' => 'one',
        ]);
        $this->actingAs($user, 'user')->get(route('tools.tarot.draw'))->assertOk();

        $draft = session(TarotDraft::KEY);
        $token = array_key_first($draft['slot_map']);
        $this->actingAs($user, 'user')->post(route('tools.tarot.draw.pick'), [
            'slot_token' => $token,
            'draw_nonce' => $draft['draw_nonce'],
        ]);

        $reveal = $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'));
        $reveal->assertOk()
            ->assertSee('yk-tarot__keywords', false)
            ->assertDontSee('Meaning in Your Reading');

        $reading = $this->actingAs($user, 'user')->get(route('tools.tarot.reading'));
        $reading->assertOk()
            ->assertSee('Card Meaning')
            ->assertSee('Meaning in Your Reading')
            ->assertSee('Reflection Question')
            ->assertSee('Suggested Action')
            ->assertSee('Talk to an Astrology & Tarot Expert');

        $payload = session(TarotDraft::KEY)['revealed']['cards'][0];
        $this->assertNotSame('', $payload['keywords'] ?? '');
        $this->assertNotSame('', $payload['reflection_question'] ?? '');
        $this->assertNotSame('', $payload['suggested_action'] ?? '');
    }
}
