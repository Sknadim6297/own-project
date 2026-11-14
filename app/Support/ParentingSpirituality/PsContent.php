<?php

namespace App\Support\ParentingSpirituality;

/**
 * Client-approved content pack (CONTENT v3).
 */
class PsContent
{
    /**
     * Parenting Stress Check questions (CONTENT 01_Parenting_Stress).
     *
     * @return list<array{id:int,question:string,area:string,reverse:bool}>
     */
    public static function stressQuestions(): array
    {
        return [
            ['id' => 1, 'question' => 'I feel overwhelmed by the number of things I have to manage as a parent.', 'area' => 'Overload', 'reverse' => false],
            ['id' => 2, 'question' => 'I feel physically or mentally exhausted by my parenting responsibilities.', 'area' => 'Exhaustion', 'reverse' => false],
            ['id' => 3, 'question' => 'I often feel guilty that I am not doing enough for my child.', 'area' => 'Guilt', 'reverse' => false],
            ['id' => 4, 'question' => 'I find it difficult to stay patient when my child is upset or not cooperating.', 'area' => 'Patience', 'reverse' => false],
            ['id' => 5, 'question' => 'I have someone I can genuinely ask for help when parenting feels difficult.', 'area' => 'Support', 'reverse' => true],
            ['id' => 6, 'question' => 'I get enough time to rest, recover or do something that helps me recharge.', 'area' => 'Recovery', 'reverse' => true],
            ['id' => 7, 'question' => 'Work, household responsibilities and parenting often compete for my attention.', 'area' => 'Work/Home Load', 'reverse' => false],
            ['id' => 8, 'question' => 'I often feel that I am handling parenting challenges mostly on my own.', 'area' => 'Aloneness', 'reverse' => false],
        ];
    }

    /**
     * @return array<string, array{title:string,content:string}>
     */
    public static function stressActions(): array
    {
        return [
            'Overload' => [
                'title' => 'Reduce the load, not your standards.',
                'content' => 'Choose one task this week that can be postponed, simplified or delegated.',
            ],
            'Exhaustion' => [
                'title' => 'Protect a small recovery window.',
                'content' => 'Block 15–20 minutes for uninterrupted rest or a restorative activity.',
            ],
            'Guilt' => [
                'title' => "Replace 'perfect parent' with 'good-enough parent'.",
                'content' => 'Write down one thing you did well for your child today.',
            ],
            'Patience' => [
                'title' => 'Create a pause before responding.',
                'content' => 'When tension rises, pause for 10 seconds, lower your voice and respond to the issue—not the emotion.',
            ],
            'Support' => [
                'title' => 'Make support specific.',
                'content' => 'Ask one person for one concrete task instead of asking generally for help.',
            ],
            'Recovery' => [
                'title' => 'Treat recovery as part of parenting.',
                'content' => 'Schedule one small activity that restores you and protect it like an appointment.',
            ],
            'Work/Home Load' => [
                'title' => 'Separate urgent from important.',
                'content' => 'Pick the three non-negotiables for today; let lower-priority tasks wait.',
            ],
            'Aloneness' => [
                'title' => 'Build one reliable support point.',
                'content' => 'Identify one trusted person or professional you could contact when things feel too heavy.',
            ],
        ];
    }

    /**
     * Pressure area groups for ranking (logic sheet).
     *
     * @return array<string, list<string>>
     */
    public static function stressAreaGroups(): array
    {
        return [
            'overload' => ['Overload', 'Work/Home Load'],
            'exhaustion' => ['Exhaustion'],
            'guilt_patience' => ['Guilt', 'Patience'],
            'support_recovery' => ['Support', 'Recovery', 'Aloneness'],
        ];
    }

    /**
     * @return list<array{q:int,a:string,b:string,prompt:string}>
     */
    public static function valuePairs(): array
    {
        return [
            ['q' => 1, 'a' => 'Freedom', 'b' => 'Security', 'prompt' => 'Which matters more to you right now?'],
            ['q' => 2, 'a' => 'Connection', 'b' => 'Independence', 'prompt' => 'Which would you protect if you could choose only one?'],
            ['q' => 3, 'a' => 'Creativity', 'b' => 'Stability', 'prompt' => 'Which gives you more energy?'],
            ['q' => 4, 'a' => 'Growth', 'b' => 'Comfort', 'prompt' => 'Which would you rather move toward?'],
            ['q' => 5, 'a' => 'Contribution', 'b' => 'Achievement', 'prompt' => 'Which feels more meaningful?'],
            ['q' => 6, 'a' => 'Adventure', 'b' => 'Predictability', 'prompt' => 'Which would you choose for the next chapter?'],
            ['q' => 7, 'a' => 'Learning', 'b' => 'Recognition', 'prompt' => 'Which would you still want without external praise?'],
            ['q' => 8, 'a' => 'Family', 'b' => 'Ambition', 'prompt' => 'Which needs more attention from you right now?'],
            ['q' => 9, 'a' => 'Honesty', 'b' => 'Harmony', 'prompt' => 'Which would you prioritise in a difficult conversation?'],
            ['q' => 10, 'a' => 'Health', 'b' => 'Productivity', 'prompt' => 'Which should influence your schedule more?'],
            ['q' => 11, 'a' => 'Purpose', 'b' => 'Pleasure', 'prompt' => 'Which feels more important when making a major decision?'],
            ['q' => 12, 'a' => 'Belonging', 'b' => 'Self-expression', 'prompt' => 'Which feels more missing right now?'],
            ['q' => 13, 'a' => 'Compassion', 'b' => 'Fairness', 'prompt' => 'Which principle matters more in conflict?'],
            ['q' => 14, 'a' => 'Curiosity', 'b' => 'Certainty', 'prompt' => 'Which helps you make better decisions?'],
            ['q' => 15, 'a' => 'Discipline', 'b' => 'Spontaneity', 'prompt' => 'Which quality would improve your life more?'],
            ['q' => 16, 'a' => 'Peace', 'b' => 'Progress', 'prompt' => 'Which would you choose if you could strengthen only one this year?'],
        ];
    }

    /**
     * @return array<string, array{theme:string,action:string}>
     */
    public static function valueMappings(): array
    {
        return [
            'Freedom' => ['theme' => 'Autonomy / Choice', 'action' => 'Create one area of the week with fewer unnecessary constraints.'],
            'Security' => ['theme' => 'Stability / Safety', 'action' => 'Review one financial, work or home uncertainty and define a practical next step.'],
            'Connection' => ['theme' => 'Relationships / Belonging', 'action' => 'Schedule one meaningful conversation or shared activity.'],
            'Independence' => ['theme' => 'Autonomy / Confidence', 'action' => 'Take ownership of one decision you have been postponing.'],
            'Creativity' => ['theme' => 'Expression / Exploration', 'action' => 'Make 30 minutes for creating, designing or experimenting.'],
            'Stability' => ['theme' => 'Security / Structure', 'action' => 'Create one repeatable routine that reduces daily friction.'],
            'Growth' => ['theme' => 'Learning / Development', 'action' => 'Choose one skill or area to improve over the next month.'],
            'Comfort' => ['theme' => 'Wellbeing / Ease', 'action' => 'Remove one avoidable source of friction from your week.'],
            'Contribution' => ['theme' => 'Service / Impact', 'action' => 'Identify one person, team or cause you can help meaningfully.'],
            'Achievement' => ['theme' => 'Progress / Mastery', 'action' => 'Define one measurable milestone and the smallest next action.'],
            'Adventure' => ['theme' => 'Exploration / Novelty', 'action' => 'Try one new place, activity or learning experience.'],
            'Predictability' => ['theme' => 'Structure / Stability', 'action' => 'Clarify one uncertain commitment or decision.'],
            'Learning' => ['theme' => 'Curiosity / Mastery', 'action' => 'Set one learning goal and a weekly practice slot.'],
            'Recognition' => ['theme' => 'Achievement / Visibility', 'action' => 'Identify work that deserves clearer evidence or communication of impact.'],
            'Family' => ['theme' => 'Relationships / Care', 'action' => 'Protect one recurring family connection point.'],
            'Ambition' => ['theme' => 'Progress / Achievement', 'action' => 'Choose one stretch goal and define the next measurable step.'],
            'Honesty' => ['theme' => 'Integrity / Authenticity', 'action' => 'Have one conversation where you state your position clearly and respectfully.'],
            'Harmony' => ['theme' => 'Peace / Relationships', 'action' => 'Identify one conflict where a calmer boundary could reduce friction.'],
            'Health' => ['theme' => 'Wellbeing / Vitality', 'action' => 'Choose one sustainable routine that supports your physical wellbeing.'],
            'Productivity' => ['theme' => 'Progress / Effectiveness', 'action' => 'Remove or automate one low-value task.'],
            'Purpose' => ['theme' => 'Meaning / Contribution', 'action' => 'Connect one current task to the bigger reason it matters.'],
            'Pleasure' => ['theme' => 'Joy / Wellbeing', 'action' => 'Add one healthy, enjoyable activity without turning it into a productivity task.'],
            'Belonging' => ['theme' => 'Connection / Community', 'action' => 'Join or reconnect with one group, person or community.'],
            'Self-expression' => ['theme' => 'Creativity / Authenticity', 'action' => 'Make one decision that reflects your genuine preference rather than expectation.'],
            'Compassion' => ['theme' => 'Care / Empathy', 'action' => 'Practise one response that is firm without being harsh.'],
            'Fairness' => ['theme' => 'Integrity / Justice', 'action' => 'Clarify what fair treatment looks like for everyone involved.'],
            'Curiosity' => ['theme' => 'Learning / Exploration', 'action' => 'Replace one assumption with a question.'],
            'Certainty' => ['theme' => 'Security / Clarity', 'action' => 'List what is known, unknown and controllable in one decision.'],
            'Discipline' => ['theme' => 'Structure / Mastery', 'action' => 'Create a small repeatable habit rather than relying on motivation.'],
            'Spontaneity' => ['theme' => 'Freedom / Novelty', 'action' => 'Leave one small part of your week intentionally unplanned.'],
            'Peace' => ['theme' => 'Calm / Balance', 'action' => 'Protect one quiet period and reduce one avoidable source of noise.'],
            'Progress' => ['theme' => 'Growth / Achievement', 'action' => "Define what 'better' looks like and the next visible step."],
        ];
    }

    /**
     * Conversation guide rows from CONTENT 03.
     *
     * @return list<array<string, string>>
     */
    public static function conversationGuides(): array
    {
        return [
            [
                'situation' => 'screen_time',
                'situation_label' => 'Screen time',
                'age_band' => '8-12',
                'goal' => 'boundary',
                'goal_label' => 'Set a boundary without escalating',
                'opening' => "I know you want more time on the device. Let's talk about what feels fair and what we need to finish first.",
                'say' => 'First ask what they were doing and why stopping feels difficult. Then explain the boundary briefly. Offer a clear next chance to use the device.',
                'avoid' => "You're addicted. / Because I said so. / Threatening to take everything away.",
                'shutdown' => "If the child shuts down, pause the argument and say: 'We don't have to solve it this second. We can come back to it when we're calmer.'",
                'next' => 'Agree on one specific rule and one review time.',
            ],
            [
                'situation' => 'studies',
                'situation_label' => 'Studies',
                'age_band' => '8-12',
                'goal' => 'understand',
                'goal_label' => 'Understand resistance and create a workable plan',
                'opening' => 'I can see starting this feels difficult. Let’s figure out which part is getting in the way.',
                'say' => 'Ask whether the problem is understanding, boredom, fatigue, workload or fear of getting it wrong. Break the task into one small start.',
                'avoid' => 'Comparing them with other children. / Calling them lazy. / Turning every conversation into marks.',
                'shutdown' => 'Offer a short reset and return with one smaller task rather than repeating the whole lecture.',
                'next' => 'Choose one 20–30 minute study block and review how it went.',
            ],
            [
                'situation' => 'anger',
                'situation_label' => 'Anger',
                'age_band' => '8-12',
                'goal' => 'boundary',
                'goal_label' => 'Set a boundary while validating emotion',
                'opening' => "It's okay to be angry. It's not okay to hurt people or break things. Let's work out what happened.",
                'say' => 'Name the emotion without approving harmful behavior. Give space. Discuss the trigger after calm returns.',
                'avoid' => 'Shaming the child for being angry. / Arguing during peak anger. / Threatening consequences immediately.',
                'shutdown' => 'Give time to settle, then revisit the situation using short questions.',
                'next' => "Agree on one safe way to signal 'I need a break.'",
            ],
            [
                'situation' => 'friends',
                'situation_label' => 'Friends',
                'age_band' => '13-17',
                'goal' => 'understand',
                'goal_label' => 'Stay connected while discussing a concern',
                'opening' => 'I want to understand what happened from your side before I give my opinion.',
                'say' => "Ask open questions. Listen before advising. Separate the child's friend from the specific behavior you are concerned about.",
                'avoid' => 'Insulting the friend. / Interrogating. / Assuming the worst.',
                'shutdown' => 'If they stop talking, reassure them that the door is open and avoid forcing an immediate disclosure.',
                'next' => 'Ask one low-pressure follow-up question later.',
            ],
            [
                'situation' => 'confidence',
                'situation_label' => 'Confidence',
                'age_band' => '8-17',
                'goal' => 'reassure',
                'goal_label' => 'Support confidence without empty praise',
                'opening' => 'I noticed how you handled that. What part felt difficult, and what helped you keep going?',
                'say' => 'Praise effort, strategy and persistence. Ask what they want help with rather than immediately fixing it.',
                'avoid' => "Comparing siblings. / 'You're the best' when the child is struggling. / Taking over every problem.",
                'shutdown' => 'If they reject praise, keep it specific and factual rather than arguing with them.',
                'next' => 'Identify one skill they want to practise this week.',
            ],
            [
                'situation' => 'discipline',
                'situation_label' => 'Discipline',
                'age_band' => '5-12',
                'goal' => 'boundary',
                'goal_label' => 'Set a clear boundary without humiliation',
                'opening' => 'We need to talk about what happened and what the rule is. I want us to work out what happens next.',
                'say' => 'State the behavior, the boundary and the consequence calmly. Keep the consequence proportionate and predictable.',
                'avoid' => 'Name-calling. / Public shaming. / Changing consequences in anger.',
                'shutdown' => 'If the child refuses to talk, give a short pause and return to the same boundary later.',
                'next' => 'Restate the rule and agree on the next opportunity to repair.',
            ],
        ];
    }

    /**
     * @return list<array{state:string,duration:int,practice:string,purpose:string,script:string}>
     */
    public static function mindfulnessPractices(): array
    {
        return [
            [
                'state' => 'stressed',
                'duration' => 3,
                'practice' => 'Breathing',
                'purpose' => 'Settle the body',
                'script' => 'Sit comfortably. Let your shoulders drop. Notice your breathing without changing it. Slowly breathe in. Slowly breathe out. Repeat for three minutes. When your mind wanders, gently return to the next breath.',
            ],
            [
                'state' => 'restless',
                'duration' => 5,
                'practice' => 'Grounding',
                'purpose' => 'Come back to the present',
                'script' => 'Notice 5 things you can see. Notice 4 things you can physically feel. Notice 3 sounds. Notice 2 smells or sensations. Notice 1 thing you are grateful to have in this moment. Finish with three slow breaths.',
            ],
            [
                'state' => 'angry',
                'duration' => 5,
                'practice' => 'Breathing',
                'purpose' => 'Create a pause before reacting',
                'script' => 'Do not try to force the anger away. Notice where it feels strongest in the body. Breathe in gently and make the exhale a little longer. Repeat. At the end, name one thing you can control in the next ten minutes.',
            ],
            [
                'state' => 'distracted',
                'duration' => 3,
                'practice' => 'Quiet focus',
                'purpose' => 'Return attention to one anchor',
                'script' => 'Choose one sound or the feeling of your feet on the floor. Stay with it for one breath at a time. When thoughts pull you away, notice that and return without judging yourself.',
            ],
            [
                'state' => 'overwhelmed',
                'duration' => 10,
                'practice' => 'Body scan',
                'purpose' => 'Slow down and notice',
                'script' => 'Start at your feet and move attention upward: feet, legs, abdomen, chest, shoulders, face. At each area, notice tension without needing to fix it. Breathe slowly and allow each area to soften if it naturally does.',
            ],
            [
                'state' => 'general_calm',
                'duration' => 5,
                'practice' => 'Quiet focus',
                'purpose' => 'Create a calm reset',
                'script' => 'Sit or lie comfortably. Notice the contact between your body and the surface supporting you. Follow five natural breaths. Notice sounds around you. Return to your breath. Finish by choosing one small, manageable next step.',
            ],
        ];
    }
}
