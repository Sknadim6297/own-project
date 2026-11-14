@extends('layouts.layout')

@section('title', 'Money & Finance Tools | YuktiOne')
@section('meta_description', 'Explore YuktiOne personal finance calculators for planning, goals, debt and savings.')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-pfm__card" aria-labelledby="pfm-hub-heading">
            <p class="yk-pfm__badge">Money &amp; Finance</p>
            <h1 id="pfm-hub-heading">Personal Finance Tools</h1>
            <p class="yk-pfm__lead">Plan cash flow, goals, debt and investments with educational calculators. Save results when you are signed in.</p>
            <div class="yk-pfm__tool-grid">
                @foreach ($tools as $tool)
                    @php
                        $open = in_array($tool['slug'], $openTools, true);
                    @endphp
                    <article class="yk-pfm__tool-card">
                        <div class="yk-pfm__tool-icon" aria-hidden="true"><i class="{{ $tool['icon'] }}"></i></div>
                        <h2>{{ $tool['name'] }}</h2>
                        <p>{{ $tool['description'] }}</p>
                        @if ($open)
                            <a class="yk-pfm__btn" href="{{ route($tool['route']) }}">Open tool</a>
                        @else
                            <span class="yk-pfm__btn yk-pfm__btn--disabled">Unavailable</span>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</main>
@endsection
