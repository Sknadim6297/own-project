<?php

namespace Tests\Feature;

use App\Models\TarotReading;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1TarotReflectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureApiActiveSession::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->default(true);
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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
            $table->foreignId('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('category');
            $table->string('question', 500);
            $table->string('spread_type', 20);
            $table->boolean('reversed_enabled')->default(false);
            $table->text('overall_reflection')->nullable();
            $table->timestamps();
        });

        Schema::create('tarot_reading_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarot_reading_id');
            $table->foreignId('tarot_card_id');
            $table->string('position');
            $table->unsignedTinyInteger('position_order');
            $table->boolean('is_reversed')->default(false);
            $table->text('insight_text');
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
        });

        $this->seed(TarotCardSeeder::class);
        app(ToolRegistryRegistrar::class)->register();

        Tool::query()->where('slug', 'tarot-reflection')->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.tarot.intro',
        ]);
    }

    public function test_options_are_public_and_match_catalog(): void
    {
        $response = $this->getJson('/api/v1/tarot/options');

        $response->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('login_required', true)
            ->assertJsonPath('fan_size', TarotCatalog::FAN_SIZE)
            ->assertJsonPath('question_min', TarotCatalog::QUESTION_MIN)
            ->assertJsonPath('question_max', TarotCatalog::QUESTION_MAX)
            ->assertJsonPath('disclaimer', TarotCatalog::disclaimer());

        $this->assertCount(count(TarotCatalog::categories()), $response->json('categories'));
        $this->assertCount(count(TarotCatalog::spreads()), $response->json('spreads'));
    }

    public function test_guest_cannot_start_a_reading(): void
    {
        $this->postJson('/api/v1/my/tarot/start')->assertUnauthorized();
    }

    public function test_one_card_flow_draw_reveal_save_is_idempotent_and_isolated(): void
    {
        $user = User::query()->create([
            'name' => 'Tarot User',
            'email' => 'tarot-api@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);
        $other = User::query()->create([
            'name' => 'Other User',
            'email' => 'other-tarot@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);

        Sanctum::actingAs($user);
        $completionKey = (string) Str::uuid();

        $this->postJson('/api/v1/my/tarot/start', ['completion_key' => $completionKey])
            ->assertCreated()
            ->assertJsonPath('completion_key', strtolower($completionKey));

        $this->postJson('/api/v1/my/tarot/category', [
            'completion_key' => $completionKey,
            'category' => 'personal-growth',
        ])->assertOk();

        $question = 'What habit may support my growth right now?';
        $this->postJson('/api/v1/my/tarot/question', [
            'completion_key' => $completionKey,
            'question' => $question,
        ])->assertOk()->assertJsonPath('question', $question);

        $this->postJson('/api/v1/my/tarot/spread', [
            'completion_key' => $completionKey,
            'spread_type' => 'one',
            'reversed_enabled' => false,
        ])->assertOk();

        $fan = $this->postJson('/api/v1/my/tarot/fan', ['completion_key' => $completionKey])
            ->assertOk()
            ->json('fan');

        $this->assertCount(TarotCatalog::FAN_SIZE, $fan['tokens']);
        $this->assertSame(1, $fan['needed']);
        $this->assertSame(0, $fan['picked']);

        $token = $fan['tokens'][0];
        $pick = $this->postJson('/api/v1/my/tarot/pick', [
            'completion_key' => $completionKey,
            'slot_token' => $token,
            'draw_nonce' => $fan['nonce'],
        ])->assertOk();

        $this->assertTrue($pick->json('complete'));
        $this->assertSame(1, $pick->json('fan.picked'));

        $reveal = $this->postJson('/api/v1/my/tarot/reveal', ['completion_key' => $completionKey])
            ->assertOk();

        $this->assertCount(1, $reveal->json('reading.cards'));
        $this->assertSame($question, $reveal->json('reading.question'));
        $this->assertSame(TarotCatalog::disclaimer(), $reveal->json('reading.disclaimer'));
        $this->assertNotEmpty($reveal->json('reading.cards.0.core_meaning'));
        $this->assertNotEmpty($reveal->json('reading.cards.0.insight'));
        $cardId = $reveal->json('reading.cards.0.card_id');

        $save = $this->postJson('/api/v1/my/tarot/save', ['completion_key' => $completionKey])
            ->assertCreated();
        $readingId = $save->json('reading.id');
        $this->assertSame($cardId, $save->json('reading.cards.0.card_id'));

        // Idempotent retry — same completion_key never duplicates the row.
        $retry = $this->postJson('/api/v1/my/tarot/save', ['completion_key' => $completionKey])
            ->assertOk();
        $this->assertFalse($retry->json('created'));
        $this->assertSame($readingId, $retry->json('reading.id'));
        $this->assertSame(1, TarotReading::query()->count());

        $this->getJson('/api/v1/my/tarot/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('readings.0.id', $readingId);

        $this->getJson('/api/v1/my/tarot/readings/'.$readingId)
            ->assertOk()
            ->assertJsonPath('reading.cards.0.card_id', $cardId);

        // Account isolation — another customer cannot open this reading.
        Sanctum::actingAs($other);
        $this->getJson('/api/v1/my/tarot/readings/'.$readingId)->assertNotFound();
        $this->getJson('/api/v1/my/tarot/history')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_admin_paused_tool_blocks_start(): void
    {
        Tool::query()->where('slug', 'tarot-reflection')->update(['status' => 'inactive']);

        $user = User::query()->create([
            'name' => 'Paused User',
            'email' => 'paused-tarot@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/tarot/options')->assertOk()->assertJsonPath('open', false);
        $this->postJson('/api/v1/my/tarot/start', ['completion_key' => (string) Str::uuid()])
            ->assertForbidden();
    }

    public function test_replayed_nonce_is_rejected(): void
    {
        $user = User::query()->create([
            'name' => 'Nonce User',
            'email' => 'nonce-tarot@example.com',
            'password' => Hash::make('secret123'),
            'registration_completed' => true,
        ]);
        Sanctum::actingAs($user);
        $key = (string) Str::uuid();

        $this->postJson('/api/v1/my/tarot/start', ['completion_key' => $key])->assertCreated();
        $this->postJson('/api/v1/my/tarot/category', [
            'completion_key' => $key,
            'category' => 'general-guidance',
        ])->assertOk();
        $this->postJson('/api/v1/my/tarot/question', [
            'completion_key' => $key,
            'question' => 'What may be useful to notice right now?',
        ])->assertOk();
        $this->postJson('/api/v1/my/tarot/spread', [
            'completion_key' => $key,
            'spread_type' => 'three',
        ])->assertOk();

        $fan = $this->postJson('/api/v1/my/tarot/fan', ['completion_key' => $key])->json('fan');
        $nonce = $fan['nonce'];
        $tokenA = $fan['tokens'][0];

        $this->postJson('/api/v1/my/tarot/pick', [
            'completion_key' => $key,
            'slot_token' => $tokenA,
            'draw_nonce' => $nonce,
        ])->assertOk();

        // Nonce rotates after each pick — replaying the old one must fail.
        $this->postJson('/api/v1/my/tarot/pick', [
            'completion_key' => $key,
            'slot_token' => $fan['tokens'][1],
            'draw_nonce' => $nonce,
        ])->assertStatus(422);

        Cache::flush();
    }
}
