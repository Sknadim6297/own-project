<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\ToolConfigItem;
use App\Services\MoodCheckinDraft;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoodEmotionTest extends TestCase
{
    public function test_emotion_screen_requires_a_primary_mood_and_lists_mood_specific_options(): void
    {
        $this->get(route('tools.psychology.mood.emotions'))
            ->assertRedirect(route('tools.psychology.mood'));

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good'])
            ->assertRedirect(route('tools.psychology.mood.emotions'));

        $response = $this->get(route('tools.psychology.mood.emotions'));
        $response->assertOk()
            ->assertSee('What feels good right now?')
            ->assertSee('Choose up to 6 emotions that describe this steady, positive feeling.')
            ->assertSee('Free tool')
            ->assertSee('Positive')
            ->assertSee('Neutral')
            ->assertSee('Happy')
            ->assertSee('Calm')
            ->assertSee('Loved')
            ->assertSee('Grateful')
            ->assertSee('Thoughtful')
            ->assertDontSee('>Excited<', false)
            ->assertDontSee('>Angry<', false)
            ->assertDontSee('>Anxious<', false)
            ->assertSee('0 of 6 selected')
            ->assertSee('href="'.route('tools.psychology.mood').'"', false)
            ->assertSee('family=DM+Sans', false)
            ->assertSee('yuktione-mood.css', false)
            ->assertSee('Yuktione_logo.png', false)
            ->assertDontSee('data-selected="true"', false)
            ->assertDontSee('name="journal"', false);

        $this->assertSame(5, substr_count($response->getContent(), 'class="yk-emotion__input"'));
    }

    public function test_emotions_are_validated_and_kept_in_the_draft(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'stressed']);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['Happy']])
            ->assertSessionHasErrors('emotions.0');
        $this->assertSame([], session(MoodCheckinDraft::KEY)['emotions']);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['anxious', 'anxious']])
            ->assertSessionHasErrors('emotions.0');

        $this->post(route('tools.psychology.mood.emotions.store'), [
            'emotions' => ['anxious', 'worried', 'tired', 'angry', 'unsure', 'calm', 'happy'],
        ])->assertSessionHasErrors('emotions');
        $this->assertSame([], session(MoodCheckinDraft::KEY)['emotions']);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['anxious', 'calm']])
            ->assertRedirect(route('tools.psychology.mood.reflection'));

        $page = $this->get(route('tools.psychology.mood.emotions'));
        $page->assertSee('value="anxious"', false)
            ->assertSee('value="calm"', false)
            ->assertSee('2 of 6 selected');
        $this->assertSame(2, substr_count($page->getContent(), 'data-selected="true"'));

        $draft = session(MoodCheckinDraft::KEY);
        $this->assertSame('emotions', $draft['step']);
        $this->assertSame('stressed', $draft['mood']);
        $this->assertSame(['anxious', 'calm'], $draft['emotions']);
        $this->assertArrayNotHasKey('label', $draft);

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('value="stressed"', false)
            ->assertSee('data-selected="true"', false);

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'sad'])
            ->assertRedirect(route('tools.psychology.mood.emotions'));
        $this->assertSame([], session(MoodCheckinDraft::KEY)['emotions']);
        $this->assertSame('sad', session(MoodCheckinDraft::KEY)['mood']);

        $this->post(route('tools.psychology.mood.emotions.store'), [])
            ->assertRedirect(route('tools.psychology.mood.reflection'));
        $this->assertSame([], session(MoodCheckinDraft::KEY)['emotions']);
        $this->assertSame('sad', session(MoodCheckinDraft::KEY)['mood']);
    }

    public function test_a_draft_without_a_mood_cannot_store_emotions(): void
    {
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['happy']])
            ->assertRedirect(route('tools.psychology.mood'));

        $this->assertNull(session(MoodCheckinDraft::KEY));
    }

    public function test_emotion_drafts_stay_with_their_owner_and_expire(): void
    {
        $this->withSession([
            MoodCheckinDraft::KEY => [
                'step' => 'emotions',
                'mood' => 'good',
                'emotions' => ['happy'],
                'owner' => 'user:42',
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ],
        ])->get(route('tools.psychology.mood.emotions'))
            ->assertRedirect(route('tools.psychology.mood'));

        $this->withSession([
            'psychology.mood_guest' => 'guest-token',
            MoodCheckinDraft::KEY => [
                'step' => 'emotions',
                'mood' => 'good',
                'emotions' => ['calm'],
                'owner' => 'guest:guest-token',
                'expires_at' => now()->subMinute()->getTimestamp(),
            ],
        ])->get(route('tools.psychology.mood.emotions'))
            ->assertRedirect(route('tools.psychology.mood'));
    }

    public function test_admin_emotion_labels_are_used_and_a_closed_tool_rejects_selection(): void
    {
        $this->registry();
        $tool = Tool::query()->where('slug', 'daily-mood-check-in')->first();
        ToolConfigItem::query()->create([
            'tool_id' => $tool->id,
            'group_key' => 'emotion',
            'item_key' => 'happy',
            'label' => 'Joyful',
            'bucket' => 'positive',
            'is_active' => true,
            'is_system' => true,
            'display_order' => 1,
        ]);

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $this->get(route('tools.psychology.mood.emotions'))
            ->assertOk()
            ->assertSee('Joyful')
            ->assertDontSee('>Happy<', false);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['happy']])
            ->assertRedirect(route('tools.psychology.mood.reflection'));
        $this->assertSame(['happy'], session(MoodCheckinDraft::KEY)['emotions']);

        $tool->update(['status' => 'unavailable']);

        $this->get(route('tools.psychology.mood.emotions'))
            ->assertOk()
            ->assertSee('unavailable right now')
            ->assertDontSee('Joyful');

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['calm']])
            ->assertForbidden();
    }

    private function registry(): void
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
        Schema::create('tool_config_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('group_key');
            $table->string('item_key');
            $table->string('label');
            $table->text('hint')->nullable();
            $table->text('detail')->nullable();
            $table->string('bucket')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        $category = ToolCategory::query()->create([
            'name' => 'Psychology',
            'slug' => 'psychology',
            'display_order' => 1,
        ]);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Daily Mood Check-in',
            'slug' => 'daily-mood-check-in',
            'module_key' => 'psychology',
            'status' => 'coming_soon',
            'is_implemented' => false,
            'display_order' => 1,
        ]);
    }
}
