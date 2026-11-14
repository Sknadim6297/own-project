@php
    $situations = [
        'screen_time' => 'Screen time',
        'studies' => 'Studies',
        'anger' => 'Anger',
        'friends' => 'Friends',
        'confidence' => 'Confidence',
        'discipline' => 'Discipline',
    ];
    $ages = ['5-7' => 'Ages 5–7', '8-12' => 'Ages 8–12', '13-17' => 'Ages 13–17'];
    $goals = [
        'understand' => 'Understand',
        'boundary' => 'Set a boundary',
        'reassure' => 'Reassure',
        'repair' => 'Repair',
    ];
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <fieldset class="yk-pfm__fieldset">
        <legend>What is the conversation about?</legend>
        <div class="yk-pfm__choice-grid">
            @foreach ($situations as $value => $label)
                <label class="yk-pfm__choice">
                    <input type="radio" name="situation" value="{{ $value }}" @checked(($old['situation'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <fieldset class="yk-pfm__fieldset">
        <legend>Child age group</legend>
        <div class="yk-pfm__choice-grid">
            @foreach ($ages as $value => $label)
                <label class="yk-pfm__choice">
                    <input type="radio" name="age_band" value="{{ $value }}" @checked(($old['age_band'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <fieldset class="yk-pfm__fieldset">
        <legend>Conversation goal</legend>
        <div class="yk-pfm__choice-grid">
            @foreach ($goals as $value => $label)
                <label class="yk-pfm__choice">
                    <input type="radio" name="goal" value="{{ $value }}" @checked(($old['goal'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    <label class="yk-pfm__label" for="context">Short context — what happened?</label>
    <textarea class="yk-pfm__input" id="context" name="context" rows="5" minlength="10" maxlength="1000" required>{{ $old['context'] ?? '' }}</textarea>
    <p class="yk-pfm__note">10–1000 characters. Do not share identifying details you are uncomfortable saving.</p>
</div>
