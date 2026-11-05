@extends('layouts.layout')

@section('title', 'Weight Journey | YuktiOne')
@section('meta_description', 'Track weight privately with optional goals. Login required to save history.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card yk-wj__card--welcome">
            <p class="yk-wj__badge">Nutrition tool</p>
            <h1>Weight Journey</h1>
            <p class="yk-wj__lead">Log measurements, optionally set a personal goal, and review progress over time—without judgmental labels.</p>

            <ul class="yk-wj__welcome-points">
                <li>Starting, current and target at a glance</li>
                <li>Real measurement chart (no fabricated points)</li>
                <li>Lose, gain, maintain or track-only goals</li>
                <li>Private history with edit and delete</li>
            </ul>

            <p class="yk-wj__note">Guests see this introduction only. Nothing is saved until you sign in.</p>
            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('login') }}">Log in to start</a>
                @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-wj__btn-secondary'])
            </div>
        </section>
    </div>
</main>
@endsection
