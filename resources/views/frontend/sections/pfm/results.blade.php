@extends('layouts.layout')

@section('title', $tool['name'].' Results | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb"><a href="{{ route('tools.pfm') }}">Money &amp; Finance</a> / {{ $tool['name'] }}</p>
        <section class="yk-pfm__card" aria-labelledby="pfm-results-heading">
            <p class="yk-pfm__badge">Results</p>
            <h1 id="pfm-results-heading">{{ $tool['name'] }}</h1>
            <p class="yk-pfm__lead">{{ $result['summary'] ?? '' }}</p>

            @if ($errors->any())
                <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
            @endif

            @include('frontend.sections.pfm.partials.result-metrics', ['toolKey' => $toolKey, 'result' => $result])
            @include('frontend.sections.pfm.partials.result-charts', ['toolKey' => $toolKey, 'result' => $result, 'inputs' => $inputs ?? []])

            <p class="yk-pfm__note">These figures are educational estimates only and are not financial advice.</p>

            <div class="yk-pfm__actions">
                <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                    @csrf
                    <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Edit &amp; recalculate</button>
                </form>

                @if ($authenticated)
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <button type="submit" class="yk-pfm__btn">Save result</button>
                    </form>
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <input type="hidden" name="as_draft" value="1">
                        <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Save as draft</button>
                    </form>
                    <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">View history</a>
                @else
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <button type="submit" class="yk-pfm__btn">Save result (sign in)</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
</main>
@endsection
