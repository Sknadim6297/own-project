@php
    $pairs = $valuePairs ?? [];
    $pairGroups = [0 => array_slice($pairs, 0, 8), 1 => array_slice($pairs, 8, 8)];
@endphp
@foreach ($pairGroups as $stepIndex => $group)
    <div @class(['yk-ps__step', 'is-active' => $stepIndex === 0]) data-ps-step="{{ $stepIndex }}">
        @foreach ($group as $pair)
            <fieldset class="yk-ps__question" data-ps-required-group>
                <legend><span class="yk-ps__qnum">{{ $pair['q'] }}</span><span>{{ $pair['prompt'] }}</span></legend>
                <div class="yk-ps__choice-grid yk-ps__choice-grid--2 yk-ps__choice-grid--versus">
                    <label class="yk-ps__choice">
                        <input type="radio" name="q{{ $pair['q'] }}" value="{{ $pair['a'] }}" @checked(($old['q'.$pair['q']] ?? '') === $pair['a']) required>
                        <span>{{ $pair['a'] }}</span>
                        <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                    </label>
                    <label class="yk-ps__choice">
                        <input type="radio" name="q{{ $pair['q'] }}" value="{{ $pair['b'] }}" @checked(($old['q'.$pair['q']] ?? '') === $pair['b']) required>
                        <span>{{ $pair['b'] }}</span>
                        <b class="yk-ps__tick" aria-hidden="true">@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 12])</b>
                    </label>
                </div>
            </fieldset>
        @endforeach
    </div>
@endforeach
<div class="yk-ps__step" data-ps-step="2">
    <h2 class="yk-ps__step-title">Break ties <small>(optional)</small></h2>
    <p class="yk-ps__hint yk-ps__hint--block">Rank up to five values to break ties. Leave blank if you are unsure.</p>
    <div class="yk-ps__field-grid">
        @for ($i = 0; $i < 5; $i++)
            <div class="yk-ps__field">
                <label class="yk-ps__label" for="ranking_{{ $i }}">Rank {{ $i + 1 }}</label>
                <input class="yk-ps__input" type="text" id="ranking_{{ $i }}" name="ranking[]" value="{{ $old['ranking'][$i] ?? '' }}" list="ps-values-list" autocomplete="off" placeholder="Start typing a value">
            </div>
        @endfor
    </div>
    <datalist id="ps-values-list">
        @foreach (\App\Support\ParentingSpirituality\PsContent::valueMappings() as $value => $map)
            <option value="{{ $value }}"></option>
        @endforeach
    </datalist>
    <div class="yk-ps__field">
        <label class="yk-ps__label" for="want_more">What do you want more of right now? <small>(optional)</small></label>
        <textarea class="yk-ps__input yk-ps__textarea" id="want_more" name="want_more" rows="3" maxlength="500">{{ $old['want_more'] ?? '' }}</textarea>
    </div>
</div>
