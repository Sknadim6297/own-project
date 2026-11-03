@extends('layouts.layout')

@section('title', 'Water Intake Tracker | YuktiOne')
@section('meta_description', 'Track daily water intake against a personal goal. Login required to save history.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water">
    <div class="yk-water__wrap">
        <section class="yk-water__card">
            <p class="yk-water__badge">Nutrition tool</p>
            <h1>Water Intake Tracker</h1>
            <p class="yk-water__lead">Log drinks, follow a personal daily goal, and review your week. Goals are tracking preferences—not medical prescriptions.</p>
            <p class="yk-water__note">Permanent history requires a YuktiOne account. Guests can preview this introduction only; nothing is saved until you sign in.</p>
            <div class="yk-water__actions">
                <a class="yk-water__btn" href="{{ route('login') }}">Log in to start tracking</a>
                <a class="yk-water__btn-secondary" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
