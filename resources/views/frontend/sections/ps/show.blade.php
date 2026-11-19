@extends('layouts.layout')

@section('title', $tool['name'].' Saved Result | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-ps-body')

@section('content')
<main class="yk-ps yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-ps__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <nav class="yk-ps__crumb" aria-label="Breadcrumb">
            <a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> <span aria-hidden="true">/</span>
            <a href="{{ route($tool['route'].'.history') }}">History</a> <span aria-hidden="true">/</span>
            <span aria-current="page">Saved result</span>
        </nav>

        <header class="yk-ps__head yk-ps__head--result" aria-labelledby="ps-show-heading">
            <span class="yk-ps__tool-icon yk-ps__tool-icon--{{ $tool['category'] }} yk-ps__tool-icon--lg">@include('frontend.sections.ps.partials.icon', ['name' => $tool['glyph'] ?? 'sparkle', 'size' => 30])</span>
            <div class="yk-ps__head-copy">
                <p class="yk-ps__badge">@include('frontend.sections.ps.partials.icon', ['name' => 'lock', 'size' => 14]) Saved privately @if ($record->is_draft) · Draft @endif</p>
                <h1 id="ps-show-heading">{{ $tool['name'] }}</h1>
                <p class="yk-ps__lead">{{ $result['summary'] ?? $record->summary }}</p>
                <p class="yk-ps__meta">@include('frontend.sections.ps.partials.icon', ['name' => 'clock', 'size' => 14]) Saved on {{ optional($record->updated_at)->timezone($timezone)->format('d M Y, h:i A') }}</p>
            </div>
        </header>

        <section class="yk-ps__panel yk-ps__panel--result" aria-label="Saved result details">
            @include('frontend.sections.ps.partials.result-body', [
                'toolKey' => $toolKey,
                'result' => $result,
                'inputs' => $inputs ?? [],
            ])

            <div class="yk-ps__actions yk-ps__actions--result">
                <a class="yk-ps__btn" href="{{ route($tool['route'].'.continue', $record) }}">@include('frontend.sections.ps.partials.icon', ['name' => 'edit', 'size' => 16]) Edit &amp; recalculate</a>
                <a class="yk-ps__btn yk-ps__btn--ghost" href="{{ route($tool['route'].'.history') }}">@include('frontend.sections.ps.partials.icon', ['name' => 'arrow-left', 'size' => 16]) Back to history</a>
                <form method="post" action="{{ route($tool['route'].'.history.destroy', $record) }}" onsubmit="return confirm('Delete this saved result?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="yk-ps__btn yk-ps__btn--ghost yk-ps__btn--danger">@include('frontend.sections.ps.partials.icon', ['name' => 'trash', 'size' => 16]) Delete</button>
                </form>
            </div>
        </section>

        <p class="yk-ps__note">@include('frontend.sections.ps.partials.icon', ['name' => 'info', 'size' => 16]) <span>{{ $disclaimer }}</span></p>
    </div>
</main>
@endsection
