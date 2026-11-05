@extends('layouts.layout')

@section('title', 'Water Intake Tracker | YuktiOne')
@section('meta_description', 'Track daily water intake against a personal goal. Login required to save history.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water">
    <div class="yk-water__wrap">
        <section class="yk-water__card yk-water__card--welcome">
            <p class="yk-water__badge">Nutrition tool</p>
            <h1>Water Intake Tracker</h1>
            <p class="yk-water__lead">Log drinks against a personal daily goal and review your week. Goals are tracking preferences—not medical prescriptions.</p>

            <div class="yk-water__welcome-preview" aria-hidden="true">
                <div class="yk-water__progress is-demo">
                    <svg viewBox="0 0 140 140">
                        <circle class="yk-water__ring-track" cx="70" cy="70" r="54" fill="none" stroke-width="12"></circle>
                        <circle class="yk-water__ring-fill" cx="70" cy="70" r="54" fill="none" stroke-width="12" stroke-linecap="round"
                            stroke-dasharray="339.292" stroke-dashoffset="135.717" transform="rotate(-90 70 70)"></circle>
                    </svg>
                    <div class="yk-water__progress-center">
                        <strong>—</strong>
                        <span>Sign in to track</span>
                    </div>
                </div>
            </div>

            <ul class="yk-water__welcome-points">
                <li>Quick-add glasses and bottles</li>
                <li>Daily progress against your goal</li>
                <li>Private weekly history</li>
            </ul>

            <p class="yk-water__note">Guests can preview this introduction only. Nothing is saved until you sign in.</p>
            <div class="yk-water__actions">
                <a class="yk-water__btn" href="{{ route('login') }}">Log in to start tracking</a>
                @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-water__btn-secondary'])
            </div>
        </section>
    </div>
</main>
@endsection
