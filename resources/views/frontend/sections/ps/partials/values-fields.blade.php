@php $pairs = $valuePairs ?? []; @endphp
<div class="yk-pfm__step" data-pfm-step="0">
    @foreach (array_slice($pairs, 0, 8) as $pair)
        <fieldset class="yk-pfm__fieldset">
            <legend>{{ $pair['q'] }}. {{ $pair['prompt'] }}</legend>
            <div class="yk-pfm__choice-grid">
                <label class="yk-pfm__choice">
                    <input type="radio" name="q{{ $pair['q'] }}" value="{{ $pair['a'] }}" @checked(($old['q'.$pair['q']] ?? '') === $pair['a']) required>
                    <span>{{ $pair['a'] }}</span>
                </label>
                <label class="yk-pfm__choice">
                    <input type="radio" name="q{{ $pair['q'] }}" value="{{ $pair['b'] }}" @checked(($old['q'.$pair['q']] ?? '') === $pair['b']) required>
                    <span>{{ $pair['b'] }}</span>
                </label>
            </div>
        </fieldset>
    @endforeach
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    @foreach (array_slice($pairs, 8, 8) as $pair)
        <fieldset class="yk-pfm__fieldset">
            <legend>{{ $pair['q'] }}. {{ $pair['prompt'] }}</legend>
            <div class="yk-pfm__choice-grid">
                <label class="yk-pfm__choice">
                    <input type="radio" name="q{{ $pair['q'] }}" value="{{ $pair['a'] }}" @checked(($old['q'.$pair['q']] ?? '') === $pair['a']) required>
                    <span>{{ $pair['a'] }}</span>
                </label>
                <label class="yk-pfm__choice">
                    <input type="radio" name="q{{ $pair['q'] }}" value="{{ $pair['b'] }}" @checked(($old['q'.$pair['q']] ?? '') === $pair['b']) required>
                    <span>{{ $pair['b'] }}</span>
                </label>
            </div>
        </fieldset>
    @endforeach
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    <p class="yk-pfm__note">Optional: rank up to five values to break ties. Leave blank if you are unsure.</p>
    @for ($i = 0; $i < 5; $i++)
        <label class="yk-pfm__label" for="ranking_{{ $i }}">Rank {{ $i + 1 }}</label>
        <input class="yk-pfm__input" type="text" id="ranking_{{ $i }}" name="ranking[]" value="{{ $old['ranking'][$i] ?? '' }}" list="ps-values-list" autocomplete="off">
    @endfor
    <datalist id="ps-values-list">
        @foreach (\App\Support\ParentingSpirituality\PsContent::valueMappings() as $value => $map)
            <option value="{{ $value }}"></option>
        @endforeach
    </datalist>
    <label class="yk-pfm__label" for="want_more">What do you want more of right now? (optional)</label>
    <textarea class="yk-pfm__input" id="want_more" name="want_more" rows="3" maxlength="500">{{ $old['want_more'] ?? '' }}</textarea>
</div>
