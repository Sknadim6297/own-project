@extends('layouts.layout')

@section('title', 'Experts | YuktiOne')
@section('meta_description', 'Browse accepted professionals on YuktiOne and book a consultation that fits your goals.')
@section('body_class', ($activeTab ?? 'experts') === 'tools' ? 'yk-tools-page' : 'yk-experts-page')

@push('styles')
    <link href="{{ asset('frontend/assets/css/yuktione-pro-directory.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-pro-directory.css')) }}" rel="stylesheet">
    @if (($activeTab ?? 'experts') === 'tools')
        <link href="{{ asset('frontend/assets/css/yuktione-tools.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-tools.css')) }}" rel="stylesheet">
        <link href="{{ asset('frontend/assets/css/yuktione-tools-page.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-tools-page.css')) }}" rel="stylesheet">
        <link href="{{ asset('frontend/assets/css/yuktione-tools-ds.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-tools-ds.css')) }}" rel="stylesheet">
    @endif
@endpush

@php
    $base = request()->except('page', 'partial');
    $link = function (array $set = [], array $forget = []) use ($base) {
        $params = $base;
        foreach ($forget as $key) {
            unset($params[$key]);
        }
        foreach ($set as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return route('professionals', $params);
    };

    $isTools = ($activeTab ?? 'experts') === 'tools';
    $activeCategory = (! $isTools && $filters['category'] !== '') ? ($categories[$filters['category']] ?? null) : null;
    $categoryLabel = $activeCategory['label'] ?? null;
    $heroTitle = $categoryLabel ?: 'Explore Experts';
    $heroLead = $isTools
        ? 'Practical tools for mental, emotional and personal growth — open what you need, when you need it.'
        : ($categoryLabel
            ? 'Verified '.$categoryLabel.' professionals — compare profiles and book a session that fits you.'
            : 'Find accepted professionals across career, wellness, lifestyle and more — then open a profile to book a personalised session.');
    $article = $categoryLabel && preg_match('/^[aeiou]/i', $categoryLabel) ? 'an' : 'a';
    $programmesEnabled = (bool) config('yuktione.programmes_enabled');
@endphp

@section('content')
<main class="yk-experts yk-pro{{ $isTools ? ' yk-pro--tools' : '' }}" id="experts"{!! $isTools ? '' : ' data-experts data-experts-endpoint="'.route('professionals').'"' !!}>
    <section class="yk-pro__hero" aria-labelledby="experts-heading">
        <div class="yk-pro__wrap yk-pro__hero-inner">
            <nav class="yk-pro__crumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">›</span>
                <a href="{{ route('categories.index') }}">Explore</a>
                <span aria-hidden="true">›</span>
                @if ($categoryLabel)
                    <a href="{{ route('professionals') }}">Experts</a>
                    <span aria-hidden="true">›</span>
                    <span aria-current="page">{{ $categoryLabel }}</span>
                @else
                    <span aria-current="page">{{ $isTools ? 'Career Tools' : 'Experts' }}</span>
                @endif
            </nav>

            <div class="yk-pro__hero-main">
                <span class="yk-pro__hero-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16 14.2a4.6 4.6 0 0 1 5.5 4.4"/></svg>
                </span>
                <div class="yk-pro__hero-copy">
                    <h1 id="experts-heading">@if ($isTools)Career <span>Tools</span>@elseif ($categoryLabel){{ $categoryLabel }} <span>Experts</span>@else Explore <span>Experts</span>@endif</h1>
                    <p>{{ $heroLead }}</p>
                    <ul class="yk-pro__trust">
                        <li><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3.5 5 6.2v5.4c0 4.2 2.9 7.3 7 8.9 4.1-1.6 7-4.7 7-8.9V6.2l-7-2.7Z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/></svg>Verified experts</li>
                        <li><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="5.5" width="16" height="14.5" rx="2.4"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/></svg>Private 1:1 sessions</li>
                        <li><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13 3.5 5.5 13h5.5l-1 7.5L18.5 11H13l0-7.5Z"/></svg>Easy booking</li>
                    </ul>
                </div>
            </div>
        </div>
        <img
            class="yk-pro__hero-img"
            src="{{ asset('frontend/assets/img/experts-hero.webp') }}"
            alt=""
            width="760"
            height="478"
            decoding="async"
            fetchpriority="high"
        >
        <svg class="yk-pro__silk" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M0 52c180 30 360 34 560 8s400-44 560-18 220 22 320 6v42H0Z" fill="#fff" opacity=".7"/>
            <path d="M0 62c200 24 380 24 580 0s420-34 580-10 190 14 280 4" fill="none" stroke="#e8d3de" stroke-width="1.6"/>
        </svg>
    </section>
    <div class="yk-pro__wrap">
        <nav class="yk-pro__tabs" aria-label="Explore sections">
            <a class="yk-pro__tab{{ $isTools ? '' : ' is-active' }}" href="{{ route('professionals') }}"{!! $isTools ? '' : ' aria-current="page"' !!}>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16 14.2a4.6 4.6 0 0 1 5.5 4.4"/></svg>
                <span>Experts</span>
            </a>
            <a class="yk-pro__tab{{ $isTools ? ' is-active' : '' }}" href="{{ route('professionals', ['view' => 'tools']) }}"{!! $isTools ? ' aria-current="page"' : '' !!}>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1.2"/></svg>
                <span>Career Tools</span>
            </a>
            @if ($programmesEnabled)
                <a class="yk-pro__tab" href="{{ route('programmes.index') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m2.5 9.5 9.5-4.5 9.5 4.5-9.5 4.5Z"/><path d="M6.5 11.8v4.4c0 1.4 2.5 2.8 5.5 2.8s5.5-1.4 5.5-2.8v-4.4M21.5 9.5v5.5"/></svg>
                    <span>Programmes</span>
                </a>
            @else
                <span class="yk-pro__tab is-soon" aria-disabled="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m2.5 9.5 9.5-4.5 9.5 4.5-9.5 4.5Z"/><path d="M6.5 11.8v4.4c0 1.4 2.5 2.8 5.5 2.8s5.5-1.4 5.5-2.8v-4.4M21.5 9.5v5.5"/></svg>
                    <span>Programmes</span>
                    <em>Soon</em>
                </span>
            @endif
        </nav>

        @if ($isTools)
            <div class="yk-tools yk-tools--embed">
                <div class="yk-tools__wrap">
                    @include('frontend.sections.partials.tools-catalog', [
                        'tItems' => $tools['items'],
                        'tFilters' => $tools['filters'],
                        'tCategories' => $tools['categories'],
                        'tCounts' => $tools['counts'],
                        'tRoute' => 'professionals',
                        'tExtra' => ['view' => 'tools'],
                    ])
                </div>
            </div>
        @else
        <header class="yk-pro__head">
            <div class="yk-pro__head-copy">
                <h2 class="yk-pro__title">
                    @if ($categoryLabel)
                        Talk to {{ $article }} <span>{{ $categoryLabel }}</span> Expert
                    @else
                        Talk to an <span>Expert</span>
                    @endif
                </h2>
                <p>Get 1:1 personalised guidance for clarity, planning and growth.</p>
            </div>
            <ul class="yk-pro__stats" aria-label="Directory highlights">
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16 14.2a4.6 4.6 0 0 1 5.5 4.4"/></svg>
                    <span><strong>{{ number_format($stats['experts'] ?? $items->total()) }}</strong> Experts</span>
                </li>
                @php($statRating = $stats['rating'] ?? (config('yuktione.placeholder_ratings', true) ? '4.8' : null))
                @if ($statRating)
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3.8 14.4 9l5.6.7-4.1 3.9 1 5.6L12 16.4 7.1 19.2l1-5.6L4 9.7 9.6 9 12 3.8Z"/></svg>
                        <span><strong>{{ $statRating }}</strong> Avg. Rating</span>
                    </li>
                @endif
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4.5" y="4.5" width="6" height="6" rx="1.4"/><rect x="13.5" y="4.5" width="6" height="6" rx="1.4"/><rect x="4.5" y="13.5" width="6" height="6" rx="1.4"/><rect x="13.5" y="13.5" width="6" height="6" rx="1.4"/></svg>
                    <span><strong>{{ $stats['categories'] ?? count($categories) }}</strong> Categories</span>
                </li>
            </ul>
        </header>
        <section class="yk-pro__bar" aria-label="Search and filters">
            <form class="yk-pro__controls" action="{{ route('professionals') }}" method="get" role="search" data-experts-search>
                @foreach (['service', 'sub_service'] as $keep)
                    @if (! empty($filters[$keep]))
                        <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">
                    @endif
                @endforeach
                @foreach ($filters['price'] as $price)
                    <input type="hidden" name="price[]" value="{{ $price }}">
                @endforeach
                @foreach ($filters['rating'] as $rating)
                    <input type="hidden" name="rating[]" value="{{ $rating }}">
                @endforeach
                @foreach ($filters['availability'] as $availability)
                    <input type="hidden" name="availability[]" value="{{ $availability }}">
                @endforeach

                <div class="yk-pro__field yk-pro__field--search">
                    <label class="visually-hidden" for="experts-search">Search experts by name</label>
                    <span class="yk-pro__field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5"/><path d="m16.2 16.2 4.3 4.3"/></svg>
                    </span>
                    <input
                        id="experts-search"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Search experts by name…"
                        autocomplete="off"
                        maxlength="100"
                        spellcheck="false"
                        data-experts-q
                        enterkeyhint="search"
                    >
                    <button
                        type="button"
                        class="yk-pro__search-clear{{ $filters['q'] === '' ? ' is-hidden' : '' }}"
                        data-experts-clear
                        aria-label="Clear search"
                        @if ($filters['q'] === '') hidden @endif
                    >
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 5.5 14.5 14.5M14.5 5.5 5.5 14.5"/></svg>
                    </button>
                </div>

                @if (! empty($experienceBuckets))
                    <div class="yk-pro__field yk-pro__field--select">
                        <label for="experts-experience">Experience</label>
                        <select id="experts-experience" name="experience" data-experts-auto-submit>
                            <option value="">Any experience</option>
                            @foreach ($experienceBuckets as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['experience'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="yk-pro__field yk-pro__field--select">
                    <label for="experts-category">Category</label>
                    <select id="experts-category" name="category" data-experts-auto-submit data-experts-category-select>
                        <option value="">All categories</option>
                        @foreach ($categories as $slug => $category)
                            <option value="{{ $slug }}" @selected(($filters['category'] ?? '') === $slug)>{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="yk-pro__field yk-pro__field--select">
                    <label for="experts-sort">Sort by</label>
                    <select id="experts-sort" name="sort" data-experts-auto-submit>
                        @foreach ([
                            'relevant' => 'Most relevant',
                            'newest' => 'Newest',
                            'rating' => 'Highest rated',
                            'price_asc' => 'Price: low to high',
                            'price_desc' => 'Price: high to low',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'relevant') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="yk-pro__search-submit">Search</button>
            </form>

            <div class="yk-pro__cats" data-pro-cats>
                <p class="visually-hidden" id="experts-category-label">Category</p>
                <button type="button" class="yk-pro__cats-nav yk-pro__cats-nav--prev" data-pro-cats-prev aria-label="Scroll categories left" hidden>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                </button>
                <nav class="yk-pro__cats-scroll" aria-labelledby="experts-category-label" role="tablist" data-experts-filters data-pro-cats-scroll>
                    <a
                        href="{{ $link([], ['category', 'service', 'sub_service']) }}"
                        class="yk-pro__pill{{ $filters['category'] === '' ? ' is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $filters['category'] === '' ? 'true' : 'false' }}"
                    ><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4.5" y="4.5" width="6" height="6" rx="1.4"/><rect x="13.5" y="4.5" width="6" height="6" rx="1.4"/><rect x="4.5" y="13.5" width="6" height="6" rx="1.4"/><rect x="13.5" y="13.5" width="6" height="6" rx="1.4"/></svg>All</a>
                    @foreach ($categories as $slug => $category)
                        <a
                            href="{{ $link(['category' => $slug], ['service', 'sub_service']) }}"
                            class="yk-pro__pill{{ $filters['category'] === $slug ? ' is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $filters['category'] === $slug ? 'true' : 'false' }}"
                        >@include('frontend.sections.partials.tool-icon-svg', ['icon' => $category['icon']]){{ $category['label'] }}</a>
                    @endforeach
                </nav>
                <button type="button" class="yk-pro__cats-nav yk-pro__cats-nav--next" data-pro-cats-next aria-label="Scroll categories right" hidden>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5"/></svg>
                </button>
            </div>
        </section>
        <section class="yk-pro__results" aria-label="Expert results" data-experts-results aria-busy="false">
            @include('frontend.sections.partials.experts-results')
        </section>
        @endif
    </div>
</main>
@endsection
