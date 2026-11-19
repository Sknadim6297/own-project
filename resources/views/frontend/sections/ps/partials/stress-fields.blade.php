@php
    $questions = $stressQuestions ?? [];
    $groups = [1 => array_slice($questions, 0, 4), 2 => array_slice($questions, 4, 4)];
@endphp
<div class="yk-ps__step is-active" data-ps-step="0">
    <div class="yk-ps__intro">
        <span class="yk-ps__intro-icon">@include('frontend.sections.ps.partials.icon', ['name' => 'heart-pulse', 'size' => 24])</span>
        <div>
            <h2>Before you begin</h2>
            <p>Eight short questions. Answer honestly—there is no right or wrong. Results are educational, not a clinical diagnosis.</p>
        </div>
    </div>
    <div class="yk-ps__legend-scale">
        <span><b>1</b> Never</span>
        <span><b>2</b> Rarely</span>
        <span><b>3</b> Sometimes</span>
        <span><b>4</b> Often</span>
        <span><b>5</b> Almost always</span>
    </div>
</div>
@foreach ($groups as $stepIndex => $group)
    <div class="yk-ps__step" data-ps-step="{{ $stepIndex }}">
        @foreach ($group as $q)
            <fieldset class="yk-ps__question" data-ps-required-group>
                <legend><span class="yk-ps__qnum">{{ $q['id'] }}</span><span>{{ $q['question'] }}</span></legend>
                <div class="yk-ps__scale" role="radiogroup">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="yk-ps__scale-option">
                            <input type="radio" name="q{{ $q['id'] }}" value="{{ $i }}" @checked((string)($old['q'.$q['id']] ?? '') === (string)$i) required>
                            <span>{{ $i }}</span>
                        </label>
                    @endfor
                </div>
                <div class="yk-ps__scale-ends" aria-hidden="true"><span>Never</span><span>Almost always</span></div>
            </fieldset>
        @endforeach
    </div>
@endforeach
