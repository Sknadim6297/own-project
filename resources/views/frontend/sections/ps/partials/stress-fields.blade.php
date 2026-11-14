@php $questions = $stressQuestions ?? []; @endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Eight short questions. Answer honestly—there is no right or wrong. Results are educational, not a clinical diagnosis.</p>
    <p>Scale: 1 = Never · 5 = Almost always</p>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    @foreach (array_slice($questions, 0, 4) as $q)
        <fieldset class="yk-pfm__fieldset">
            <legend>{{ $q['id'] }}. {{ $q['question'] }}</legend>
            <div class="yk-pfm__scale">
                @for ($i = 1; $i <= 5; $i++)
                    <label class="yk-pfm__scale-option">
                        <input type="radio" name="q{{ $q['id'] }}" value="{{ $i }}" @checked((string)($old['q'.$q['id']] ?? '') === (string)$i) required>
                        <span>{{ $i }}</span>
                    </label>
                @endfor
            </div>
        </fieldset>
    @endforeach
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    @foreach (array_slice($questions, 4, 4) as $q)
        <fieldset class="yk-pfm__fieldset">
            <legend>{{ $q['id'] }}. {{ $q['question'] }}</legend>
            <div class="yk-pfm__scale">
                @for ($i = 1; $i <= 5; $i++)
                    <label class="yk-pfm__scale-option">
                        <input type="radio" name="q{{ $q['id'] }}" value="{{ $i }}" @checked((string)($old['q'.$q['id']] ?? '') === (string)$i) required>
                        <span>{{ $i }}</span>
                    </label>
                @endfor
            </div>
        </fieldset>
    @endforeach
</div>
