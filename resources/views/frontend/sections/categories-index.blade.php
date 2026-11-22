@extends('layouts.layout')

@section('title', 'All Categories | YuktiOne')
@section('meta_description', 'Browse YuktiOne categories for tools, experts, and programmes across wellbeing, career, finance, and more.')
@section('body_class', 'yk-categories-page')

@section('content')
<main class="yk-cat-hub" id="categories">
    <section class="yk-cat-hub__hero">
        <div class="yk-cat-hub__wrap">
            <nav class="yk-cat-hub__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">›</span>
                <span aria-current="page">All Categories</span>
            </nav>
            <h1><x-yuktione.two-tone text="All Categories" /></h1>
            <p class="yk-cat-hub__lead">Find tools, experts{{ config('yuktione.programmes_enabled') ? ', and programmes' : '' }} organised around what matters most to you.</p>
            <form class="yk-cat-hub__search" action="{{ route('categories.index') }}" method="get" role="search">
                <label class="visually-hidden" for="category-search">Search categories</label>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
                <input id="category-search" type="search" name="q" value="{{ $q }}" placeholder="Search categories..." autocomplete="off">
                <button type="submit">Search</button>
            </form>
        </div>
    </section>

    <section class="yk-cat-hub__grid-wrap" aria-labelledby="yk-cat-hub-grid-title">
        <div class="yk-cat-hub__wrap">
            <h2 class="visually-hidden" id="yk-cat-hub-grid-title">Category list</h2>

            @if (empty($categories))
                <div class="yk-cat-hub__empty" role="status">
                    <h3>No categories found</h3>
                    <p>Try another search, or <a href="{{ route('categories.index') }}">browse all categories</a>.</p>
                </div>
            @else
                <ul class="yk-cat-hub__grid yk-cat-hub__grid--photo">
                    @foreach ($categories as $category)
                        <li>
                            <a
                                class="yk-home-cats__card yk-home-cats__card--photo yk-home-cats__card--{{ $category['photo_tone'] ?? 'magenta' }} yk-cat-hub__photo"
                                href="{{ $category['url'] }}"
                            >
                                <span class="yk-home-cats__media">
                                    @if (! empty($category['image']))
                                        <img
                                            src="{{ asset('frontend/assets/img/home-cats/' . $category['image']) }}"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                            width="320"
                                            height="320"
                                        >
                                    @else
                                        <span class="yk-cat-hub__icon" aria-hidden="true">
                                            @include('frontend.sections.partials.explore-icon', ['icon' => $category['icon']])
                                        </span>
                                    @endif
                                </span>
                                <span class="yk-home-cats__label">{{ $category['label'] }}</span>
                                @if (($category['blurb'] ?? $category['tagline'] ?? '') !== '')
                                    <span class="yk-home-cats__blurb">{{ $category['blurb'] ?: $category['tagline'] }}</span>
                                @endif
                                <span class="yk-cat-hub__meta yk-cat-hub__meta--photo">
                                    {{ $category['counts']['tools'] }} tools · {{ $category['counts']['experts'] }} experts
                                    @if (config('yuktione.programmes_enabled'))
                                        · {{ $category['counts']['programmes'] }} programmes
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</main>
@endsection
