@extends('layouts.layout')

@section('title', 'Parenting & Spirituality Tools | YuktiOne')
@section('meta_description', 'Explore YuktiOne parenting and spirituality tools for reflection, routines, conversations, mindfulness and values.')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-pfm__card" aria-labelledby="ps-hub-heading">
            <p class="yk-pfm__badge">Parenting &amp; Spirituality</p>
            <h1 id="ps-hub-heading">Parenting &amp; Spirituality Tools</h1>
            <p class="yk-pfm__lead">Practical, educational tools for family life and calm reflection. Saved results stay private when you are signed in.</p>

            <h2 class="yk-pfm__section-title">Parenting</h2>
            <div class="yk-pfm__tool-grid">
                @foreach ($parenting as $tool)
                    @php $open = in_array($tool['slug'], $openTools, true); @endphp
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

            <h2 class="yk-pfm__section-title" style="margin-top:1.5rem">Spirituality</h2>
            <div class="yk-pfm__tool-grid">
                @foreach ($spirituality as $tool)
                    @php $open = in_array($tool['slug'], $openTools, true); @endphp
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
