@extends('layouts.layout')

@section('title', 'Calorie & Nutrition Tracker | YuktiOne')
@section('meta_description', 'Log meals from a verified food catalogue. Login required to save history.')
@section('body_class', 'yk-nut-body')

@section('content')
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">Nutrition tool</p>
            <h1>Calorie &amp; Nutrition Tracker</h1>
            <p class="yk-nut__lead">Search the food catalogue, set portions from source-backed servings, and log breakfast, lunch, dinner, or snacks. Goals are optional tracking preferences—not diet prescriptions.</p>
            <p class="yk-nut__note">Guests may browse public foods. Permanent meal history requires a YuktiOne account. Empty diaries mean no entries were saved—not that no food was eaten.</p>
            <div class="yk-nut__actions">
                <a class="yk-nut__btn" href="{{ route('login') }}">Log in to track meals</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.search') }}">Search foods</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
