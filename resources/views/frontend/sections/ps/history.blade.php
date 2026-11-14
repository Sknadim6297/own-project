@extends('layouts.layout')

@section('title', $tool['name'].' History | YuktiOne')
@section('meta_description', 'Private history of saved '.$tool['name'].' results.')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb"><a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> / {{ $tool['name'] }} history</p>

        <section class="yk-pfm__card" aria-labelledby="ps-history-heading">
            <div class="yk-pfm__history-header">
                <div>
                    <p class="yk-pfm__badge">Private history</p>
                    <h1 id="ps-history-heading">{{ $tool['name'] }} history</h1>
                    <p class="yk-pfm__lead">Only you can see these saved results on your YuktiOne account.</p>
                </div>
                <a class="yk-pfm__btn" href="{{ route($tool['route']) }}">New result</a>
            </div>

            @if (session('status'))
                <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
            @endif

            @if ($items->isEmpty())
                <div class="yk-pfm__empty" role="status">
                    <p class="yk-pfm__empty-title">No saved results yet</p>
                    <p>Complete {{ $tool['name'] }}, then save a result or draft to see it here.</p>
                    <a class="yk-pfm__btn" href="{{ route($tool['route']) }}">Start now</a>
                </div>
            @else
                <ul class="yk-pfm__history">
                    @foreach ($items as $item)
                        <li class="yk-pfm__history-card">
                            <div>
                                <p class="yk-pfm__history-title">{{ $item->title }}</p>
                                <p class="yk-pfm__history-summary">{{ \Illuminate\Support\Str::limit($item->summary, 120) }}</p>
                                <p class="yk-pfm__muted">
                                    Saved {{ optional($item->updated_at)->timezone($timezone)->format('d M Y, h:i A') }}
                                    @if ($item->is_draft) · Draft @endif
                                </p>
                            </div>
                            <div class="yk-pfm__actions">
                                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history.show', $item) }}">View</a>
                                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.continue', $item) }}">Edit &amp; recalculate</a>
                                <form method="post" action="{{ route($tool['route'].'.history.destroy', $item) }}" onsubmit="return confirm('Delete this saved result?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($items->hasPages())
                    <div class="yk-pfm__pager">{{ $items->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
