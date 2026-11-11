<?php

namespace Tests\Feature;

use App\Models\TarotCard;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotDeckCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase2ATarotFoundationTest extends TestCase
{
    private const EXISTING_TOOL_SLUGS = [
        'daily-mood-check-in',
        'digital-journal',
        'gratitude-journal',
        'career-interest-test',
        'skills-strengths-assessment',
        'bmi-bmr-calculator',
        'water-intake-tracker',
        'weight-journey',
        'calorie-nutrition-tracker',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
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
    }

    public function test_catalog_defines_exactly_seventy_eight_unique_cards(): void
    {
        $cards = TarotDeckCatalog::cards();
        $this->assertCount(78, $cards);
        $this->assertCount(78, collect($cards)->pluck('card_id')->unique());
        $this->assertCount(22, collect($cards)->where('arcana', 'major'));
        $this->assertCount(56, collect($cards)->where('arcana', 'minor'));

        foreach (['wands', 'cups', 'swords', 'pentacles'] as $suit) {
            $this->assertCount(
                14,
                collect($cards)->where('suit', $suit),
                $suit
            );
        }

        foreach ($cards as $card) {
            $this->assertNotSame('', trim((string) $card['upright_meaning']), $card['card_id']);
            $this->assertNotSame('', trim((string) $card['reversed_meaning']), $card['card_id']);
        }

        $meanings = collect($cards)->map(
            fn (array $card) => $card['card_id'].'|'.$card['upright_meaning'].'|'.$card['reversed_meaning']
        );
        $this->assertCount(78, $meanings->unique(), 'Each card must keep its own upright/reversed meanings.');
    }

    public function test_tarot_card_seeder_is_idempotent_and_leaves_optional_fields_null(): void
    {
        $this->seed(TarotCardSeeder::class);
        $this->seed(TarotCardSeeder::class);

        $this->assertSame(78, TarotCard::query()->count());
        $this->assertCount(78, TarotCard::query()->pluck('card_id')->unique());
        $this->assertSame(22, TarotCard::query()->where('arcana', 'major')->count());
        $this->assertSame(56, TarotCard::query()->where('arcana', 'minor')->count());

        foreach (['wands', 'cups', 'swords', 'pentacles'] as $suit) {
            $this->assertSame(14, TarotCard::query()->where('suit', $suit)->count(), $suit);
        }

        $star = TarotCard::query()->where('card_id', 'major_17')->firstOrFail();
        $this->assertSame('The Star', $star->card_name);
        $this->assertSame('Hope, renewal, inspiration, healing, faith', $star->upright_meaning);
        $this->assertSame('Discouragement, doubt, loss of direction', $star->reversed_meaning);
        $this->assertNull($star->upright_keywords);
        $this->assertNull($star->reversed_keywords);
        $this->assertNull($star->image_path);

        TarotCard::query()->each(function (TarotCard $card) {
            $this->assertNotSame('', trim($card->upright_meaning), $card->card_id);
            $this->assertNotSame('', trim($card->reversed_meaning), $card->card_id);
        });
    }

    public function test_reading_relationships_and_reversed_defaults(): void
    {
        $this->seed(TarotCardSeeder::class);

        $user = User::query()->create([
            'name' => 'Reader',
            'email' => 'reader@example.com',
        ]);

        $reading = TarotReading::query()->create([
            'user_id' => $user->id,
            'completion_key' => (string) Str::uuid(),
            'category' => 'career-work',
            'question' => 'Should I change my job?',
            'spread_type' => TarotReading::SPREAD_ONE,
        ]);

        $this->assertFalse($reading->reversed_enabled);
        $this->assertFalse((bool) $reading->getAttributes()['reversed_enabled']);

        $card = TarotCard::query()->where('card_id', 'major_17')->firstOrFail();

        $drawn = TarotReadingCard::query()->create([
            'tarot_reading_id' => $reading->id,
            'tarot_card_id' => $card->id,
            'position' => 'quick_clarity',
            'position_order' => 1,
            'insight_text' => 'Snapshot insight',
            'drawn_at' => now(),
        ]);

        $this->assertFalse($drawn->is_reversed);
        $this->assertTrue($user->tarotReadings()->whereKey($reading->id)->exists());
        $this->assertTrue($reading->cards()->whereKey($drawn->id)->exists());
        $this->assertSame('The Star', $drawn->fresh()->card->card_name);
        $this->assertSame($reading->id, $drawn->fresh()->reading->id);
        $this->assertSame(1, $card->readingCards()->count());
    }

    public function test_tarot_registry_entry_is_unpublished_idempotent_and_existing_tools_remain(): void
    {
        $registrar = app(ToolRegistryRegistrar::class);
        $registrar->register();
        $registrar->register();

        $this->assertSame(4, ToolCategory::query()->count());
        $this->assertSame(10, Tool::query()->count());
        $this->assertSame(1, Tool::query()->where('slug', 'tarot-reflection')->count());
        $this->assertTrue(ToolCategory::query()->where('slug', 'astrology-tarot')->exists());

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('coming_soon', $tarot->status);
        $this->assertFalse($tarot->is_implemented);
        $this->assertNull($tarot->route_name);
        $this->assertFalse($tarot->canLaunch());
        $this->assertSame('astrology-tarot', $tarot->module_key);
        $this->assertSame(
            'astrology-tarot',
            ToolCategory::query()->findOrFail($tarot->tool_category_id)->slug
        );

        foreach (self::EXISTING_TOOL_SLUGS as $slug) {
            $this->assertTrue(Tool::query()->where('slug', $slug)->exists(), $slug);
        }

        $this->assertSame(3, Tool::query()->where('module_key', 'psychology')->count());
        $this->assertSame(2, Tool::query()->where('module_key', 'career-guidance')->count());
        $this->assertSame(4, Tool::query()->where('module_key', 'nutrition')->count());
        $this->assertSame(1, Tool::query()->where('module_key', 'astrology-tarot')->count());

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertDontSee('Tarot Reflection');
    }
}
