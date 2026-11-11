@extends('layouts.layout')

@section('title', 'Weight Journey unavailable | YuktiOne')
@section('meta_description', 'This tracker is unavailable right now.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj yk-tools-ds" data-yk-tools-ds data-yk-wj>
    <div class="yk-wj__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-wj__card" aria-labelledby="wj-closed-heading">
            <p class="yk-wj__badge">Nutrition tool</p>
            <h1 id="wj-closed-heading">Weight Journey</h1>
            <p class="yk-wj__lead">This tracker is unavailable right now.</p>
            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
