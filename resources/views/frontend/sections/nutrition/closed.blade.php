@extends('layouts.layout')

@section('title', 'Calorie & Nutrition Tracker unavailable | YuktiOne')
@section('meta_description', 'This nutrition tool is not available yet.')
@section('body_class', 'yk-nut-body')

@section('content')
<main class="yk-nut yk-tools-ds" data-yk-tools-ds data-yk-nut>
    <div class="yk-nut__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-nut__card" aria-labelledby="nut-closed-heading">
            <p class="yk-nut__badge">Coming soon</p>
            <h1 id="nut-closed-heading">Calorie &amp; Nutrition Tracker</h1>
            <p class="yk-nut__lead">Meal logging opens after a reviewed food catalogue is available. The tool remains unpublished until then.</p>
            <div class="yk-nut__actions">
                <a class="yk-nut__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
