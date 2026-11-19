@php
    $states = [
        'stressed' => 'Stressed',
        'restless' => 'Restless',
        'angry' => 'Angry',
        'distracted' => 'Distracted',
        'overwhelmed' => 'Overwhelmed',
        'general_calm' => 'General calm',
    ];
    $practices = [
        'Breathing' => 'Slow your breath, settle your body',
        'Grounding' => 'Come back to the present moment',
        'Body scan' => 'Release tension, head to toe',
        'Quiet focus' => 'Rest your attention on one thing',
    ];
@endphp
<div class="yk-ps__step is-active" data-ps-step="0">
    <fieldset class="yk-ps__question yk-ps__question--plain" data-ps-required-group>
        <legend><span>How do you feel right now?</span></legend>
        <div class="yk-ps__choice-grid yk-ps__choice-grid--3">
            @foreach ($states as $value => $label)
                <label class="yk-ps__choice">
                    <input type="radio" name="state" value="{{ $value }}" @checked(($old['state'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                    <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-ps__step" data-ps-step="1">
    <fieldset class="yk-ps__question yk-ps__question--plain" data-ps-required-group>
        <legend><span>Duration</span></legend>
        <div class="yk-ps__choice-grid yk-ps__choice-grid--3">
            @foreach ([3 => 'A quick reset', 5 => 'A steady pause', 10 => 'A deep settle'] as $mins => $hint)
                <label class="yk-ps__choice yk-ps__choice--stack">
                    <input type="radio" name="duration" value="{{ $mins }}" @checked((string)($old['duration'] ?? '') === (string)$mins) required>
                    <span><strong>{{ $mins }}</strong> minutes</span>
                    <small>{{ $hint }}</small>
                    <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-ps__step" data-ps-step="2">
    <fieldset class="yk-ps__question yk-ps__question--plain" data-ps-required-group>
        <legend><span>Practice preference</span></legend>
        <div class="yk-ps__choice-grid yk-ps__choice-grid--2">
            @foreach ($practices as $practice => $hint)
                <label class="yk-ps__choice yk-ps__choice--stack">
                    <input type="radio" name="practice" value="{{ $practice }}" @checked(($old['practice'] ?? '') === $practice) required>
                    <span>{{ $practice }}</span>
                    <small>{{ $hint }}</small>
                    <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                </label>
            @endforeach
        </div>
    </fieldset>
    <input type="hidden" name="completed_seconds" id="completed_seconds" value="{{ $old['completed_seconds'] ?? '' }}">
    <p class="yk-ps__hint yk-ps__hint--block">After you generate the practice, use the timer on the results page. You can pause and resume anytime.</p>
</div>
