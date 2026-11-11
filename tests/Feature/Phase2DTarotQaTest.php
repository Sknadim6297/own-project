<?php

namespace Tests\Feature;

use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\Tool;
use App\Models\User;
use App\Services\ExpertsDiscovery;
use App\Services\TarotDraft;
use App\Services\TarotReadingRecorder;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase2DTarotQaTest extends TestCase
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
            $table->index(['user_id', 'created_at']);
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

    public function test_pdf_acceptance_surface_and_unpublished_catalogue(): void
    {
        $this->assertCount(6, TarotCatalog::categoryKeys());
        $this->assertSame(['one', 'three'], TarotCatalog::spreadKeys());
        $this->assertFalse(TarotCatalog::spreads()['one']['count'] !== 1);
        $this->assertSame(3, TarotCatalog::spreads()['three']['count']);

        $tool = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('coming_soon', $tool->status);
        $this->assertFalse($tool->is_implemented);
        $this->assertNull($tool->route_name);
        $this->assertFalse($tool->canLaunch());

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertDontSee('Tarot Reflection');
    }

    public function test_end_to_end_one_card_journey_ctas_and_snapshot_reopen(): void
    {
        $user = $this->member();
        $this->completeDraw($user, 'one');

        $this->actingAs($user, 'user')
            ->get(route('tools.tarot.intro'))
            ->assertOk()
            ->assertSee('Disclaimer', false)
            ->assertSee(TarotCatalog::disclaimer());

        $reading = $this->actingAs($user, 'user')->get(route('tools.tarot.reading'));
        $reading->assertOk()
            ->assertSee('Talk to an Expert')
            ->assertSee('Save Reading')
            ->assertDontSee('Start another reading')
            ->assertDontSee('Journal')
            ->assertDontSee('Share');

        $html = $reading->getContent();
        $this->assertSame(1, substr_count($html, 'data-tarot-cta="expert"'));
        $this->assertSame(1, substr_count($html, 'data-tarot-cta="save"'));

        $draft = session(TarotDraft::KEY);
        $insight = $draft['revealed']['cards'][0]['insight'];
        $cardId = $draft['revealed']['cards'][0]['card_id'];
        $this->assertFalse($draft['reversed_enabled']);
        $this->assertFalse($draft['revealed']['cards'][0]['is_reversed']);

        $this->actingAs($user, 'user')->post(route('tools.tarot.save'))->assertRedirect();
        $saved = TarotReading::query()->with('cards.card')->firstOrFail();
        $this->assertSame($cardId, $saved->cards->first()->card->card_id);
        $this->assertSame($insight, $saved->cards->first()->insight_text);

        $duplicateBlocked = false;
        try {
            DB::table('tarot_readings')->insert([
                'user_id' => $user->id,
                'completion_key' => $saved->completion_key,
                'category' => 'career-work',
                'question' => 'duplicate',
                'spread_type' => 'one',
                'reversed_enabled' => 0,
                'overall_reflection' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $duplicateBlocked = str_contains(strtolower($e->getMessage()), 'unique');
        }
        $this->assertTrue($duplicateBlocked, 'completion_key unique constraint must block duplicate rows.');
        $this->assertSame(1, TarotReading::query()->count());
    }

    public function test_recorder_returns_existing_row_when_unique_race_is_caught(): void
    {
        $user = $this->member('race@example.com');
        $this->completeDraw($user, 'one');
        $this->actingAs($user, 'user');

        $first = app(TarotReadingRecorder::class)->saveFromDraft();
        $second = app(TarotReadingRecorder::class)->saveFromDraft();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, TarotReading::query()->count());
        $this->assertSame(1, TarotReadingCard::query()->count());

        $show = $this->actingAs($user, 'user')->get(route('tools.tarot.history.show', $first));
        $show->assertOk()
            ->assertSee($first->cards->first()->insight_text)
            ->assertSee('Talk to an Expert')
            ->assertDontSee('Start another reading');
    }

    public function test_all_six_expert_mappings_resolve_to_real_explore_categories(): void
    {
        $discovery = app(ExpertsDiscovery::class);
        $expected = [
            'love-relationships' => 'relationships',
            'career-work' => 'career-business',
            'money-finances' => 'money-finance',
            'personal-growth' => 'mental-health',
            'life-direction' => 'spirituality',
            'general-guidance' => 'astrology-tarot',
        ];

        foreach ($expected as $tarot => $explore) {
            $result = $discovery->forTarotCategory($tarot);
            $this->assertSame($explore, $result['category'], $tarot);
            $this->assertStringContainsString('category='.$explore, $result['url']);
            $this->assertTrue(array_key_exists($explore, \App\Services\ExploreCatalog::CATEGORIES));
        }
    }

    private function member(string $email = 'phase2d@example.com'): User
    {
        return User::query()->create([
            'name' => 'Phase 2D',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function completeDraw(User $user, string $spread): void
    {
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'));
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), ['category' => 'career-work']);
        $this->actingAs($user, 'user')->post(route('tools.tarot.question.store'), [
            'question' => 'Should I change my job or stay for growth?',
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.spread.store'), ['spread_type' => $spread]);
        $this->actingAs($user, 'user')->get(route('tools.tarot.draw'))->assertOk();

        $needed = TarotCatalog::spreads()[$spread]['count'];
        for ($i = 0; $i < $needed; $i++) {
            $draft = session(TarotDraft::KEY);
            $used = array_column($draft['picks'] ?? [], 'token');
            $token = collect(array_keys($draft['slot_map']))->first(fn ($t) => ! in_array($t, $used, true));
            $this->actingAs($user, 'user')->post(route('tools.tarot.draw.pick'), [
                'slot_token' => $token,
                'draw_nonce' => $draft['draw_nonce'],
            ]);
        }

        $this->actingAs($user, 'user')->get(route('tools.tarot.reveal'))->assertOk();
    }
}
