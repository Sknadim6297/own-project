@extends('layouts.layout')

@section('title', 'Water Intake Tracker | YuktiOne')
@section('meta_description', 'Track daily water intake against a personal goal. Login required to save history.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water yk-tools-ds yk-tool-shell" data-yk-tools-ds data-yk-water data-yk-tool-shell="1">
    <div class="yk-water__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-water__card yk-water__card--welcome" aria-labelledby="water-welcome-heading" data-yk-tool-compact-header>
            <p class="yk-water__badge"><x-yk-icon name="droplets" class="yk-icon--sm" /> Nutrition tool</p>
            <div class="yk-tool-shell__title-row" style="margin:0.35rem 0 0.5rem">
                <span class="yk-tool-shell__icon" aria-hidden="true"><x-yk-icon name="droplets" class="yk-icon--lg" /></span>
                <div>
                    <h1 id="water-welcome-heading"><x-yuktione.two-tone text="Water Intake Tracker" /></h1>
                    <p class="yk-water__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('water-intake-tracker') }} Tracking preferences only—not medical prescriptions.</p>
                </div>
            </div>

            <p class="yk-water__note">Sign in to log drinks and save private history.</p>
            <div class="yk-water__actions">
                <a class="yk-water__btn" href="{{ route('login') }}">Log in to start tracking</a>
                @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-water__btn-secondary'])
            </div>
        </section>
    </div>
</main>
@endsection
