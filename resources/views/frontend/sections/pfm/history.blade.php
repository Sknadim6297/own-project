@extends('layouts.layout')

@section('title', $tool['name'].' History | YuktiOne')
@section('meta_description', 'Private history of saved '.$tool['name'].' calculations.')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb"><a href="{{ route('tools.pfm') }}">Money &amp; Finance</a> / {{ $tool['name'] }} history</p>

        <section class="yk-pfm__card" aria-labelledby="pfm-history-heading">
            <div class="yk-pfm__history-header">
                <div>
                    <p class="yk-pfm__badge">Private history</p>
                    <h1 id="pfm-history-heading">{{ $tool['name'] }} history</h1>
                    <p class="yk-pfm__lead">Only you can see these saved calculations on your YuktiOne account.</p>
                </div>
                <a class="yk-pfm__btn" href="{{ route($tool['route']) }}">New calculation</a>
            </div>

            @if (session('status'))
                <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
            @endif

            @if ($items->isEmpty())
                <div class="yk-pfm__empty" role="status">
                    <p class="yk-pfm__empty-title">No saved calculations yet</p>
                    <p>Run {{ $tool['name'] }}, then save a result or draft to see it here.</p>
                    <a class="yk-pfm__btn" href="{{ route($tool['route']) }}">Start a calculation</a>
                </div>
            @else
                <ul class="yk-pfm__history">
                    @foreach ($items as $item)
                        @include('frontend.sections.pfm.partials.history-card', [
                            'item' => $item,
                            'tool' => $tool,
                            'toolKey' => $toolKey,
                            'timezone' => $timezone,
                        ])
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
