@extends('layouts.layout')

@section('title', 'Explore | YuktiOne')
@section('meta_description', 'Discover experts and articles to support your journey.')
@section('body_class', 'yk-explore-page')

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

        return route('gridlisting', $params);
    };
    $sorts = [
        'relevant' => 'Most Relevant',
        'newest' => 'Newest',
        'rating' => 'Highest Rated',
        'price_asc' => 'Price Low to High',
        'price_desc' => 'Price High to Low',
    ];
    $tabs = [
        'all' => 'All',
        'experts' => 'Experts',
        'tools' => 'Tools',
        'articles' => 'Articles',
    ];
    if (config('yuktione.programmes_enabled')) {
        $tabs = [
            'all' => 'All',
            'experts' => 'Experts',
            'tools' => 'Tools',
            'programmes' => 'Programmes',
            'articles' => 'Articles',
        ];
    }
    $topics = ['Anxiety', 'Relationships', 'Career', 'Money', 'Meditation', 'Nutrition'];
    $contentTypes = ['experts' => 'Experts', 'tools' => 'Tools', 'articles' => 'Articles'];
    if (config('yuktione.programmes_enabled')) {
        $contentTypes = ['experts' => 'Experts', 'tools' => 'Tools', 'programmes' => 'Programmes', 'articles' => 'Articles'];
    }
@endphp

@section('content')
<main class="yk-explore" id="explore" data-explore>
    <section class="yk-explore__hero" aria-labelledby="explore-heading">
        <div class="yk-explore__wrap yk-explore__hero-grid">
            <div>
                <nav class="yk-explore__crumb" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span aria-hidden="true">›</span>
                    <span aria-current="page">Explore</span>
                </nav>
                <h1 id="explore-heading">Explore what<br><span>speaks to you.</span></h1>
                <p class="yk-explore__lead">Discover experts, tools{{ config('yuktione.programmes_enabled') ? ', programmes' : '' }} and articles to support your journey.</p>
                <form class="yk-explore__search" action="{{ route('gridlisting') }}" method="get" role="search">
                    @include('frontend.sections.partials.explore-state', ['omit' => ['q']])
                    <label class="visually-hidden" for="explore-search">Search experts, tools{{ config('yuktione.programmes_enabled') ? ', programmes' : '' }}, topics</label>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
                    <input id="explore-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search experts, tools{{ config('yuktione.programmes_enabled') ? ', programmes' : '' }}, topics..." autocomplete="off">
                    <button type="submit" aria-label="Search">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h12"/><path d="m13 6 6 6-6 6"/></svg>
                    </button>
                </form>
                <div class="yk-explore__topics">
                    <span>Popular searches:</span>
                    <ul>
                        @foreach ($topics as $topic)
                            <li><a href="{{ $link(['q' => $topic], ['category', 'service', 'sub_service']) }}">{{ $topic }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="yk-explore__art" aria-hidden="true">
                <svg viewBox="0 0 420 320">
                    <ellipse cx="250" cy="170" rx="120" ry="130" fill="#e7f6ef"/>
                    <path d="M40 250c40-80 90-120 150-110-30 50-70 80-150 110Z" fill="#7ea36a"/>
                    <path d="M70 80c50-20 90 10 100 50-40 10-80 0-100-50Z" fill="#9d81db" opacity=".85"/>
                    <path d="M300 40c30 50 20 100-20 130-10-50-10-90 20-130Z" fill="#8fb67c"/>
                    <path d="M330 180c40 10 70 40 70 80-50-10-80-30-70-80Z" fill="#b7a4e4"/>
                    <path d="M20 200c30-10 50 10 55 40-30 5-50-10-55-40Z" fill="#316d61" opacity=".75"/>
                    <path d="M360 90c20-30 50-30 70-10-30 8-50 16-70 10Z" fill="#a9d0c0"/>
                </svg>
            </div>
        </div>
    </section>

    <nav class="yk-explore__categories" aria-label="Explore categories">
        <div class="yk-explore__wrap">
            <ul>
                <li>
                    <a class="yk-explore__category is-all @if ($filters['category'] === '') is-selected @endif" href="{{ $link([], ['category', 'service', 'sub_service']) }}" @if ($filters['category'] === '') aria-current="true" @endif>
                        <span>@include('frontend.sections.partials.explore-icon', ['icon' => 'grid'])</span>
                        All
                    </a>
                </li>
                @foreach ($categories as $key => $category)
                    <li>
                        <a class="yk-explore__category is-{{ $category['tone'] }} @if ($filters['category'] === $key) is-selected @endif" href="{{ $link(['category' => $key], ['service', 'sub_service']) }}" @if ($filters['category'] === $key) aria-current="true" @endif>
                            <span>@include('frontend.sections.partials.explore-icon', ['icon' => $category['icon']])</span>
                            {{ $category['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div class="yk-explore__wrap yk-explore__layout">
        <button class="yk-explore__backdrop" type="button" data-explore-close aria-label="Close filters"></button>
        <form class="yk-explore__sidebar" method="get" action="{{ route('gridlisting') }}" data-explore-filters>
            <input type="hidden" name="type" value="all">
            @include('frontend.sections.partials.explore-state', ['omit' => ['type', 'types', 'price', 'rating', 'availability']])
            <div class="yk-explore__sidebar-head">
                <h2>Filters</h2>
                <a href="{{ route('gridlisting') }}">Reset</a>
            </div>

            <fieldset>
                <legend>Content Type</legend>
                @foreach ($contentTypes as $key => $label)
                    <label class="@if ($key === 'tools') is-disabled @endif">
                        <input type="checkbox" name="types[]" value="{{ $key }}" @checked(in_array($key, $activeTypes, true)) @disabled($key === 'tools')>
                        <span>{{ $label }}</span>
                        <small>({{ $counts['types'][$key] }})</small>
                    </label>
                @endforeach
                @unless ($toolsAvailable)
                    <p class="yk-explore__note">Tools are listed separately. <a href="{{ route('tools.index') }}">Open the Tools catalogue</a>.</p>
                @endunless
            </fieldset>

            <fieldset>
                <legend>Category</legend>
                @foreach ($categories as $key => $category)
                    <label>
                        <input type="checkbox" @checked($filters['category'] === $key) onchange="window.location.href=this.dataset.href" data-href="{{ $filters['category'] === $key ? $link([], ['category', 'service', 'sub_service']) : $link(['category' => $key], ['service', 'sub_service']) }}">
                        <span>{{ $category['label'] }}</span>
                        <small>({{ $counts['categories'][$key] }})</small>
                    </label>
                @endforeach
            </fieldset>

            <fieldset>
                <legend>Price / Cost</legend>
                <label>
                    <input type="checkbox" name="price[]" value="free" @checked(in_array('free', $filters['price'], true))>
                    <span>Free</span>
                    <small>({{ $counts['price']['free'] }})</small>
                </label>
                <label>
                    <input type="checkbox" name="price[]" value="paid" @checked(in_array('paid', $filters['price'], true))>
                    <span>Paid</span>
                    <small>({{ $counts['price']['paid'] }})</small>
                </label>
            </fieldset>

            @if ($ratingAvailable)
                <fieldset>
                    <legend>Rating</legend>
                    @foreach (['4.5' => '4.5 & above', '4' => '4.0 & above', '3.5' => '3.5 & above'] as $value => $label)
                        <label>
                            <input type="checkbox" name="rating[]" value="{{ $value }}" @checked(in_array($value, $filters['rating'], true))>
                            <span>{{ $label }}</span>
                            <small>({{ $counts['rating'][$value] }})</small>
                        </label>
                    @endforeach
                </fieldset>
            @endif

            @if ($availabilityAvailable)
                <fieldset>
                    <legend>Availability</legend>
                    <label>
                        <input type="checkbox" name="availability[]" value="now" @checked(in_array('now', $filters['availability'], true))>
                        <span>Available Now</span>
                        <small>({{ $counts['availability']['now'] }})</small>
                    </label>
                    <label>
                        <input type="checkbox" name="availability[]" value="anytime" @checked(in_array('anytime', $filters['availability'], true))>
                        <span>Anytime</span>
                        <small>({{ $counts['availability']['anytime'] }})</small>
                    </label>
                </fieldset>
            @endif
            <button class="yk-explore__close" type="button" data-explore-close>Close</button>
        </form>

        <section class="yk-explore__results" aria-label="Explore results">
            <div class="yk-explore__bar">
            <div class="yk-explore__tabs" role="tablist" aria-label="Content type">
                @foreach ($tabs as $key => $label)
                    <a role="tab" href="{{ $link(['type' => $key === 'all' ? null : $key], ['types']) }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="yk-explore__toolbar">
                <p>Showing {{ $items->total() }} {{ $items->total() === 1 ? 'result' : 'results' }}</p>
                <div class="yk-explore__toolbar-actions">
                    <button class="yk-explore__filter-open" type="button" data-explore-open>Filters</button>
                    <div class="yk-explore__views" aria-label="Result layout">
                        <a href="{{ $link(['view' => null]) }}" aria-label="Grid view" aria-pressed="{{ $filters['view'] === 'grid' ? 'true' : 'false' }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><rect x="14" y="14" width="6" height="6"/></svg>
                        </a>
                        <a href="{{ $link(['view' => 'list']) }}" aria-label="List view" aria-pressed="{{ $filters['view'] === 'list' ? 'true' : 'false' }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M5 12h14M5 17h14"/></svg>
                        </a>
                    </div>
                    <form method="get" action="{{ route('gridlisting') }}">
                        @include('frontend.sections.partials.explore-state', ['omit' => ['sort']])
                        <label for="explore-sort">Sort By</label>
                        <select id="explore-sort" name="sort" onchange="this.form.submit()">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            </div>

            @if ($tab === 'tools')
                <p class="yk-explore__empty">Tools live in the dedicated catalogue. <a href="{{ route('tools.index') }}">Browse Tools</a> — or switch tabs for experts{{ config('yuktione.programmes_enabled') ? ', programmes' : '' }} and articles.</p>
            @elseif ($items->isEmpty())
                <p class="yk-explore__empty">No results match these filters. Try another topic or <a href="{{ route('gridlisting') }}">reset Explore</a>.</p>
            @else
                <ul class="yk-explore__grid @if ($filters['view'] === 'list') is-list @endif">
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
                                    @if ($card['excerpt'])
                                        <p class="yk-card__excerpt">{{ \Illuminate\Support\Str::limit($card['excerpt'], 110) }}</p>
                                    @endif
                                    <p class="yk-card__meta">
                                        @if ($card['rating'])
                                            <span class="yk-card__rating"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.4 9.8 5.8l4.7.4-3.6 3.1 1.1 4.6L8 11.6 3.9 14l1.1-4.6L1.5 6.2l4.7-.4Z"/></svg> {{ $card['rating'] }} <span>({{ $card['review_count'] }})</span></span>
                                        @elseif ($card['kind'] === 'expert')
                                            <span class="yk-card__new">New</span>
                                        @endif
                                        @if ($card['price_label'])
                                            <strong>{{ $card['price_label'] }}@if ($card['price_suffix'])<small>{{ $card['price_suffix'] }}</small>@endif</strong>
                                        @endif
                                        @if ($card['meta'])
                                            <span>{{ $card['meta'] }}</span>
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
                    <nav class="yk-explore__pages" aria-label="Result pages">
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

    <section class="yk-explore__featured" aria-labelledby="explore-featured">
        <div class="yk-explore__wrap">
            <div class="yk-explore__featured-head">
                <h2 id="explore-featured">Featured for You</h2>
                <a href="{{ route('gridlisting') }}">View All</a>
            </div>
            <ul>
                @foreach ($featured as $item)
                    <li>
                        <a class="is-{{ $item['tone'] }}" href="{{ route('gridlisting', $item['query']) }}">
                            <span>@include('frontend.sections.partials.explore-icon', ['icon' => $item['icon']])</span>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</main>
<script>
(function () {
    var root = document.querySelector('[data-explore]');
    if (!root) return;
    var form = root.querySelector('[data-explore-filters]');
    var open = function (on) { root.classList.toggle('is-filters-open', on); };
    root.querySelector('[data-explore-open]')?.addEventListener('click', function () { open(true); });
    root.querySelectorAll('[data-explore-close]').forEach(function (button) {
        button.addEventListener('click', function () { open(false); });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') open(false);
    });
    if (form) {
        form.addEventListener('change', function (event) {
            if (event.target.matches('input[type="checkbox"]') && !event.target.dataset.href) form.submit();
        });
    }
})();
</script>
@endsection
