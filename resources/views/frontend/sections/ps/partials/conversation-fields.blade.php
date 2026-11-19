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
<div class="yk-ps__step is-active" data-ps-step="0">
    <fieldset class="yk-ps__question yk-ps__question--plain" data-ps-required-group>
        <legend><span>What is the conversation about?</span></legend>
        <div class="yk-ps__choice-grid yk-ps__choice-grid--3">
            @foreach ($situations as $value => $label)
                <label class="yk-ps__choice">
                    <input type="radio" name="situation" value="{{ $value }}" @checked(($old['situation'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                    <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-ps__step" data-ps-step="1">
    <fieldset class="yk-ps__question yk-ps__question--plain" data-ps-required-group>
        <legend><span>Child age group</span></legend>
        <div class="yk-ps__choice-grid yk-ps__choice-grid--3">
            @foreach ($ages as $value => $label)
                <label class="yk-ps__choice">
                    <input type="radio" name="age_band" value="{{ $value }}" @checked(($old['age_band'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                    <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                </label>
            @endforeach
        </div>
    </fieldset>
    <fieldset class="yk-ps__question yk-ps__question--plain" data-ps-required-group>
        <legend><span>Conversation goal</span></legend>
        <div class="yk-ps__choice-grid yk-ps__choice-grid--2">
            @foreach ($goals as $value => $label)
                <label class="yk-ps__choice">
                    <input type="radio" name="goal" value="{{ $value }}" @checked(($old['goal'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                    <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-ps__step" data-ps-step="2">
    <div class="yk-ps__field">
        <label class="yk-ps__label" for="context">Short context — what happened?</label>
        <textarea class="yk-ps__input yk-ps__textarea" id="context" name="context" rows="6" minlength="10" maxlength="1000" placeholder="e.g. He refuses to stop playing games at dinner time and gets upset when I ask…" required>{{ $old['context'] ?? '' }}</textarea>
        <p class="yk-ps__hint"><span data-ps-count-for="context">0</span>/1000 characters · minimum 10. Do not share identifying details you are uncomfortable saving.</p>
    </div>
</div>
