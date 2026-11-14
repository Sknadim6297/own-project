@extends('layouts.layout')

@section('title', $tool['name'].' Saved Result | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb">
            <a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> /
            <a href="{{ route($tool['route'].'.history') }}">History</a> /
            Saved result
        </p>
        <section class="yk-pfm__card" aria-labelledby="ps-show-heading">
            <p class="yk-pfm__badge">Saved privately</p>
            <h1 id="ps-show-heading">{{ $tool['name'] }}</h1>
            <p class="yk-pfm__lead">{{ $result['summary'] ?? $record->summary }}</p>
            <p class="yk-pfm__muted">Saved on {{ optional($record->updated_at)->timezone($timezone)->format('d M Y, h:i A') }}</p>

            @include('frontend.sections.ps.partials.result-body', [
                'toolKey' => $toolKey,
                'result' => $result,
                'inputs' => $inputs ?? [],
            ])

            <p class="yk-pfm__note">{{ $disclaimer }}</p>

            <div class="yk-pfm__actions">
                <a class="yk-pfm__btn" href="{{ route($tool['route'].'.continue', $record) }}">Edit &amp; recalculate</a>
                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">Back to history</a>
                <form method="post" action="{{ route($tool['route'].'.history.destroy', $record) }}" onsubmit="return confirm('Delete this saved result?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
