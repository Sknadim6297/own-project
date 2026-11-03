@extends('layouts.layout')

@section('title', 'Nutrition Goals | YuktiOne')
@section('meta_description', 'Optional calorie and macronutrient tracking goals.')
@section('body_class', 'yk-nut-body')

@section('content')
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">Optional goals</p>
            <h1>Nutrition goals</h1>
            <p class="yk-nut__lead">Goals are personal tracking preferences. YuktiOne does not prescribe calorie deficits, weight-loss targets, or restrictive diets.</p>
            <p class="yk-nut__note">Younger users should set goals with a parent, guardian, or qualified professional. This tool is not a clinical nutrition service.</p>

            @if ($errors->any())
                <p class="yk-nut__error">{{ $errors->first() }}</p>
            @endif

            <form method="post" action="{{ route('tools.nutrition.goals.update') }}">
                @csrf
                <div class="yk-nut__field">
                    <label>
                        <input type="checkbox" name="track_only" value="1" @checked(old('track_only', $goals->track_only))>
                        Tracking only (no calorie or macro goals)
                    </label>
                </div>
                <div class="yk-nut__field">
                    <label for="calories_kcal">Daily calories (kcal)</label>
                    <input id="calories_kcal" type="number" name="calories_kcal" min="500" max="10000" step="1"
                           value="{{ old('calories_kcal', $goals->calories_kcal) }}" placeholder="Optional">
                </div>
                <div class="yk-nut__field">
                    <label for="protein_g">Protein (g)</label>
                    <input id="protein_g" type="number" name="protein_g" min="0" max="500" step="0.1"
                           value="{{ old('protein_g', $goals->protein_g) }}" placeholder="Optional">
                </div>
                <div class="yk-nut__field">
                    <label for="carbs_g">Carbohydrates (g)</label>
                    <input id="carbs_g" type="number" name="carbs_g" min="0" max="1000" step="0.1"
                           value="{{ old('carbs_g', $goals->carbs_g) }}" placeholder="Optional">
                </div>
                <div class="yk-nut__field">
                    <label for="fat_g">Fat (g)</label>
                    <input id="fat_g" type="number" name="fat_g" min="0" max="500" step="0.1"
                           value="{{ old('fat_g', $goals->fat_g) }}" placeholder="Optional">
                </div>
                <div class="yk-nut__actions">
                    <button class="yk-nut__btn" type="submit">Save goals</button>
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition') }}">Cancel</a>
                </div>
            </form>
        </section>
    </div>
</main>
@endsection
