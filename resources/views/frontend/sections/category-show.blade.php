@extends('layouts.layout')

@section('title', $category['label'] . ' | YuktiOne')
@section('meta_description', ($category['tagline'] !== '' ? $category['tagline'] : 'Explore tools and experts for '.$category['label'].' on YuktiOne.'))
@section('body_class', 'yk-category-page')

@section('content')
<main class="yk-cat-detail" data-category-detail data-category-slug="{{ $category['slug'] }}">
    <section class="yk-cat-detail__hero">
        <div class="yk-cat-detail__wrap">
            <a class="yk-cat-detail__back" href="{{ route('categories.index') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 6 9 12l6 6"/></svg>
                <span>All Categories</span>
            </a>
            <h1>{{ $category['label'] }}</h1>
            @if ($category['tagline'] !== '')
                <p class="yk-cat-detail__lead">{{ $category['tagline'] }}</p>
            @endif
        </div>
    </section>

    <section class="yk-cat-detail__tabs-wrap" aria-label="Category sections">
        <div class="yk-cat-detail__wrap">
            <div class="yk-cat-detail__tabs" role="tablist" aria-label="{{ $category['label'] }} content">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        class="yk-cat-detail__tab {{ $tab === $key ? 'is-active' : '' }}"
                        role="tab"
                        id="cat-tab-{{ $key }}"
                        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        aria-controls="cat-panel-{{ $key }}"
                        data-cat-tab="{{ $key }}"
                    >
                        {{ $label }}
                        <span class="yk-cat-detail__count">{{ $counts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>

            <div
                id="cat-panel-tools"
                class="yk-cat-detail__panel {{ $tab === 'tools' ? 'is-active' : '' }}"
                role="tabpanel"
                aria-labelledby="cat-tab-tools"
                data-cat-panel="tools"
                @if ($tab !== 'tools') hidden @endif
            >
                @if ($tools->isEmpty())
                    <div class="yk-cat-detail__empty" role="status">
                        <h2>No tools yet</h2>
                        <p>Launchable tools for this category will appear here when published.</p>
                        <a href="{{ route('tools.index') }}">Browse all tools</a>
                    </div>
                @else
                    <ul class="yk-rows yk-rows--tools">
                        @foreach ($tools as $tool)
                            <li>@include('frontend.sections.partials.catalog-tool-row', ['tool' => $tool])</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div
                id="cat-panel-experts"
                class="yk-cat-detail__panel {{ $tab === 'experts' ? 'is-active' : '' }}"
                role="tabpanel"
                aria-labelledby="cat-tab-experts"
                data-cat-panel="experts"
                @if ($tab !== 'experts') hidden @endif
            >
                @if ($experts->isEmpty())
                    <div class="yk-cat-detail__empty" role="status">
                        <h2>No experts yet</h2>
                        <p>Verified professionals for this category will appear here when available.</p>
                        <a href="{{ route('professionals') }}">Browse all experts</a>
                    </div>
                @else
                    <ul class="yk-rows yk-rows--experts">
                        @foreach ($experts as $expert)
                            <li>@include('frontend.sections.partials.catalog-expert-row', ['expert' => $expert])</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div
                id="cat-panel-programmes"
                class="yk-cat-detail__panel {{ $tab === 'programmes' ? 'is-active' : '' }}"
                role="tabpanel"
                aria-labelledby="cat-tab-programmes"
                data-cat-panel="programmes"
                @if ($tab !== 'programmes') hidden @endif
            >
                @if (! $programmesEnabled)
                    <div class="yk-cat-detail__empty" role="status">
                        <h2>Programmes unavailable</h2>
                        <p>Programmes are not enabled on the website right now.</p>
                    </div>
                @elseif ($programmes->isEmpty())
                    <div class="yk-cat-detail__empty" role="status">
                        <h2>No programmes yet</h2>
                        <p>Category-linked programmes will appear here when published.</p>
                    </div>
                @else
                    <ul class="yk-cat-detail__list">
                        @foreach ($programmes as $programme)
                            <li>
                                <a class="yk-cat-detail__card" href="{{ $programme['url'] }}">
                                    <span class="yk-cat-detail__media" aria-hidden="true">
                                        @if (!empty($programme['image']))
                                            <img src="{{ $programme['image'] }}" alt="" width="64" height="64" loading="lazy">
                                        @else
                                            <span>{{ $programme['initials'] ?? 'P' }}</span>
                                        @endif
                                    </span>
                                    <span class="yk-cat-detail__copy">
                                        <span class="yk-cat-detail__title">{{ $programme['title'] }}</span>
                                        @if (!empty($programme['excerpt']))
                                            <span class="yk-cat-detail__text">{{ \Illuminate\Support\Str::limit($programme['excerpt'], 110) }}</span>
                                        @endif
                                    </span>
                                    <span class="yk-cat-detail__cta">{{ $programme['cta'] ?? 'View' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-category-detail]');
    if (!root) return;

    var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-cat-tab]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[data-cat-panel]'));
    var slug = root.getAttribute('data-category-slug') || '';

    function activate(name, push) {
        tabs.forEach(function (tab) {
            var on = tab.getAttribute('data-cat-tab') === name;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            var on = panel.getAttribute('data-cat-panel') === name;
            panel.classList.toggle('is-active', on);
            if (on) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        });
        if (push && window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            window.history.replaceState({ tab: name }, '', url.toString());
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activate(tab.getAttribute('data-cat-tab'), true);
        });
    });

    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        var next = params.get('tab') || 'tools';
        if (!root.querySelector('[data-cat-tab="' + next + '"]')) next = 'tools';
        activate(next, false);
    });
})();
</script>
@endpush
