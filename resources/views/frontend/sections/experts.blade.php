@extends('layouts.layout')

@section('title', 'Experts | YuktiOne')
@section('meta_description', 'Browse accepted professionals on YuktiOne and book a consultation that fits your goals.')
@section('body_class', 'yk-experts-page')

@php
    $base = request()->except('page');
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
    $sorts = [
        'relevant' => 'Most Relevant',
        'newest' => 'Newest',
        'rating' => 'Highest Rated',
        'price_asc' => 'Price Low to High',
        'price_desc' => 'Price High to Low',
    ];
    $experienceBuckets = $experienceBuckets ?? [];
    $filtering = $filters['q'] !== ''
        || $filters['category'] !== ''
        || $filters['service'] !== ''
        || ($filters['experience'] ?? '') !== ''
        || $filters['price'] !== []
        || $filters['rating'] !== []
        || $filters['availability'] !== [];
    $clearFiltersUrl = $link([], ['q', 'category', 'service', 'sub_service', 'experience', 'price', 'rating', 'availability', 'sort']);
@endphp

@section('content')
<main class="yk-experts" id="experts" data-experts>
    <div class="yk-experts__wrap">
        <header class="yk-experts__intro">
            <h1 id="experts-heading">Explore Experts</h1>
            <p class="yk-experts__lead">Find accepted professionals across career, wellness, lifestyle and more — then open a profile to book.</p>
        </header>

        <nav class="yk-experts__cats" aria-label="Expert categories" role="tablist">
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

        @if (!empty($experienceBuckets))
            <div class="yk-experts__experience" role="group" aria-labelledby="experts-experience-label">
                <p id="experts-experience-label" class="yk-experts__filter-label">Years of experience</p>
                <div class="yk-experts__exp-row">
                    <a
                        href="{{ $link([], ['experience']) }}"
                        class="yk-experts__chip yk-experts__chip--exp{{ ($filters['experience'] ?? '') === '' ? ' is-active' : '' }}"
                        aria-pressed="{{ ($filters['experience'] ?? '') === '' ? 'true' : 'false' }}"
                    >All Experience</a>
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

        <section class="yk-experts__results" aria-label="Expert results">
            <div class="yk-experts__toolbar">
                <p class="yk-experts__count" aria-live="polite">
                    {{ $items->total() }} {{ $items->total() === 1 ? 'expert' : 'experts' }}
                    @if ($filtering)
                        <span class="yk-experts__count-hint">matching filters</span>
                    @endif
                </p>
                <div class="yk-experts__toolbar-actions">
                    @if ($filtering)
                        <a class="yk-experts__clear" href="{{ $clearFiltersUrl }}">Clear filters</a>
                    @endif
                    <form class="yk-experts__search" action="{{ route('professionals') }}" method="get" role="search">
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
                        <label class="visually-hidden" for="experts-search">Search experts</label>
                        <input id="experts-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search experts…" autocomplete="off" maxlength="100">
                        <button type="submit" aria-label="Search experts">Search</button>
                    </form>
                    <form class="yk-experts__sort" method="get" action="{{ route('professionals') }}">
                        @foreach (request()->except('sort', 'page') as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $item)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label class="visually-hidden" for="experts-sort">Sort by</label>
                        <select id="experts-sort" name="sort" onchange="this.form.submit()">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if ($items->isEmpty())
                <div class="yk-experts__empty" role="status">
                    <div class="yk-experts__empty-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 8h5M18.5 5.5v5"/></svg>
                    </div>
                    @if ($filtering)
                        <h2>No matching experts</h2>
                        <p>No accepted experts currently match these filters. Try another category or clear filters to browse everyone.</p>
                        <div class="yk-experts__empty-actions">
                            <a class="yk-experts__btn yk-experts__btn--primary" href="{{ $clearFiltersUrl }}">View all experts</a>
                            @if (($filters['category'] ?? '') !== '')
                                <a class="yk-experts__btn yk-experts__btn--ghost" href="{{ $link([], ['experience', 'q', 'service', 'sub_service', 'price', 'rating', 'availability']) }}">Keep {{ $categories[$filters['category']]['label'] ?? 'category' }}</a>
                            @endif
                        </div>
                    @else
                        <h2>No experts listed yet</h2>
                        <p>When professionals are accepted and active, they will appear here.</p>
                    @endif
                </div>
            @else
                <ul class="yk-experts__grid">
                    @foreach ($items as $card)
                        <li>
                            <article class="yk-expert-card">
                                <a class="yk-expert-card__media" href="{{ $card['url'] }}" tabindex="-1" aria-hidden="true">
                                    @if ($card['image'])
                                        <img src="{{ $card['image'] }}" alt="" width="320" height="320" loading="lazy" decoding="async" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                    @endif
                                    <span class="yk-expert-card__fallback" @if ($card['image']) hidden @endif>{{ $card['initials'] }}</span>
                                </a>
                                <div class="yk-expert-card__body">
                                    <h2 class="yk-expert-card__name">
                                        <a href="{{ $card['url'] }}">{{ $card['title'] }}</a>
                                        @if (!empty($card['verified']))
                                            <span class="yk-expert-card__verified" title="Verified professional">
                                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3.5 8 3 3 6-6"/></svg>
                                                <span class="visually-hidden">Verified</span>
                                            </span>
                                        @endif
                                    </h2>
                                    @if (!empty($card['subtitle']))
                                        <p class="yk-expert-card__role">{{ $card['subtitle'] }}</p>
                                    @endif
                                    @if (!empty($card['experience']))
                                        <p class="yk-expert-card__exp">{{ $card['experience'] }} experience</p>
                                    @endif
                                    @if (!empty($card['qualification']))
                                        <p class="yk-expert-card__qual">{{ \Illuminate\Support\Str::limit($card['qualification'], 72) }}</p>
                                    @endif
                                    @if (!empty($card['price_label']))
                                        <p class="yk-expert-card__price">
                                            {{ $card['price_label'] }}@if (!empty($card['price_suffix']))<small>{{ $card['price_suffix'] }}</small>@endif
                                        </p>
                                    @endif
                                    <div class="yk-expert-card__actions">
                                        <a class="yk-experts__btn yk-experts__btn--ghost" href="{{ $card['url'] }}">{{ $card['cta'] ?? 'View Profile' }}</a>
                                        <a class="yk-experts__btn yk-experts__btn--primary" href="{{ $card['url'] }}">{{ $card['book_cta'] ?? 'Book Consultation' }}</a>
                                    </div>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>

                @if ($items->hasPages())
                    <nav class="yk-experts__pages" aria-label="Expert pages">
                        @if ($items->onFirstPage())
                            <span aria-disabled="true">Previous</span>
                        @else
                            <a href="{{ $items->previousPageUrl() }}">Previous</a>
                        @endif
                        <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                        @if ($items->hasMorePages())
                            <a href="{{ $items->nextPageUrl() }}">Next</a>
                        @else
                            <span aria-disabled="true">Next</span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
