@php
    $states = [
        'stressed' => 'Stressed',
        'restless' => 'Restless',
        'angry' => 'Angry',
        'distracted' => 'Distracted',
        'overwhelmed' => 'Overwhelmed',
        'general_calm' => 'General calm',
    ];
    $practices = ['Breathing', 'Grounding', 'Body scan', 'Quiet focus'];
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <fieldset class="yk-pfm__fieldset">
        <legend>How do you feel right now?</legend>
        <div class="yk-pfm__choice-grid">
            @foreach ($states as $value => $label)
                <label class="yk-pfm__choice">
                    <input type="radio" name="state" value="{{ $value }}" @checked(($old['state'] ?? '') === $value) required>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <fieldset class="yk-pfm__fieldset">
        <legend>Duration</legend>
        <div class="yk-pfm__choice-grid">
            @foreach ([3, 5, 10] as $mins)
                <label class="yk-pfm__choice">
                    <input type="radio" name="duration" value="{{ $mins }}" @checked((string)($old['duration'] ?? '') === (string)$mins) required>
                    <span>{{ $mins }} minutes</span>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    <fieldset class="yk-pfm__fieldset">
        <legend>Practice preference</legend>
        <div class="yk-pfm__choice-grid">
            @foreach ($practices as $practice)
                <label class="yk-pfm__choice">
                    <input type="radio" name="practice" value="{{ $practice }}" @checked(($old['practice'] ?? '') === $practice) required>
                    <span>{{ $practice }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <input type="hidden" name="completed_seconds" id="completed_seconds" value="{{ $old['completed_seconds'] ?? '' }}">
    <p class="yk-pfm__note">After you generate the practice, use the timer on the results page. You can pause and resume anytime.</p>
</div>
