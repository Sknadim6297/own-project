{{--
    Tools catalogue (category pills, search, home-style tiles, pager).
    Same tool-tile cards as home "Try Our Tools" and /categories/{slug}.
    Expects: $tItems, $tFilters, $tCategories, $tCounts, $tRoute, $tExtra.
--}}
@php
    $tBase = request()->except('page', 'partial');
    $tLink = function (array $set = [], array $forget = []) use ($tBase, $tRoute) {
        $params = $tBase;
        foreach ($forget as $key) {
            unset($params[$key]);
        }
        foreach ($set as $key => $value) {
            if ($value === null || $value === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return route($tRoute, $params);
    };
    $tCategoryActive = (string) ($tFilters['category'] ?? '');
    $tHasCategoryFilter = $tCategoryActive !== '' || ! empty($tFilters['category_any']);
    $tFiltering = ($tFilters['q'] ?? '') !== '' || $tHasCategoryFilter;
    $tClearUrl = $tLink([], ['q', 'category']);
    $tAllCount = $tHasCategoryFilter ? collect($tCounts)->sum() : $tItems->total();
@endphp

<nav class="yk-cat-detail__tabs yk-cat-detail__tabs--filters" aria-label="Tool categories" role="tablist">
    <a
        href="{{ $tLink([], ['category']) }}"
        class="yk-tools__chip{{ ! $tHasCategoryFilter ? ' is-active' : '' }}"
        role="tab"
        aria-selected="{{ ! $tHasCategoryFilter ? 'true' : 'false' }}"
    >
        All
        <span class="yk-cat-detail__count">{{ $tAllCount }}</span>
    </a>
    @foreach ($tCategories as $slug => $category)
        <a
            href="{{ $tLink(['category' => $slug]) }}"
<<<<<<< Updated upstream
            class="yk-cat-detail__tab{{ $tFilters['category'] === $slug ? ' is-active' : '' }}"
=======
            class="yk-tools__chip is-{{ $category['tone'] }}{{ $tCategoryActive === $slug || in_array($slug, $tFilters['category_any'] ?? [], true) ? ' is-active' : '' }}"
>>>>>>> Stashed changes
            role="tab"
            aria-selected="{{ $tCategoryActive === $slug || in_array($slug, $tFilters['category_any'] ?? [], true) ? 'true' : 'false' }}"
        >
            {{ $category['label'] }}
            <span class="yk-cat-detail__count">{{ $tCounts[$slug] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

<section class="yk-catalog-panel" aria-label="Tool results">
    <div class="yk-catalog-toolbar">
        <p class="yk-catalog-toolbar__count" aria-live="polite">
            Showing {{ $tItems->total() }} {{ $tItems->total() === 1 ? 'tool' : 'tools' }}
            @if ($tFiltering)
                <span>· matching filters</span>
            @endif
        </p>
        <div class="yk-catalog-toolbar__actions">
            @if ($tFiltering)
                <a class="yk-catalog-toolbar__clear" href="{{ $tClearUrl }}">Clear filters</a>
            @endif
            <form class="yk-catalog-search" action="{{ route($tRoute) }}" method="get" role="search">
                @foreach ($tExtra as $extraName => $extraValue)
                    <input type="hidden" name="{{ $extraName }}" value="{{ $extraValue }}">
                @endforeach
                @if ($tFilters['category'] !== '')
                    <input type="hidden" name="category" value="{{ $tFilters['category'] }}">
                @endif
                <label class="visually-hidden" for="tools-search">Search tools</label>
                <span class="yk-catalog-search__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16.2 16.2 3.3 3.3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <input id="tools-search" type="search" name="q" value="{{ $tFilters['q'] }}" placeholder="Search tools…" autocomplete="off" maxlength="100">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    @if ($tItems->isEmpty())
        <div class="yk-cat-detail__empty" role="status">
            @if ($tFiltering)
                <h2>No tools match these filters</h2>
                <p>Try another category or search term, or clear filters to browse everything available.</p>
                <a href="{{ $tClearUrl }}">Clear filters</a>
            @else
                <h2>No published tools yet</h2>
                <p>When a tool is ready for everyone, it will appear in this catalogue.</p>
            @endif
        </div>
    @else
        <ul class="yk-tools__tiles yk-tools__tiles--catalog">
            @foreach ($tItems as $tool)
                <li>
                    @include('frontend.sections.partials.tool-tile', [
                        'label' => $tool['name'] ?? '',
                        'url' => $tool['url'] ?? '#',
                        'icon' => $tool['icon'] ?? 'leaf',
                        'slug' => $tool['slug'] ?? '',
                    ])
                </li>
            @endforeach
        </ul>

        @if ($tItems->hasPages())
            <nav class="yk-catalog-pages" aria-label="Tool pages">
                @if ($tItems->onFirstPage())
                    <span aria-disabled="true">Previous</span>
                @else
                    <a href="{{ $tItems->previousPageUrl() }}">Previous</a>
                @endif
                <span>Page {{ $tItems->currentPage() }} of {{ $tItems->lastPage() }}</span>
                @if ($tItems->hasMorePages())
                    <a href="{{ $tItems->nextPageUrl() }}">Next</a>
                @else
                    <span aria-disabled="true">Next</span>
                @endif
            </nav>
        @endif
    @endif
</section>
