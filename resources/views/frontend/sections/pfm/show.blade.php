@extends('layouts.layout')

@section('title', $tool['name'].' Saved Result | YuktiOne')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb">
            <a href="{{ route('tools.pfm') }}">Money &amp; Finance</a> /
            <a href="{{ route($tool['route'].'.history') }}">History</a> /
            Saved result
        </p>
        <section class="yk-pfm__card" aria-labelledby="pfm-show-heading">
            <div class="yk-pfm__history-top">
                <div>
                    <p class="yk-pfm__badge">{{ $calculation->is_draft ? 'Saved draft' : 'Saved result' }}</p>
                    <h1 id="pfm-show-heading">{{ $calculation->title }}</h1>
                    <p class="yk-pfm__lead">{{ $calculation->summary }}</p>
                    <p class="yk-pfm__meta">Saved {{ optional($calculation->updated_at)->timezone($timezone)->format('d M Y, h:i A') }}</p>
                </div>
            </div>

            @if (session('status'))
                <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
            @endif

            @include('frontend.sections.pfm.partials.result-metrics', ['toolKey' => $toolKey, 'result' => $result])
            @include('frontend.sections.pfm.partials.result-charts', ['toolKey' => $toolKey, 'result' => $result, 'inputs' => $inputs ?? []])

            <div class="yk-pfm__actions">
                <a class="yk-pfm__btn" href="{{ route($tool['route'].'.continue', $calculation) }}">Edit &amp; recalculate</a>
                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">Back to history</a>
                <form method="post" action="{{ route($tool['route'].'.history.destroy', $calculation) }}" onsubmit="return confirm('Delete this saved calculation? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
