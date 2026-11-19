@extends('layouts.layout')

@section('title', $tool['name'].' History | YuktiOne')
@section('meta_description', 'Private history of saved '.$tool['name'].' results.')
@section('body_class', 'yk-ps-body')

@section('content')
<main class="yk-ps yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-ps__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <nav class="yk-ps__crumb" aria-label="Breadcrumb"><a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> <span aria-hidden="true">/</span> <span aria-current="page">{{ $tool['name'] }} history</span></nav>

        <header class="yk-ps__head yk-ps__head--history" aria-labelledby="ps-history-heading">
            <span class="yk-ps__tool-icon yk-ps__tool-icon--{{ $tool['category'] }} yk-ps__tool-icon--lg">@include('frontend.sections.ps.partials.icon', ['name' => 'history', 'size' => 28])</span>
            <div class="yk-ps__head-copy">
                <p class="yk-ps__badge">@include('frontend.sections.ps.partials.icon', ['name' => 'lock', 'size' => 14]) Private history</p>
                <h1 id="ps-history-heading">{{ $tool['name'] }} history</h1>
                <p class="yk-ps__lead">Only you can see these saved results on your YuktiOne account.</p>
            </div>
            <a class="yk-ps__btn yk-ps__head-cta" href="{{ route($tool['route']) }}">@include('frontend.sections.ps.partials.icon', ['name' => 'plus', 'size' => 16]) New result</a>
        </header>

        <section class="yk-ps__panel" aria-label="Saved results">
            @if (session('status'))
                <p class="yk-ps__status" role="status">@include('frontend.sections.ps.partials.icon', ['name' => 'sparkle', 'size' => 18]) <span>{{ session('status') }}</span></p>
            @endif
            @if ($errors->any())
                <p class="yk-ps__error" role="alert">@include('frontend.sections.ps.partials.icon', ['name' => 'alert', 'size' => 18]) <span>{{ $errors->first() }}</span></p>
            @endif

            @if ($items->isEmpty())
                <div class="yk-ps__empty" role="status">
                    <span class="yk-ps__empty-icon">@include('frontend.sections.ps.partials.icon', ['name' => $tool['glyph'] ?? 'sparkle', 'size' => 30])</span>
                    <p class="yk-ps__empty-title">No saved results yet</p>
                    <p>Complete {{ $tool['name'] }}, then save a result or draft to see it here.</p>
                    <a class="yk-ps__btn" href="{{ route($tool['route']) }}">Start now @include('frontend.sections.ps.partials.icon', ['name' => 'arrow', 'size' => 16])</a>
                </div>
            @else
                <ul class="yk-ps__history">
                    @foreach ($items as $item)
                        <li class="yk-ps__history-card">
                            <div class="yk-ps__history-main">
                                <p class="yk-ps__history-title">{{ $item->title }} @if ($item->is_draft)<span class="yk-ps__chip yk-ps__chip--draft">Draft</span>@endif</p>
                                <p class="yk-ps__history-summary">{{ \Illuminate\Support\Str::limit($item->summary, 120) }}</p>
                                <p class="yk-ps__meta">@include('frontend.sections.ps.partials.icon', ['name' => 'clock', 'size' => 14]) Saved {{ optional($item->updated_at)->timezone($timezone)->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="yk-ps__actions yk-ps__actions--row">
                                <a class="yk-ps__btn yk-ps__btn--sm" href="{{ route($tool['route'].'.history.show', $item) }}">View</a>
                                <a class="yk-ps__btn yk-ps__btn--sm yk-ps__btn--ghost" href="{{ route($tool['route'].'.continue', $item) }}">Edit &amp; recalculate</a>
                                <form method="post" action="{{ route($tool['route'].'.history.destroy', $item) }}" onsubmit="return confirm('Delete this saved result?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="yk-ps__btn yk-ps__btn--sm yk-ps__btn--ghost yk-ps__btn--danger" aria-label="Delete saved result">@include('frontend.sections.ps.partials.icon', ['name' => 'trash', 'size' => 15]) Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($items->hasPages())
                    <nav class="yk-ps__pager" aria-label="Pagination">
                        @if ($items->onFirstPage())
                            <span class="yk-ps__btn yk-ps__btn--sm yk-ps__btn--disabled" aria-disabled="true">@include('frontend.sections.ps.partials.icon', ['name' => 'arrow-left', 'size' => 15]) Newer</span>
                        @else
                            <a class="yk-ps__btn yk-ps__btn--sm yk-ps__btn--ghost" href="{{ $items->previousPageUrl() }}" rel="prev">@include('frontend.sections.ps.partials.icon', ['name' => 'arrow-left', 'size' => 15]) Newer</a>
                        @endif
                        <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                        @if ($items->hasMorePages())
                            <a class="yk-ps__btn yk-ps__btn--sm yk-ps__btn--ghost" href="{{ $items->nextPageUrl() }}" rel="next">Older @include('frontend.sections.ps.partials.icon', ['name' => 'arrow', 'size' => 15])</a>
                        @else
                            <span class="yk-ps__btn yk-ps__btn--sm yk-ps__btn--disabled" aria-disabled="true">Older @include('frontend.sections.ps.partials.icon', ['name' => 'arrow', 'size' => 15])</span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
