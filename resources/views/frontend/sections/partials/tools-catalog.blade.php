{{--
    Tools catalogue (category chips, search, grid, pager).
    Rendered by /tools (tools.index) and by /professionals?view=tools (Career Tools tab).
    Expects: $tItems (paginator), $tFilters, $tCategories, $tCounts, $tRoute (route name), $tExtra (hidden form fields).
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
    $tFiltering = $tFilters['q'] !== '' || $tFilters['category'] !== '';
    $tClearUrl = $tLink([], ['q', 'category']);
    $tAllCount = $tFilters['category'] === ''
        ? $tItems->total()
        : collect($tCounts)->sum();
@endphp

<nav class="yk-tools__cats" aria-label="Tool categories" role="tablist">
    <a
        href="{{ $tLink([], ['category']) }}"
        class="yk-tools__chip{{ $tFilters['category'] === '' ? ' is-active' : '' }}"
        role="tab"
        aria-selected="{{ $tFilters['category'] === '' ? 'true' : 'false' }}"
    >
        <span class="yk-tools__chip-icon" aria-hidden="true">
            @include('frontend.sections.partials.tool-icon-svg', ['icon' => 'grid'])
        </span>
        All Tools
        <span class="yk-tools__chip-count">({{ $tAllCount }})</span>
    </a>
    @foreach ($tCategories as $slug => $category)
        <a
            href="{{ $tLink(['category' => $slug]) }}"
            class="yk-tools__chip is-{{ $category['tone'] }}{{ $tFilters['category'] === $slug ? ' is-active' : '' }}"
            role="tab"
            aria-selected="{{ $tFilters['category'] === $slug ? 'true' : 'false' }}"
        >
            <span class="yk-tools__chip-icon" aria-hidden="true">
                @include('frontend.sections.partials.tool-icon-svg', ['icon' => $category['icon'] ?? 'leaf'])
            </span>
            {{ $category['label'] }}
            <span class="yk-tools__chip-count">({{ $tCounts[$slug] ?? 0 }})</span>
        </a>
    @endforeach
</nav>

<section class="yk-tools__results" aria-label="Tool results">
    <div class="yk-tools__toolbar">
        <p class="yk-tools__count" aria-live="polite">
            Showing {{ $tItems->total() }} {{ $tItems->total() === 1 ? 'tool' : 'tools' }}
            @if ($tFiltering)
                <span class="yk-tools__count-hint">matching filters</span>
            @endif
        </p>
        <div class="yk-tools__toolbar-actions">
            @if ($tFiltering)
                <a class="yk-tools__clear" href="{{ $tClearUrl }}">Clear filters</a>
            @endif
            <form class="yk-tools__search" action="{{ route($tRoute) }}" method="get" role="search">
                @foreach ($tExtra as $extraName => $extraValue)
                    <input type="hidden" name="{{ $extraName }}" value="{{ $extraValue }}">
                @endforeach
                @if ($tFilters['category'] !== '')
                    <input type="hidden" name="category" value="{{ $tFilters['category'] }}">
                @endif
                <label class="visually-hidden" for="tools-search">Search tools</label>
                <span class="yk-tools__search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16.2 16.2 3.3 3.3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <input id="tools-search" type="search" name="q" value="{{ $tFilters['q'] }}" placeholder="Search tools…" autocomplete="off" maxlength="100">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    @if ($tItems->isEmpty())
        <div class="yk-tools__empty" role="status">
            <div class="yk-tools__empty-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><path d="M16.75 13.5v6.5M13.5 16.75h6.5"/></svg>
            </div>
            @if ($tFiltering)
                <h2>No tools match these filters</h2>
                <p>Try another category or search term, or clear filters to browse everything available.</p>
                <div class="yk-tools__empty-actions">
                    <a class="yk-tools__btn yk-tools__btn--primary" href="{{ $tClearUrl }}">Clear filters</a>
                    @if ($tFilters['category'] !== '')
                        <a class="yk-tools__btn yk-tools__btn--ghost" href="{{ $tLink([], ['q']) }}">Keep {{ $tCategories[$tFilters['category']]['label'] ?? 'category' }}</a>
                    @endif
                </div>
            @else
                <h2>No published tools yet</h2>
                <p>When a tool is ready for everyone, it will appear in this catalogue.</p>
            @endif
        </div>
    @else
        <ul class="yk-tools__grid">
            @foreach ($tItems as $tool)
                <li>
                    @include('frontend.sections.partials.tool-card', [
                        'label' => $tool['name'],
                        'url' => $tool['url'],
                        'icon' => $tool['icon'],
                        'slug' => $tool['slug'] ?? '',
                        'description' => $tool['description'] ?? '',
                    ])
                </li>
            @endforeach
        </ul>

        @if ($tItems->hasPages())
            <nav class="yk-tools__pages" aria-label="Tool pages">
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
