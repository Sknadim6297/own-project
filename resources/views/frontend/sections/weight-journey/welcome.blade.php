@extends('layouts.layout')

@section('title', 'Weight Journey | YuktiOne')
@section('meta_description', 'Track weight privately with optional goals. Login required to save history.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj yk-tools-ds yk-tool-shell" data-yk-tools-ds data-yk-wj data-yk-tool-shell="1">
    <div class="yk-wj__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-wj__card yk-wj__card--welcome" aria-labelledby="wj-welcome-heading" data-yk-tool-compact-header>
            <p class="yk-wj__badge"><x-yk-icon name="scale" class="yk-icon--sm" /> Fitness tool</p>
            <div class="yk-tool-shell__title-row" style="margin:0.35rem 0 0.5rem">
                <span class="yk-tool-shell__icon" aria-hidden="true"><x-yk-icon name="scale" class="yk-icon--lg" /></span>
                <div>
                    <h1 id="wj-welcome-heading"><x-yuktione.two-tone text="Weight Journey" /></h1>
                    <p class="yk-wj__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('weight-journey') }} Personal tracking only—without judgmental labels.</p>
                </div>
            </div>

            <p class="yk-wj__note">Sign in to log measurements and save private history.</p>
            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('login') }}">Log in to start</a>
                @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-wj__btn-secondary'])
            </div>
        </section>
    </div>
</main>
@endsection
