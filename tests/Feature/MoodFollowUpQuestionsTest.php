<?php

namespace Tests\Feature;

use App\Services\MoodCheckin;
use App\Services\MoodCheckinDraft;
use App\Support\Psychology\MoodFollowUpCatalog;
use App\Support\Psychology\PsychologyCatalog;
use Tests\TestCase;

class MoodFollowUpQuestionsTest extends TestCase
{
    public function test_every_mood_has_a_follow_up_set_using_catalog_emotion_keys(): void
    {
        $catalog = app(MoodCheckin::class);
        $allEmotions = PsychologyCatalog::emotionKeys();

        foreach (PsychologyCatalog::moodKeys() as $mood) {
            $this->assertTrue(MoodFollowUpCatalog::hasMood($mood), $mood.' missing follow-up set');
            $set = MoodFollowUpCatalog::forMood($mood);
            $this->assertNotSame('', $set['emotion_heading']);
            $this->assertNotSame('', $set['reflection_question']);
            $this->assertNotEmpty($set['emotion_keys']);
            $this->assertNotEmpty($set['reflection_prompts']);
            $this->assertCount(0, array_diff($set['emotion_keys'], $allEmotions), $mood.' uses unknown emotion keys');
            $this->assertSame($set['emotion_keys'], $catalog->emotionKeysForMood($mood));
        }
    }

    public function test_each_mood_shows_its_own_emotion_question_set(): void
    {
        $expectations = [
            'very-happy' => [
                'heading' => 'What is lifting your mood?',
                'see' => ['Happy', 'Excited', 'Grateful'],
                'hide' => ['Angry', 'Anxious', 'Lonely'],
            ],
            'good' => [
                'heading' => 'What feels good right now?',
                'see' => ['Happy', 'Calm', 'Thoughtful'],
                'hide' => ['Angry', 'Anxious', 'Worried'],
            ],
            'neutral' => [
                'heading' => 'How would you describe this in-between feeling?',
                'see' => ['Neutral', 'Quiet', 'Unsure'],
                'hide' => ['Angry', 'Excited', 'Loved'],
            ],
            'sad' => [
                'heading' => 'What emotions are sitting with you?',
                'see' => ['Sad', 'Lonely', 'Worried'],
                'hide' => ['Excited', 'Angry'],
            ],
            'stressed' => [
                'heading' => 'What is showing up with this stress?',
                'see' => ['Anxious', 'Worried', 'Angry'],
                'hide' => ['Happy', 'Excited', 'Loved'],
            ],
        ];

        foreach ($expectations as $mood => $expect) {
            $this->flushSession();
            $this->post(route('tools.psychology.mood.store'), ['mood' => $mood])
                ->assertRedirect(route('tools.psychology.mood.emotions'));

            $page = $this->get(route('tools.psychology.mood.emotions'))->assertOk();
            $page->assertSee($expect['heading']);
            foreach ($expect['see'] as $label) {
                $page->assertSee($label);
            }
            foreach ($expect['hide'] as $label) {
                $page->assertDontSee('>'.$label.'<', false);
            }
        }
    }

    public function test_reflection_prompts_and_question_follow_the_selected_mood(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'stressed']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['anxious']]);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('What is contributing to the stress right now?')
            ->assertSee('Something feels hard to manage')
            ->assertSee('I need a moment to feel calmer')
            ->assertDontSee('Something good happened today')
            ->assertDontSee('What made you feel this way today?');

        $this->flushSession();
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'very-happy']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['happy']]);

        $this->get(route('tools.psychology.mood.reflection'))
            ->assertOk()
            ->assertSee('What contributed to this positive feeling today?')
            ->assertSee('Something or someone made me smile')
            ->assertDontSee('Something feels hard to manage');
    }

    public function test_emotions_from_another_mood_are_rejected_server_side(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['anxious']])
            ->assertSessionHasErrors('emotions.0');
        $this->assertSame([], session(MoodCheckinDraft::KEY)['emotions'] ?? []);

        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['happy', 'calm']])
            ->assertRedirect(route('tools.psychology.mood.reflection'));
        $this->assertSame(['happy', 'calm'], session(MoodCheckinDraft::KEY)['emotions']);
    }

    public function test_changing_mood_clears_incompatible_follow_up_answers(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'good']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['happy', 'calm']]);
        $this->post(route('tools.psychology.mood.reflection.store'), [
            'action' => 'save',
            'reflection' => 'Kept for this mood',
        ])->assertRedirect(route('login'));

        $this->assertTrue(session(MoodCheckinDraft::KEY)['reflection_held']);
        $this->assertSame(['happy', 'calm'], session(MoodCheckinDraft::KEY)['emotions']);

        $this->get(route('tools.psychology.mood'))
            ->assertOk()
            ->assertSee('data-mood-has-followup="1"', false);

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'sad'])
            ->assertRedirect(route('tools.psychology.mood.emotions'));

        $draft = session(MoodCheckinDraft::KEY);
        $this->assertSame('sad', $draft['mood']);
        $this->assertSame([], $draft['emotions']);
        $this->assertNull($draft['reflection']);
        $this->assertFalse($draft['reflection_held']);
    }

    public function test_same_mood_resubmit_keeps_follow_up_answers(): void
    {
        $this->post(route('tools.psychology.mood.store'), ['mood' => 'neutral']);
        $this->post(route('tools.psychology.mood.emotions.store'), ['emotions' => ['quiet', 'thoughtful']]);

        $this->post(route('tools.psychology.mood.store'), ['mood' => 'neutral'])
            ->assertRedirect(route('tools.psychology.mood.emotions'));

        $draft = session(MoodCheckinDraft::KEY);
        $this->assertSame('neutral', $draft['mood']);
        $this->assertSame(['quiet', 'thoughtful'], $draft['emotions']);
    }
}
