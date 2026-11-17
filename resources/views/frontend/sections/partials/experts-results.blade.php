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
    $sorts = [
        'relevant' => 'Most Relevant',
        'newest' => 'Newest',
        'rating' => 'Highest Rated',
        'price_asc' => 'Price Low to High',
        'price_desc' => 'Price High to Low',
    ];
    $filtering = ($filters['q'] ?? '') !== ''
        || ($filters['category'] ?? '') !== ''
        || ($filters['service'] ?? '') !== ''
        || ($filters['experience'] ?? '') !== ''
        || ($filters['price'] ?? []) !== []
        || ($filters['rating'] ?? []) !== []
        || ($filters['availability'] ?? []) !== [];
    $clearFiltersUrl = $link([], ['q', 'category', 'service', 'sub_service', 'experience', 'price', 'rating', 'availability', 'sort']);
    $searching = ($filters['q'] ?? '') !== '';
@endphp

<div class="yk-experts__toolbar">
    <div class="yk-experts__count" aria-live="polite">
        <span class="yk-experts__count-pill">
            <span class="yk-experts__count-num">{{ number_format($items->total()) }}</span>
            <span class="yk-experts__count-label">{{ $items->total() === 1 ? 'Expert' : 'Experts' }} available</span>
        </span>
        @if ($filtering)
            <span class="yk-experts__count-hint">{{ $searching ? 'Matching your search' : 'Showing filtered results' }}</span>
        @endif
    </div>
    <div class="yk-experts__toolbar-actions">
        @if ($filtering)
            <a class="yk-experts__clear" href="{{ $clearFiltersUrl }}" data-experts-clear-filters>Clear filters</a>
        @endif
        <form class="yk-experts__sort" method="get" action="{{ route('professionals') }}">
            @foreach (request()->except('sort', 'page', 'partial') as $key => $value)
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
                    <option value="{{ $value }}" @selected(($filters['sort'] ?? 'relevant') === $value)>{{ $label }}</option>
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
        @if ($searching)
            <h2>No matching results found</h2>
            <p>No accepted experts match that name prefix. Try fewer characters or clear the search.</p>
            <div class="yk-experts__empty-actions">
                <button type="button" class="yk-xcard__btn yk-xcard__btn--solid" data-experts-clear-search>Clear search</button>
                @if (($filters['category'] ?? '') !== '')
                    <a class="yk-xcard__btn yk-xcard__btn--ghost" href="{{ $link([], ['experience', 'q', 'service', 'sub_service', 'price', 'rating', 'availability']) }}">Keep {{ $categories[$filters['category']]['label'] ?? 'category' }}</a>
                @endif
            </div>
        @elseif ($filtering)
            <h2>No matching experts</h2>
            <p>No accepted experts currently match these filters. Try another category or clear filters to browse everyone.</p>
            <div class="yk-experts__empty-actions">
                <a class="yk-xcard__btn yk-xcard__btn--solid" href="{{ $clearFiltersUrl }}">View all experts</a>
                @if (($filters['category'] ?? '') !== '')
                    <a class="yk-xcard__btn yk-xcard__btn--ghost" href="{{ $link([], ['experience', 'q', 'service', 'sub_service', 'price', 'rating', 'availability']) }}">Keep {{ $categories[$filters['category']]['label'] ?? 'category' }}</a>
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
            <li>@include('frontend.sections.partials.catalog-expert-row', ['expert' => $card])</li>
        @endforeach
    </ul>

    @if ($items->hasPages())
        @php
            $current = $items->currentPage();
            $last = $items->lastPage();
            $window = collect(range(max(1, $current - 1), min($last, $current + 1)));
            if ($window->first() > 1) {
                $window = $window->prepend(1);
            }
            if ($window->last() < $last) {
                $window = $window->push($last);
            }
            $window = $window->unique()->values();
        @endphp
        <nav class="yk-experts__pager" aria-label="Expert pages">
            @if ($items->onFirstPage())
                <span class="yk-experts__pager-btn is-disabled" aria-disabled="true">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                    <span>Prev</span>
                </span>
            @else
                <a class="yk-experts__pager-btn" href="{{ $items->previousPageUrl() }}" data-experts-page>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                    <span>Prev</span>
                </a>
            @endif

            <div class="yk-experts__pager-pages">
                @foreach ($window as $page)
                    @if (!$loop->first && $page - $window[$loop->index - 1] > 1)
                        <span class="yk-experts__pager-gap" aria-hidden="true">…</span>
                    @endif
                    @if ($page === $current)
                        <span class="yk-experts__pager-num is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="yk-experts__pager-num" href="{{ $items->url($page) }}" data-experts-page>{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            @if ($items->hasMorePages())
                <a class="yk-experts__pager-btn" href="{{ $items->nextPageUrl() }}" data-experts-page>
                    <span>Next</span>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5"/></svg>
                </a>
            @else
                <span class="yk-experts__pager-btn is-disabled" aria-disabled="true">
                    <span>Next</span>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5"/></svg>
                </span>
            @endif
        </nav>
    @endif
@endif
