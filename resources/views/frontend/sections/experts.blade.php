@extends('layouts.layout')

@section('title', 'Experts | YuktiOne')
@section('meta_description', 'Browse accepted professionals on YuktiOne and book a consultation that fits your goals.')
@section('body_class', 'yk-category-page')

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
    $activeCategory = (! $isTools && ($filters['category'] ?? '') !== '') ? ($categories[$filters['category']] ?? null) : null;
    $categoryLabel = $activeCategory['label'] ?? null;
    $heroTitle = $isTools ? 'Tools' : ($categoryLabel ?: 'Experts');
    $heroLead = $isTools
        ? 'Practical tools for mental, emotional and personal growth — open what you need, when you need it.'
        : ($categoryLabel
            ? 'Verified '.$categoryLabel.' professionals — compare profiles and book a session that fits you.'
            : 'Find verified professionals across career, wellness, lifestyle and more — then open a profile to book.');
    $sectionCounts = $isTools
        ? ['tools' => $tools['items']->total() ?? 0]
        : ['experts' => $items->total() ?? ($stats['experts'] ?? 0)];
@endphp

@section('content')
<main class="yk-cat-detail yk-experts{{ $isTools ? ' yk-experts--tools' : '' }}" id="experts"{!! $isTools ? '' : ' data-experts data-experts-endpoint="'.route('professionals').'"' !!}>
    @include('frontend.sections.partials.catalog-page-hero', [
        'title' => $heroTitle,
        'lead' => $heroLead,
        'backHref' => route('categories.index'),
        'backLabel' => 'All Categories',
    ])

    <section class="yk-cat-detail__tabs-wrap" aria-label="{{ $isTools ? 'Tools' : 'Experts' }} catalogue">
        <div class="yk-cat-detail__wrap">
            @include('frontend.sections.partials.catalog-section-nav', [
                'active' => $isTools ? 'tools' : 'experts',
                'counts' => $sectionCounts,
            ])

            @if ($isTools)
                @include('frontend.sections.partials.tools-catalog', [
                    'tItems' => $tools['items'],
                    'tFilters' => $tools['filters'],
                    'tCategories' => $tools['categories'],
                    'tCounts' => $tools['counts'],
                    'tRoute' => 'professionals',
                    'tExtra' => ['view' => 'tools'],
                ])
            @else
                <nav class="yk-cat-detail__tabs yk-cat-detail__tabs--filters" aria-label="Expert categories" role="tablist" data-experts-filters>
                    <a
                        href="{{ $link([], ['category', 'service', 'sub_service']) }}"
                        class="yk-cat-detail__tab{{ ($filters['category'] ?? '') === '' ? ' is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ ($filters['category'] ?? '') === '' ? 'true' : 'false' }}"
                    >All</a>
                    @foreach ($categories as $slug => $category)
                        <a
                            href="{{ $link(['category' => $slug], ['service', 'sub_service']) }}"
                            class="yk-cat-detail__tab{{ ($filters['category'] ?? '') === $slug ? ' is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ ($filters['category'] ?? '') === $slug ? 'true' : 'false' }}"
                        >{{ $category['label'] }}</a>
                    @endforeach
                </nav>

                <section class="yk-catalog-panel" aria-label="Search and filters">
                    <form class="yk-catalog-toolbar yk-catalog-toolbar--form" action="{{ route('professionals') }}" method="get" role="search" data-experts-search>
                        @foreach (['service', 'sub_service'] as $keep)
                            @if (! empty($filters[$keep]))
                                <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">
                            @endif
                        @endforeach
                        @foreach ($filters['price'] ?? [] as $price)
                            <input type="hidden" name="price[]" value="{{ $price }}">
                        @endforeach
                        @foreach ($filters['rating'] ?? [] as $rating)
                            <input type="hidden" name="rating[]" value="{{ $rating }}">
                        @endforeach
                        @foreach ($filters['availability'] ?? [] as $availability)
                            <input type="hidden" name="availability[]" value="{{ $availability }}">
                        @endforeach
                        @if (($filters['category'] ?? '') !== '')
                            <input type="hidden" name="category" value="{{ $filters['category'] }}">
                        @endif

                        <div class="yk-catalog-toolbar__actions yk-catalog-toolbar__actions--grow">
                            <div class="yk-catalog-search">
                                <label class="visually-hidden" for="experts-search">Search experts by name</label>
                                <span class="yk-catalog-search__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5"/><path d="m16.2 16.2 4.3 4.3"/></svg>
                                </span>
                                <input
                                    id="experts-search"
                                    type="search"
                                    name="q"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Search experts by name…"
                                    autocomplete="off"
                                    maxlength="100"
                                    spellcheck="false"
                                    data-experts-q
                                    enterkeyhint="search"
                                >
                                <button type="submit">Search</button>
                            </div>

                            @if (! empty($experienceBuckets))
                                <label class="visually-hidden" for="experts-experience">Experience</label>
                                <select class="yk-catalog-select" id="experts-experience" name="experience" data-experts-auto-submit>
                                    <option value="">Any experience</option>
                                    @foreach ($experienceBuckets as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['experience'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <label class="visually-hidden" for="experts-sort">Sort by</label>
                            <select class="yk-catalog-select" id="experts-sort" name="sort" data-experts-auto-submit>
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
                    </form>
                </section>

                <section class="yk-catalog-panel yk-experts__results" aria-label="Expert results" data-experts-results aria-busy="false">
                    @include('frontend.sections.partials.experts-results')
                </section>
            @endif
        </div>
    </section>
</main>
@endsection
