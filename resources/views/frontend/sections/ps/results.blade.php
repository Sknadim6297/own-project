@extends('layouts.layout')

@section('title', $tool['name'].' Results | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-ps-body')

@section('content')
<main class="yk-ps yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-ps__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <nav class="yk-ps__crumb" aria-label="Breadcrumb"><a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> <span aria-hidden="true">/</span> <span aria-current="page">{{ $tool['name'] }}</span></nav>

        <header class="yk-ps__head yk-ps__head--result" aria-labelledby="ps-results-heading">
            <span class="yk-ps__tool-icon yk-ps__tool-icon--{{ $tool['category'] }} yk-ps__tool-icon--lg">@include('frontend.sections.ps.partials.icon', ['name' => $tool['glyph'] ?? 'sparkle', 'size' => 30])</span>
            <div class="yk-ps__head-copy">
                <p class="yk-ps__badge">@include('frontend.sections.ps.partials.icon', ['name' => 'sparkle', 'size' => 14]) Your result</p>
                <h1 id="ps-results-heading">{{ $tool['name'] }}</h1>
                <p class="yk-ps__lead">{{ $result['summary'] ?? '' }}</p>
            </div>
        </header>

        <section class="yk-ps__panel yk-ps__panel--result" aria-label="Result details">
            @if ($errors->any())
                <p class="yk-ps__error" role="alert">@include('frontend.sections.ps.partials.icon', ['name' => 'alert', 'size' => 18]) <span>{{ $errors->first() }}</span></p>
            @endif

            @include('frontend.sections.ps.partials.result-body', [
                'toolKey' => $toolKey,
                'result' => $result,
                'inputs' => $inputs ?? [],
            ])

            <div class="yk-ps__actions yk-ps__actions--result">
                @if ($authenticated)
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <button type="submit" class="yk-ps__btn">@include('frontend.sections.ps.partials.icon', ['name' => 'save', 'size' => 16]) Save result</button>
                    </form>
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <input type="hidden" name="as_draft" value="1">
                        <button type="submit" class="yk-ps__btn yk-ps__btn--ghost">Save as draft</button>
                    </form>
                    <a class="yk-ps__btn yk-ps__btn--ghost" href="{{ route($tool['route'].'.history') }}">@include('frontend.sections.ps.partials.icon', ['name' => 'history', 'size' => 16]) View history</a>
                @else
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <button type="submit" class="yk-ps__btn">@include('frontend.sections.ps.partials.icon', ['name' => 'lock', 'size' => 16]) Save result (sign in)</button>
                    </form>
                @endif
                <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                    @csrf
                    <button type="submit" class="yk-ps__btn yk-ps__btn--ghost">@include('frontend.sections.ps.partials.icon', ['name' => 'edit', 'size' => 16]) Edit &amp; recalculate</button>
                </form>
            </div>
        </section>

        <p class="yk-ps__note">@include('frontend.sections.ps.partials.icon', ['name' => 'info', 'size' => 16]) <span>{{ $disclaimer }}</span></p>
    </div>
</main>
@endsection
