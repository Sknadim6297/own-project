@extends('layouts.layout')

@section('title', 'Tools | YuktiOne')
@section('meta_description', 'Practical tools and resources to support your mental, emotional and personal growth.')
@section('body_class', 'yk-tools-page')

@php
    $base = request()->except('page');
    $link = function (array $set = [], array $forget = []) use ($base) {
        $params = $base;
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

        return route('tools.index', $params);
    };
    $filtering = $filters['q'] !== '' || $filters['category'] !== '';
    $clearFiltersUrl = $link([], ['q', 'category']);
    $allToolsCount = $filters['category'] === ''
        ? $items->total()
        : collect($counts ?? [])->sum();
@endphp

@section('content')
<main class="yk-tools" id="tools" data-tools>
    <div class="yk-tools__wrap">
        <header class="yk-tools__hero">
            <div class="yk-tools__hero-copy">
                <p class="yk-tools__eyebrow" aria-hidden="true"></p>
                <h1 id="tools-heading">Try Our <span>Tools</span></h1>
                <p class="yk-tools__lead">Practical tools for mental, emotional and personal growth — open what you need, when you need it.</p>
            </div>
            <div class="yk-tools__hero-visual">
                <img
                    class="yk-tools__hero-img"
                    src="{{ asset('frontend/assets/img/yuktione-tools-hero.png') }}"
                    alt="Mindful wellness — calm focus with growth, health and clarity"
                    width="900"
                    height="900"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        </header>

        <nav class="yk-tools__cats" aria-label="Tool categories" role="tablist">
            <a
                href="{{ $link([], ['category']) }}"
                class="yk-tools__chip{{ $filters['category'] === '' ? ' is-active' : '' }}"
                role="tab"
                aria-selected="{{ $filters['category'] === '' ? 'true' : 'false' }}"
            >
                All Tools
                <span class="yk-tools__chip-count">({{ $allToolsCount }})</span>
            </a>
            @foreach ($categories as $slug => $category)
                <a
                    href="{{ $link(['category' => $slug]) }}"
                    class="yk-tools__chip is-{{ $category['tone'] }}{{ $filters['category'] === $slug ? ' is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $filters['category'] === $slug ? 'true' : 'false' }}"
                >
                    {{ $category['label'] }}
                    <span class="yk-tools__chip-count">({{ $counts[$slug] ?? 0 }})</span>
                </a>
            @endforeach
        </nav>

        <section class="yk-tools__results" aria-label="Tool results">
            <div class="yk-tools__toolbar">
                <p class="yk-tools__count" aria-live="polite">
                    Showing {{ $items->total() }} {{ $items->total() === 1 ? 'tool' : 'tools' }}
                    @if ($filtering)
                        <span class="yk-tools__count-hint">matching filters</span>
                    @endif
                </p>
                <div class="yk-tools__toolbar-actions">
                    @if ($filtering)
                        <a class="yk-tools__clear" href="{{ $clearFiltersUrl }}">Clear filters</a>
                    @endif
                    <form class="yk-tools__search" action="{{ route('tools.index') }}" method="get" role="search">
                        @if ($filters['category'] !== '')
                            <input type="hidden" name="category" value="{{ $filters['category'] }}">
                        @endif
                        <label class="visually-hidden" for="tools-search">Search tools</label>
                        <span class="yk-tools__search-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16.2 16.2 3.3 3.3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <input id="tools-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search tools…" autocomplete="off" maxlength="100">
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>

            @if ($items->isEmpty())
                <div class="yk-tools__empty" role="status">
                    <div class="yk-tools__empty-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><path d="M16.75 13.5v6.5M13.5 16.75h6.5"/></svg>
                    </div>
                    @if ($filtering)
                        <h2>No tools match these filters</h2>
                        <p>Try another category or search term, or clear filters to browse everything available.</p>
                        <div class="yk-tools__empty-actions">
                            <a class="yk-tools__btn yk-tools__btn--primary" href="{{ $clearFiltersUrl }}">Clear filters</a>
                            @if ($filters['category'] !== '')
                                <a class="yk-tools__btn yk-tools__btn--ghost" href="{{ $link([], ['q']) }}">Keep {{ $categories[$filters['category']]['label'] ?? 'category' }}</a>
                            @endif
                        </div>
                    @else
                        <h2>No published tools yet</h2>
                        <p>When a tool is ready for everyone, it will appear in this catalogue.</p>
                    @endif
                </div>
            @else
                <ul class="yk-tools__grid">
                    @foreach ($items as $tool)
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

                @if ($items->hasPages())
                    <nav class="yk-tools__pages" aria-label="Tool pages">
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
