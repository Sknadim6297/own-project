<?php

namespace Tests\Feature;

use App\Models\TarotReading;
use App\Models\User;
use App\Services\TarotDraft;
use App\Services\ToolRegistryRegistrar;
use App\Support\Tarot\TarotCatalog;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 18 — end-to-end Tarot Reflection workflow gaps:
 * unfinished draft resume/restart, every category, both spreads through save/history,
 * sticky reveal CTA CSS, and Task 17 button regressions stay intact.
 */
class TarotReflectionWorkflowTest extends TestCase
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

    public function test_intro_offers_continue_and_restart_when_draft_exists(): void
    {
        $user = $this->member('resume-ui@example.com');
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'));
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => 'personal-growth',
        ]);

        $intro = $this->actingAs($user, 'user')->get(route('tools.tarot.intro'));
        $intro->assertOk()
            ->assertSee('Continue Reading')
            ->assertSee('Start a new reading')
            ->assertDontSee('>Start a Reading<', false);
    }

    public function test_continue_resumes_unfinished_draft_without_wiping_category(): void
    {
        $user = $this->member('resume@example.com');
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'));
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => 'life-direction',
        ]);
        $keyBefore = session(TarotDraft::KEY)['completion_key'];

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.start'))
            ->assertRedirect(route('tools.tarot.question'));

        $draft = session(TarotDraft::KEY);
        $this->assertSame($keyBefore, $draft['completion_key']);
        $this->assertSame('life-direction', $draft['category']);
    }

    public function test_restart_clears_draft_and_keeps_prior_saved_readings(): void
    {
        $user = $this->member('restart@example.com');
        $this->completeThroughSave($user, 'one', 'money-finances', 'How can I approach money decisions with more clarity?');
        $this->assertSame(1, TarotReading::query()->where('user_id', $user->id)->count());
        $savedKey = TarotReading::query()->firstOrFail()->completion_key;

        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.start'), ['restart' => '1'])
            ->assertRedirect(route('tools.tarot.category'));

        $draft = session(TarotDraft::KEY);
        $this->assertNotSame($savedKey, $draft['completion_key']);
        $this->assertNull($draft['category']);
        $this->assertSame(1, TarotReading::query()->where('user_id', $user->id)->count());
    }

    public function test_every_existing_category_can_be_selected(): void
    {
        $user = $this->member('categories@example.com');
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'));

        foreach (TarotCatalog::categoryKeys() as $key) {
            $this->actingAs($user, 'user')
                ->post(route('tools.tarot.category.store'), ['category' => $key])
                ->assertRedirect(route('tools.tarot.question'));
            $this->assertSame($key, session(TarotDraft::KEY)['category']);
        }
    }

    public function test_three_card_spread_save_and_history_preserves_positions(): void
    {
        $user = $this->member('three-e2e@example.com');
        $question = 'What may help me grow in my current role?';
        $this->completeThroughSave($user, 'three', 'career-work', $question);

        $reading = TarotReading::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('three', $reading->spread_type);
        $this->assertSame('career-work', $reading->category);
        $this->assertSame($question, $reading->question);
        $this->assertSame(
            ['situation', 'challenge', 'guidance'],
            $reading->cards()->orderBy('position_order')->pluck('position')->all()
        );

        $this->actingAs($user, 'user')
            ->get(route('tools.tarot.history'))
            ->assertOk()
            ->assertSee('Career & Work')
            ->assertSee('Three Cards')
            ->assertSee('Open Reading');

        $this->actingAs($user, 'user')
            ->get(route('tools.tarot.history.show', $reading))
            ->assertOk()
            ->assertSee('Situation')
            ->assertSee('Challenge')
            ->assertSee('Guidance')
            ->assertSee($question);
    }

    public function test_one_card_spread_still_saves_independently(): void
    {
        $user = $this->member('one-e2e@example.com');
        $this->completeThroughSave(
            $user,
            'one',
            'general-guidance',
            'What may be useful to notice in my situation right now?'
        );

        $reading = TarotReading::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('one', $reading->spread_type);
        $this->assertSame(1, $reading->cards()->count());
        $this->assertSame('quick_clarity', $reading->cards()->first()->position);
    }

    public function test_reveal_and_reading_keep_sticky_mobile_cta_styles(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-tarot.css'));
        $this->assertStringContainsString('.yk-tarot[data-tarot-reveal] .yk-tarot__actions', $css);
        $this->assertStringContainsString('.yk-tarot__final-ctas', $css);
        $this->assertStringContainsString('position: fixed', $css);
        $this->assertStringContainsString('overflow-x: clip', $css);
        $this->assertStringContainsString('padding-right: calc(0.85rem + 76px)', $css);

        $reveal = file_get_contents(resource_path('views/frontend/sections/tarot/reveal.blade.php'));
        $this->assertStringContainsString('data-tarot-reveal', $reveal);
        $this->assertStringContainsString('See Full Reading', $reveal);
    }

    public function test_task17_button_guards_remain_in_force(): void
    {
        $js = file_get_contents(public_path('frontend/assets/js/yuktione-tarot.js'));
        $this->assertStringContainsString('markSubmitterBusy', $js);
        $this->assertStringNotContainsString('btn.disabled = true', $js);
        $this->assertStringContainsString("form.closest('.yk-tarot__fan')", $js);

        $draw = file_get_contents(resource_path('views/frontend/sections/tarot/draw.blade.php'));
        $this->assertStringContainsString('data-tarot-draw-once', $draw);
        $this->assertStringNotContainsString('data-tarot-once', $draw);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Task 18 User',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function completeThroughSave(User $user, string $spread, string $category, string $question): void
    {
        $this->actingAs($user, 'user')->post(route('tools.tarot.start'), ['restart' => '1']);
        $this->actingAs($user, 'user')->post(route('tools.tarot.category.store'), [
            'category' => $category,
        ]);
        $this->actingAs($user, 'user')->post(route('tools.tarot.question.store'), [
            'question' => $question,
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
        $this->actingAs($user, 'user')->get(route('tools.tarot.reading'))->assertOk();
        $this->actingAs($user, 'user')
            ->post(route('tools.tarot.save'))
            ->assertRedirect();
    }
}
