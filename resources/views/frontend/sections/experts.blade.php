@extends('layouts.layout')

@section('title', 'Experts | YuktiOne')
@section('meta_description', 'Browse accepted professionals on YuktiOne and book a consultation that fits your goals.')
@section('body_class', 'yk-toolpage-body yk-experts-page')

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
    $topics = ['Anxiety', 'Career', 'Relationships', 'Money', 'Nutrition', 'Astrology'];
    $filtering = $filters['q'] !== '' || $filters['category'] !== '' || $filters['service'] !== '' || $filters['price'] !== [] || $filters['rating'] !== [] || $filters['availability'] !== [];
@endphp

@section('content')
<main class="yk-toolpage yk-experts" id="experts" data-experts>
    <section class="yk-toolpage__hero" aria-labelledby="experts-heading">
        <div class="yk-toolpage__wrap yk-toolpage__hero-grid">
            <div>
                <nav class="yk-toolpage__crumb" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span aria-hidden="true">›</span>
                    <span aria-current="page">Experts</span>
                </nav>
                <h1 id="experts-heading">Find the right<br><span>expert for you.</span></h1>
                <p class="yk-toolpage__lead">Browse accepted professionals across career, wellness, lifestyle and more, then book a time that works.</p>
                <form class="yk-toolpage__search" action="{{ route('professionals') }}" method="get" role="search">
                    @foreach (['category', 'service', 'sort', 'view'] as $keep)
                        @if (!empty($filters[$keep]))
                            <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">
                        @endif
                    @endforeach
                    <label class="visually-hidden" for="experts-search">Search experts, services, or topics</label>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
                    <input id="experts-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search experts, services, or topics..." autocomplete="off" maxlength="100">
                    <button type="submit" aria-label="Search">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h12"/><path d="m13 6 6 6-6 6"/></svg>
                    </button>
                </form>
                <div class="yk-toolpage__topics">
                    <span>Popular searches:</span>
                    <ul>
                        @foreach ($topics as $topic)
                            <li><a href="{{ $link(['q' => $topic]) }}">{{ $topic }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="yk-toolpage__art" aria-hidden="true">
                <svg viewBox="0 0 420 320">
                    <ellipse cx="250" cy="170" rx="120" ry="130" fill="#e7f6ef"/>
                    <path d="M40 250c40-80 90-120 150-110-30 50-70 80-150 110Z" fill="#7ea36a"/>
                    <path d="M70 80c50-20 90 10 100 50-40 10-80 0-100-50Z" fill="#9d81db" opacity=".85"/>
                    <path d="M300 40c30 50 20 100-20 130-10-50-10-90 20-130Z" fill="#8fb67c"/>
                    <path d="M330 180c40 10 70 40 70 80-50-10-80-30-70-80Z" fill="#b7a4e4"/>
                    <circle cx="210" cy="150" r="42" fill="#fff" opacity=".55"/>
                    <circle cx="210" cy="136" r="16" fill="#316d61" opacity=".35"/>
                    <path d="M186 176c12-14 36-14 48 0" fill="none" stroke="#316d61" stroke-width="8" stroke-linecap="round" opacity=".35"/>
                </svg>
            </div>
        </div>
    </section>

    <div class="yk-toolpage__wrap">
        <nav class="yk-toolpage__cats" aria-label="Expert categories">
            <a href="{{ $link([], ['category', 'service', 'sub_service']) }}" @class(['is-all', 'is-active' => $filters['category'] === ''])>
                <span>@include('frontend.sections.partials.explore-icon', ['icon' => 'grid'])</span>
                All
            </a>
            @foreach ($categories as $slug => $category)
                <a href="{{ $link(['category' => $slug], ['service', 'sub_service']) }}" @class(['is-active' => $filters['category'] === $slug, 'is-'.$category['tone']])>
                    <span>@include('frontend.sections.partials.explore-icon', ['icon' => $category['icon']])</span>
                    {{ $category['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="yk-toolpage__layout">
            <aside class="yk-toolpage__sidebar">
                <div class="yk-toolpage__sidebar-head">
                    <h2>Filters</h2>
                    <a href="{{ route('professionals') }}">Reset</a>
                </div>
                <fieldset>
                    <legend>Category</legend>
                    @foreach ($categories as $slug => $category)
                        <a href="{{ $link(['category' => $filters['category'] === $slug ? null : $slug], ['service', 'sub_service']) }}" @if ($filters['category'] === $slug) aria-current="true" @endif>
                            <span class="yk-toolpage__tick" aria-hidden="true"></span>
                            <span>{{ $category['label'] }}</span>
                            <small>({{ $counts['categories'][$slug] ?? 0 }})</small>
                        </a>
                    @endforeach
                </fieldset>
                @if ($ratingAvailable && !empty($counts['rating']))
                    <fieldset>
                        <legend>Rating</legend>
                        @foreach (['4.5' => '4.5 & up', '4' => '4.0 & up', '3.5' => '3.5 & up'] as $value => $label)
                            @if (isset($counts['rating'][$value]))
                                <a href="{{ $link(['rating' => in_array($value, $filters['rating'], true) ? array_values(array_diff($filters['rating'], [$value])) : array_values(array_merge($filters['rating'], [$value]))]) }}" @if (in_array($value, $filters['rating'], true)) aria-current="true" @endif>
                                    <span class="yk-toolpage__tick" aria-hidden="true"></span>
                                    <span>{{ $label }}</span>
                                    <small>({{ $counts['rating'][$value] }})</small>
                                </a>
                            @endif
                        @endforeach
                    </fieldset>
                @endif
                @if ($availabilityAvailable && !empty($counts['availability']))
                    <fieldset>
                        <legend>Availability</legend>
                        @foreach (['now' => 'Available now', 'anytime' => 'Anytime'] as $value => $label)
                            @if (isset($counts['availability'][$value]))
                                <a href="{{ $link(['availability' => in_array($value, $filters['availability'], true) ? array_values(array_diff($filters['availability'], [$value])) : array_values(array_merge($filters['availability'], [$value]))]) }}" @if (in_array($value, $filters['availability'], true)) aria-current="true" @endif>
                                    <span class="yk-toolpage__tick" aria-hidden="true"></span>
                                    <span>{{ $label }}</span>
                                    <small>({{ $counts['availability'][$value] }})</small>
                                </a>
                            @endif
                        @endforeach
                    </fieldset>
                @endif
            </aside>

            <section class="yk-toolpage__results yk-experts__results" aria-label="Expert results">
                <div class="yk-toolpage__toolbar">
                    <p>Showing {{ $items->total() }} {{ $items->total() === 1 ? 'expert' : 'experts' }}</p>
                    <div class="yk-experts__toolbar-actions">
                        <button class="yk-toolpage__filter-open" type="button" data-experts-open>Filters</button>
                        <form method="get" action="{{ route('professionals') }}">
                            @foreach (request()->except('sort', 'page') as $key => $value)
                                @if (is_array($value))
                                    @foreach ($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label class="visually-hidden" for="experts-sort">Sort By</label>
                            <select id="experts-sort" name="sort" onchange="this.form.submit()">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>

                @if ($items->isEmpty())
                    <div class="yk-toolpage__empty">
                        <span class="yk-toolpage__empty-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 8h5M18.5 5.5v5"/></svg>
                        </span>
                        @if ($filtering)
                            <h2>No experts match these filters</h2>
                            <p>Try another topic, or <a href="{{ route('professionals') }}">reset Experts</a>.</p>
                        @else
                            <h2>No experts listed yet</h2>
                            <p>When professionals are accepted and active, they will appear here.</p>
                        @endif
                    </div>
                @else
                    <ul class="yk-explore__grid">
                        @foreach ($items as $card)
                            <li>
                                <article class="yk-card yk-card--{{ $card['kind'] }}">
                                    <a class="yk-card__media" href="{{ $card['url'] }}">
                                        @if ($card['image'])
                                            <img src="{{ $card['image'] }}" alt="" width="640" height="360" loading="lazy" decoding="async" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                        @endif
                                        <span class="yk-card__fallback" @if ($card['image']) hidden @endif aria-hidden="true">{{ $card['initials'] }}</span>
                                        <span class="yk-card__kicker">{{ $card['kicker'] }}</span>
                                        @if ($card['verified'])
                                            <span class="yk-card__check" title="Accepted professional">
                                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3.5 8 3 3 6-6"/></svg>
                                                <span class="visually-hidden">Accepted professional</span>
                                            </span>
                                        @endif
                                    </a>
                                    <div class="yk-card__body">
                                        <h3><a href="{{ $card['url'] }}">{{ $card['title'] }}</a></h3>
                                        @if ($card['subtitle'])
                                            <p class="yk-card__role">{{ $card['subtitle'] }}</p>
                                        @endif
                                        <p class="yk-card__meta">
                                            @if ($card['rating'])
                                                <span class="yk-card__rating"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.4 9.8 5.8l4.7.4-3.6 3.1 1.1 4.6L8 11.6 3.9 14l1.1-4.6L1.5 6.2l4.7-.4Z"/></svg> {{ $card['rating'] }} <span>({{ $card['review_count'] }})</span></span>
                                            @else
                                                <span class="yk-card__new">New</span>
                                            @endif
                                            @if ($card['price_label'])
                                                <strong>{{ $card['price_label'] }}@if ($card['price_suffix'])<small>{{ $card['price_suffix'] }}</small>@endif</strong>
                                            @endif
                                        </p>
                                        @if ($card['chips'] || ($card['chip_extra'] ?? 0) > 0)
                                            <ul class="yk-card__chips">
                                                @foreach ($card['chips'] as $chip)
                                                    <li>{{ $chip }}</li>
                                                @endforeach
                                                @if (($card['chip_extra'] ?? 0) > 0)
                                                    <li class="is-more">+{{ $card['chip_extra'] }}</li>
                                                @endif
                                            </ul>
                                        @endif
                                        <a class="yk-card__cta" href="{{ $card['url'] }}">{{ $card['cta'] }} <span aria-hidden="true">→</span></a>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                    @if ($items->hasPages())
                        <nav class="yk-explore__pages" aria-label="Expert pages">
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
    </div>
    <button class="yk-toolpage__backdrop" type="button" data-experts-close aria-label="Close filters"></button>
</main>
<script>
(function () {
    var root = document.querySelector('[data-experts]');
    if (!root) return;
    var open = function (on) { root.classList.toggle('is-filters-open', on); };
    root.querySelector('[data-experts-open]')?.addEventListener('click', function () { open(true); });
    root.querySelectorAll('[data-experts-close]').forEach(function (button) {
        button.addEventListener('click', function () { open(false); });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') open(false);
    });
})();
</script>
@endsection
