@extends('layouts.layout')

@section('title', 'Water Intake Tracker unavailable | YuktiOne')
@section('meta_description', 'This tracker is unavailable right now.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water yk-tools-ds" data-yk-tools-ds data-yk-water>
    <div class="yk-water__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-water__card" aria-labelledby="water-closed-heading">
            <p class="yk-water__badge">Nutrition tool</p>
            <h1 id="water-closed-heading">Water Intake Tracker</h1>
            <p class="yk-water__lead">This tracker is unavailable right now.</p>
            <div class="yk-water__actions">
                <a class="yk-water__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
