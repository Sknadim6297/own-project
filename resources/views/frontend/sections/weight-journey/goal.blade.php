@extends('layouts.layout')

@section('title', 'Weight Goal | YuktiOne')
@section('meta_description', 'Set or update an optional personal weight tracking goal.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Optional goal</p>
            <h1>Weight goal</h1>
            <p class="yk-wj__lead">Personal tracking preference—not an ideal-body recommendation.</p>

            @if (session('status'))
                <p class="yk-wj__ok" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-wj__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <form method="post" action="{{ route('tools.weight-journey.goal.store') }}" data-wj-submit-once>
                @csrf
                <fieldset class="yk-wj__field">
                    <legend>Direction</legend>
                    <div class="yk-wj__direction-grid" role="radiogroup" aria-label="Goal direction">
                        @foreach ($directions as $key => $label)
                            <label class="yk-wj__direction-card">
                                <input type="radio" name="direction" value="{{ $key }}" @checked(old('direction', $goal->direction ?? 'track') === $key) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="yk-wj__field">
                    <legend>Unit</legend>
                    <div class="yk-wj__units" role="radiogroup" aria-label="Display unit">
                        <label><input type="radio" name="display_unit" value="kg" @checked(old('display_unit', $goal->display_unit ?? 'kg') === 'kg')> kg</label>
                        <label><input type="radio" name="display_unit" value="lb" @checked(old('display_unit', $goal->display_unit ?? 'kg') === 'lb')> lb</label>
                    </div>
                </fieldset>

                <div class="yk-wj__log-grid">
                    <div class="yk-wj__field">
                        <label for="start-w">Starting weight</label>
                        <input id="start-w" type="number" name="starting_weight" step="0.001" min="0" inputmode="decimal" value="{{ old('starting_weight', $goal->starting_weight_kg ?? '') }}" required>
                    </div>
                    <div class="yk-wj__field">
                        <label for="target-w">Target weight</label>
                        <input id="target-w" type="number" name="target_weight" step="0.001" min="0" inputmode="decimal" value="{{ old('target_weight', $goal->target_weight_kg ?? '') }}">
                        <p class="yk-wj__note">Required for lose/gain. Optional for track-only.</p>
                    </div>
                    <div class="yk-wj__field">
                        <label for="target-d">Target date (optional)</label>
                        <input id="target-d" type="date" name="target_date" value="{{ old('target_date', optional($goal->target_date ?? null)?->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="yk-wj__actions">
                    <button class="yk-wj__btn" type="submit">Save goal</button>
                    <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey') }}">Cancel</a>
                </div>
            </form>

            @if ($goal)
                <form method="post" action="{{ route('tools.weight-journey.goal.archive') }}" style="margin-top:1rem;" onsubmit="return confirm('Archive the active goal?');" data-wj-submit-once>
                    @csrf
                    <button class="yk-wj__btn-secondary" type="submit">Archive active goal</button>
                </form>
            @endif

            @if ($history->isNotEmpty())
                <h2 class="yk-wj__section-title" style="margin-top:1.35rem;">Goal history</h2>
                <ul class="yk-wj__list">
                    @foreach ($history as $g)
                        <li>
                            <div>
                                <strong>{{ \App\Support\WeightJourney\WeightJourneyCatalog::DIRECTIONS[$g->direction] ?? $g->direction }}</strong>
                                <span class="yk-wj__pill">{{ $g->status }}</span>
                                <div class="yk-wj__meta">
                                    From {{ optional($g->effective_from)->timezone('Asia/Kolkata')?->format('d M Y') }}
                                    @if ($g->effective_to) to {{ $g->effective_to->timezone('Asia/Kolkata')->format('d M Y') }} @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</main>
<script>
(function () {
    document.querySelectorAll('[data-wj-submit-once]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-submitting') === '1') {
                event.preventDefault();
                return;
            }
            form.setAttribute('data-submitting', '1');
            form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.disabled = true;
            });
        });
    });
})();
</script>
@endsection
