<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\MoodCheckinDraft;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodReflectionTest extends TestCase
{
    public function test_emotion_continue_opens_the_reflection_screen(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good'])
            ->assertRedirect(route('tools.psychology.mood.emotions'));

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['calm', 'grateful']])
            ->assertRedirect(route('tools.psychology.mood.reflection'));

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('Would you like to share more?')
            ->assertSee("Sometimes writing down what's on your mind can help.", false)
            ->assertSee('What helped you feel good today?')
            ->assertSee('Write anything you would like to share...')
            ->assertSee('Need a little inspiration?')
            ->assertSee('A kind moment with someone')
            ->assertSee('Something went smoothly today')
            ->assertSee('I took care of myself in a small way')
            ->assertDontSee('Something good happened today')
            ->assertSee('Optional · Up to 1,000 characters')
            ->assertSee('0 / 1,000')
            ->assertSee('Save Check-in')
            ->assertSee('Skip')
            ->assertSee('Free tool')
            ->assertSee('href="'.route('tools.psychology.mood.emotions').'"', false)
            ->assertSee('family=DM+Sans', false)
            ->assertSee('yuktione-mood.css', false)
            ->assertSee('Yuktione_logo.png', false)
            ->assertDontSee('This check-in is still in progress.')
            ->assertDontSee('Your check-in is complete');

        $this->assertFalse(Schema::hasTable('mood_checkins'));
    }

    public function test_reflection_requires_a_mood_and_an_emotion_step(): void
    {
        $this->get(route('tools.psychology.mood.reflection'))
            ->assertRedirect(route('tools.psychology.mood'));

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'sad']);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertRedirect(route('tools.psychology.mood.emotions'));

        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'A private note',
        ])->assertRedirect(route('tools.psychology.mood.emotions'));

        $this->assertFalse(session(MoodCheckinDraft::KEY)['reflection_held']);
    }

    public function test_reflection_is_saved_skipped_and_restored(): void
    {
        $this->reachReflection(['grateful']);

        $note = "A quiet morning.\nनमस्ते";
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => $note,
        ])->assertRedirect(route('login'));

        $draft = session(MoodCheckinDraft::KEY);
        $this->assertSame('reflection', $draft['step']);
        $this->assertSame('good', $draft['mood']);
        $this->assertSame(['grateful'], $draft['emotions']);
        $this->assertSame($note, $draft['reflection']);
        $this->assertTrue($draft['reflection_held']);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('This check-in is still in progress.')
            ->assertSee('A quiet morning.', false)
            ->assertSee('नमस्ते', false);

        $this->get(route('tools.psychology.mood.emotions'))
            ->assertOk()
            ->assertSee('value="grateful"', false);

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('value="good"', false);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertSee('नमस्ते', false);

        $this->post(route('tools.psychology.mood.reflection.store'), ['action' => 'skip'])
            ->assertRedirect(route('login'));

        $skipped = session(MoodCheckinDraft::KEY);
        $this->assertNull($skipped['reflection']);
        $this->assertTrue($skipped['reflection_held']);
        $this->assertSame('good', $skipped['mood']);
        $this->assertSame(['grateful'], $skipped['emotions']);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('This check-in is still in progress.')
            ->assertDontSee('नमस्ते');
    }

    public function test_invalid_reflection_payloads_are_rejected(): void
    {
        $this->reachReflection();

        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => str_repeat('a', 1001),
        ])->assertSessionHasErrors('reflection');
        $this->assertNull(session(MoodCheckinDraft::KEY)['reflection']);

        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => ['<script>alert(1)</script>'],
        ])->assertSessionHasErrors('reflection');

        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'publish',
            'reflection' => 'no',
        ])->assertSessionHasErrors('action');

        $this->assertSame('emotions', session(MoodCheckinDraft::KEY)['step']);
    }

    public function test_reflection_text_is_escaped_and_isolated(): void
    {
        $this->reachReflection(['grateful']);

        $payload = '<script>alert(1)</script>';
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => $payload,
        ]);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee($payload, false);

        $this->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'sad',
                'emotions' => ['lonely'],
                'reflection' => 'Someone else wrote this',
                'reflection_held' => true,
                'owner' => 'user:42',
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->get(route('tools.psychology.mood.reflection'))
            ->assertRedirect(route('tools.psychology.mood'));

        $this->withSession([
            'psychology.mood_guest' => 'guest-token',
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => 'An old note',
                'reflection_held' => true,
                'owner' => 'guest:guest-token',
                'expires_at' => now()->subMinute()->getTimestamp(),
            ],
        ])->get(route('tools.psychology.mood.reflection'))
            ->assertRedirect(route('tools.psychology.mood'));
    }

    public function test_a_closed_tool_rejects_reflection(): void
    {
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
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Psychology',
            'slug' => 'psychology',
            'display_order' => 1,
        ]);
        $tool = Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Daily Mood Check-in',
            'slug' => 'daily-mood-check-in',
            'module_key' => 'psychology',
            'status' => 'coming_soon',
            'is_implemented' => false,
            'display_order' => 1,
        ]);

        $this->reachReflection();
        $tool->update(['status' => 'unavailable']);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('unavailable right now')
            ->assertDontSee('Save Check-in');

        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Still private',
        ])->assertForbidden();
    }

    private function reachReflection(array $emotions = ['calm']): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => $emotions]);
    }
}
