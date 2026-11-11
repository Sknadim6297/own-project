@extends('layouts.layout')

@section('title', 'Daily water goal | YuktiOne')
@section('meta_description', 'Set a personal daily water tracking goal.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water yk-tools-ds" data-yk-tools-ds data-yk-water>
    <div class="yk-water__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-water__card" aria-labelledby="water-goal-heading">
            <p class="yk-water__badge">Preference</p>
            <h1 id="water-goal-heading">Daily water goal</h1>
            <p class="yk-water__lead">Your personal tracking target—not a medically prescribed amount.</p>

            @if ($errors->any())
                <p class="yk-water__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <form method="post" action="{{ route('tools.water-intake.goal.update') }}" data-water-submit-once>
                @csrf
                <div class="yk-water__field">
                    <label for="goal-ml">Daily goal (ml)</label>
                    <input id="goal-ml" type="number" name="daily_goal_ml" min="{{ $min }}" max="{{ $max }}" step="1" value="{{ old('daily_goal_ml', $settings->daily_goal_ml) }}" required>
                    <p class="yk-water__note">Allowed range: {{ number_format($min) }}–{{ number_format($max) }} ml.</p>
                </div>
                <div class="yk-water__field">
                    <label for="display-unit">Display unit</label>
                    <select id="display-unit" name="display_unit">
                        <option value="ml" @selected(old('display_unit', $settings->display_unit) === 'ml')>Millilitres (ml)</option>
                        <option value="L" @selected(old('display_unit', $settings->display_unit) === 'L')>Litres (L)</option>
                    </select>
                </div>
                <div class="yk-water__actions">
                    <button class="yk-water__btn" type="submit">Save goal</button>
                    <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake') }}">Cancel</a>
                </div>
            </form>
        </section>
    </div>
</main>
@endsection
