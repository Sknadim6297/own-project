@extends('layouts.layout')

@section('title', 'Calorie & Nutrition Tracker | YuktiOne')
@section('meta_description', 'Log meals from a verified food catalogue. Login required to save history.')
@section('body_class', 'yk-nut-body')

@section('content')
@php $catalogueReady = ($catalogueCount ?? 0) > 0; @endphp
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card yk-nut__card--welcome">
            <p class="yk-nut__badge">{{ $catalogueReady ? 'Nutrition tool' : 'Food Catalogue Pending' }}</p>
            <h1>Calorie &amp; Nutrition Tracker</h1>
            <p class="yk-nut__lead">Search published foods, set portions from source-backed servings, and log breakfast, lunch, dinner, or snacks.</p>

            <ul class="yk-nut__welcome-points">
                <li>Daily calories and macros from real entries</li>
                <li>Optional personal goals (never prescriptions)</li>
                <li>Meal history with nutrition snapshots</li>
            </ul>

            @unless ($catalogueReady)
                <div class="yk-nut__empty yk-nut__empty--gate" role="status">
                    <p class="yk-nut__empty-title">Food Catalogue Pending</p>
                    <p class="yk-nut__empty-copy">Food logging becomes available after the verified catalogue is published. YuktiOne does not invent nutrition values.</p>
                </div>
            @endunless

            <p class="yk-nut__note">Guests may browse public foods. Meal history requires a YuktiOne account.</p>
            <div class="yk-nut__actions">
                @if ($catalogueReady)
                    <a class="yk-nut__btn" href="{{ route('login') }}">Log in to track meals</a>
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.search') }}">Search foods</a>
                @else
                    <a class="yk-nut__btn" href="{{ route('login') }}">Log in</a>
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.search') }}">Check catalogue status</a>
                @endif
                @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-nut__btn-secondary'])
            </div>
        </section>
    </div>
</main>
@endsection
