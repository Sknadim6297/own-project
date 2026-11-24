@extends('layouts.layout')

@section('title', 'Calorie & Nutrition Tracker | YuktiOne')
@section('meta_description', 'Log meals from a verified food catalogue. Login required to save history.')
@section('body_class', 'yk-nut-body')

@section('content')
@php $catalogueReady = ($catalogueCount ?? 0) > 0; @endphp
<main class="yk-nut yk-tools-ds yk-tool-shell" data-yk-tools-ds data-yk-nut data-yk-tool-shell="1">
    <div class="yk-nut__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-nut__card yk-nut__card--welcome" aria-labelledby="nut-welcome-heading" data-yk-tool-compact-header>
            <p class="yk-nut__badge">{{ $catalogueReady ? 'Nutrition tool' : 'Food Catalogue Pending' }}</p>
            <div class="yk-tool-shell__title-row" style="margin:0.35rem 0 0.5rem">
                <span class="yk-tool-shell__icon" aria-hidden="true"><x-yk-icon name="leaf" class="yk-icon--lg" /></span>
                <div>
                    <h1 id="nut-welcome-heading"><x-yuktione.two-tone text="Calorie & Nutrition Tracker" /></h1>
                    <p class="yk-nut__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('calorie-nutrition-tracker') }}</p>
                </div>
            </div>

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
