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
            <p class="yk-wj__lead">Targets are personal preferences for tracking—not ideal-body recommendations. You can track without a target.</p>
            <p class="yk-wj__note">Saving a new goal archives the previous active goal so history is preserved.</p>

            @if (session('status'))
                <p class="yk-wj__ok">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-wj__error">{{ $errors->first() }}</p>
            @endif

            <form method="post" action="{{ route('tools.weight-journey.goal.store') }}">
                @csrf
                <fieldset class="yk-wj__field">
                    <legend>Direction</legend>
                    @foreach ($directions as $key => $label)
                        <label style="display:flex;gap:0.45rem;align-items:center;margin:0.35rem 0;font-weight:600;">
                            <input type="radio" name="direction" value="{{ $key }}" @checked(old('direction', $goal->direction ?? 'track') === $key) required>
                            {{ $label }}
                        </label>
                    @endforeach
                </fieldset>

                <fieldset class="yk-wj__field">
                    <legend>Unit</legend>
                    <div class="yk-wj__units">
                        <label><input type="radio" name="display_unit" value="kg" @checked(old('display_unit', $goal->display_unit ?? 'kg') === 'kg')> kg</label>
                        <label><input type="radio" name="display_unit" value="lb" @checked(old('display_unit', $goal->display_unit ?? 'kg') === 'lb')> lb</label>
                    </div>
                </fieldset>

                <div class="yk-wj__field">
                    <label for="start-w">Starting weight</label>
                    <input id="start-w" type="number" name="starting_weight" step="0.001" min="0" value="{{ old('starting_weight', $goal->starting_weight_kg ?? '') }}" required>
                </div>
                <div class="yk-wj__field">
                    <label for="target-w">Target weight (optional for track-only)</label>
                    <input id="target-w" type="number" name="target_weight" step="0.001" min="0" value="{{ old('target_weight', $goal->target_weight_kg ?? '') }}">
                </div>
                <div class="yk-wj__field">
                    <label for="target-d">Optional target date (planning display only)</label>
                    <input id="target-d" type="date" name="target_date" value="{{ old('target_date', optional($goal->target_date ?? null)?->format('Y-m-d')) }}">
                </div>

                <div class="yk-wj__actions">
                    <button class="yk-wj__btn" type="submit">Save goal</button>
                    <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey') }}">Cancel</a>
                </div>
            </form>

            @if ($goal)
                <form method="post" action="{{ route('tools.weight-journey.goal.archive') }}" style="margin-top:1rem;" onsubmit="return confirm('Archive the active goal?');">
                    @csrf
                    <button class="yk-wj__btn-secondary" type="submit">Archive active goal</button>
                </form>
            @endif
        </section>
    </div>
</main>
@endsection
