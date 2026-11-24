<?php

namespace Tests\Feature;

use App\Models\TarotReading;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyTarotReflectionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureApiActiveSession::class);
        $this->storage(true);
        Carbon::setTestNow(Carbon::parse('2026-03-15 06:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guests_can_load_options_but_cannot_draw(): void
    {
        $this->getJson('/api/v1/tarot/options')
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('login_required', true)
            ->assertJsonPath('fan_size', TarotCatalog::FAN_SIZE)
            ->assertJsonCount(6, 'categories')
            ->assertJsonCount(2, 'spreads');

        $this->postJson('/api/v1/my/tarot/start')->assertUnauthorized();
    }

    public function test_one_card_flow_is_idempotent_and_history_is_owned(): void
    {
        $owner = $this->member('tarot-owner@example.com');
        $other = $this->member('tarot-other@example.com');
        Sanctum::actingAs($owner);

        $start = $this->postJson('/api/v1/my/tarot/start')->assertCreated();
        $key = $start->json('completion_key');
        $this->assertNotEmpty($key);

        $this->postJson('/api/v1/my/tarot/category', [
            'completion_key' => $key,
            'category' => 'career-work',
        ])->assertOk();

        $this->postJson('/api/v1/my/tarot/question', [
            'completion_key' => $key,
            'question' => 'short',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/my/tarot/question', [
            'completion_key' => $key,
            'question' => '<script>x</script>What may help me grow in my role?',
        ])
            ->assertOk()
            ->assertJsonPath('question', 'xWhat may help me grow in my role?')
            ->assertJsonMissing(['question' => '<script>']);

        $this->postJson('/api/v1/my/tarot/spread', [
            'completion_key' => $key,
            'spread_type' => 'one',
        ])->assertOk()->assertJsonPath('reversed_enabled', false);

        $fan = $this->postJson('/api/v1/my/tarot/fan', ['completion_key' => $key])
            ->assertOk()
            ->json('fan');

        $this->assertCount(TarotCatalog::FAN_SIZE, $fan['tokens']);
        $this->assertSame(1, $fan['needed']);
        $this->assertSame(0, $fan['picked']);

        $token = $fan['tokens'][0];
        $nonce = $fan['nonce'];

        $this->postJson('/api/v1/my/tarot/pick', [
            'completion_key' => $key,
            'slot_token' => $token,
            'draw_nonce' => 'stale-nonce',
        ])->assertUnprocessable();

        $picked = $this->postJson('/api/v1/my/tarot/pick', [
            'completion_key' => $key,
            'slot_token' => $token,
            'draw_nonce' => $nonce,
        ])->assertOk();

        $this->assertTrue($picked->json('complete'));
        $this->assertSame(1, $picked->json('fan.picked'));

        $reveal = $this->postJson('/api/v1/my/tarot/reveal', ['completion_key' => $key])
            ->assertOk()
            ->assertJsonPath('saved', false)
            ->assertJsonPath('reading.spread_type', 'one')
            ->assertJsonCount(1, 'reading.cards');

        $cardName = $reveal->json('reading.cards.0.card_name');
        $this->assertNotEmpty($cardName);
        $this->assertNotEmpty($reveal->json('reading.cards.0.insight'));

        $first = $this->postJson('/api/v1/my/tarot/save', ['completion_key' => $key])
            ->assertCreated()
            ->assertJsonPath('created', true);

        $id = (int) $first->json('reading.id');
        $this->assertSame(1, TarotReading::query()->count());

        $this->postJson('/api/v1/my/tarot/save', ['completion_key' => $key])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('reading.id', $id);

        $this->assertSame(1, TarotReading::query()->count());

        $this->getJson('/api/v1/my/tarot/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/my/tarot/readings/'.$id)->assertNotFound();

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/my/tarot/readings/'.$id)
            ->assertOk()
            ->assertJsonPath('reading.cards.0.card_name', $cardName);
    }

    public function test_validation_and_closed_tool(): void
    {
        Sanctum::actingAs($this->member('tarot-rules@example.com'));

        $this->postJson('/api/v1/my/tarot/category', [
            'completion_key' => (string) Str::uuid(),
            'category' => 'career-work',
        ])->assertUnprocessable();

        Tool::query()->where('slug', 'tarot-reflection')->update([
            'status' => 'inactive',
            'route_name' => null,
            'is_implemented' => false,
        ]);

        $this->getJson('/api/v1/tarot/options')
            ->assertOk()
            ->assertJsonPath('open', false);
    }

    private function storage(bool $open): void
    {
        foreach ([
            'tarot_reading_cards',
            'tarot_readings',
            'tarot_cards',
            'tools',
            'tool_categories',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
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
            $table->text('description')->nullable();
            $table->string('status');
            $table->string('route_name')->nullable();
            $table->unsignedInteger('display_order')->default(0);
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
            $table->string('category');
            $table->text('question');
            $table->string('spread_type');
            $table->boolean('reversed_enabled')->default(false);
            $table->text('overall_reflection')->nullable();
            $table->timestamps();
        });
        Schema::create('tarot_reading_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarot_reading_id')->constrained('tarot_readings')->cascadeOnDelete();
            $table->foreignId('tarot_card_id')->constrained('tarot_cards')->cascadeOnDelete();
            $table->string('position');
            $table->unsignedTinyInteger('position_order');
            $table->boolean('is_reversed')->default(false);
            $table->text('insight_text');
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
            $table->unique(['tarot_reading_id', 'position_order']);
            $table->unique(['tarot_reading_id', 'tarot_card_id']);
        });

        $this->seed(TarotCardSeeder::class);

        $category = ToolCategory::query()->create(['name' => 'Astrology & Tarot', 'slug' => 'astrology-tarot']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Tarot Reflection',
            'slug' => 'tarot-reflection',
            'module_key' => 'astrology-tarot',
            'description' => 'Reflective tarot.',
            'status' => $open ? 'active' : 'coming_soon',
            'route_name' => $open ? 'tools.tarot.intro' : null,
            'is_implemented' => $open,
        ]);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Member',
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }
}
