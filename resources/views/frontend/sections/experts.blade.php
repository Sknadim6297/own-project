@extends('layouts.layout')

@section('title', 'Experts | YuktiOne')
@section('meta_description', 'Browse accepted professionals on YuktiOne and book a consultation that fits your goals.')
@section('body_class', 'yk-experts-page')

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
@endphp

@section('content')
<main class="yk-experts" id="experts" data-experts data-experts-endpoint="{{ route('professionals') }}">
    <div class="yk-experts__wrap">
        <header class="yk-experts__intro">
            <p class="yk-experts__eyebrow">Trusted professionals</p>
            <h1 id="experts-heading">Explore Experts</h1>
            <p class="yk-experts__lead">Find accepted professionals across career, wellness, lifestyle and more — then open a profile to book.</p>
        </header>

        <section class="yk-experts__panel" aria-label="Search and filters">
            <form class="yk-experts__search" action="{{ route('professionals') }}" method="get" role="search" data-experts-search>
                @foreach (['category', 'service', 'sub_service', 'experience', 'sort'] as $keep)
                    @if (!empty($filters[$keep]))
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
                <label class="visually-hidden" for="experts-search">Search experts by name</label>
                <span class="yk-experts__search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5"/><path d="m16.2 16.2 4.3 4.3"/></svg>
                </span>
                <input
                    id="experts-search"
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Search by name…"
                    autocomplete="off"
                    maxlength="100"
                    spellcheck="false"
                    data-experts-q
                    enterkeyhint="search"
                >
                <button
                    type="button"
                    class="yk-experts__search-clear{{ $filters['q'] === '' ? ' is-hidden' : '' }}"
                    data-experts-clear
                    aria-label="Clear search"
                    @if ($filters['q'] === '') hidden @endif
                >
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 5.5 14.5 14.5M14.5 5.5 5.5 14.5"/></svg>
                </button>
                <button type="submit" class="yk-experts__search-submit">Search</button>
            </form>

            <div class="yk-experts__filters" data-experts-filters>
                <div class="yk-experts__filter-block">
                    <p class="yk-experts__filter-label" id="experts-category-label">Category</p>
                    <nav class="yk-experts__cats" aria-labelledby="experts-category-label" role="tablist">
                        <a
                            href="{{ $link([], ['category', 'service', 'sub_service']) }}"
                            class="yk-experts__chip{{ $filters['category'] === '' ? ' is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $filters['category'] === '' ? 'true' : 'false' }}"
                        >All Experts</a>
                        @foreach ($categories as $slug => $category)
                            <a
                                href="{{ $link(['category' => $slug], ['service', 'sub_service']) }}"
                                class="yk-experts__chip is-{{ $category['tone'] }}{{ $filters['category'] === $slug ? ' is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $filters['category'] === $slug ? 'true' : 'false' }}"
                            >{{ $category['label'] }}</a>
                        @endforeach
                    </nav>
                </div>

                @if (!empty($experienceBuckets))
                    <div class="yk-experts__filter-block">
                        <p class="yk-experts__filter-label" id="experts-experience-label">Experience</p>
                        <div class="yk-experts__exp-row" role="group" aria-labelledby="experts-experience-label">
                            <a
                                href="{{ $link([], ['experience']) }}"
                                class="yk-experts__chip yk-experts__chip--exp{{ ($filters['experience'] ?? '') === '' ? ' is-active' : '' }}"
                                aria-pressed="{{ ($filters['experience'] ?? '') === '' ? 'true' : 'false' }}"
                            >Any</a>
                            @foreach ($experienceBuckets as $value => $label)
                                <a
                                    href="{{ $link(['experience' => $value]) }}"
                                    class="yk-experts__chip yk-experts__chip--exp{{ ($filters['experience'] ?? '') === $value ? ' is-active' : '' }}"
                                    aria-pressed="{{ ($filters['experience'] ?? '') === $value ? 'true' : 'false' }}"
                                >{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="yk-experts__results" aria-label="Expert results" data-experts-results aria-busy="false">
            @include('frontend.sections.partials.experts-results')
        </section>
    </div>
</main>
@endsection
