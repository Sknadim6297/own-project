@extends('layouts.layout')

@section('title', 'Programmes | YuktiOne')
@section('meta_description', 'Discover learning experiences and expert-led programmes for your growth on YuktiOne.')
@section('body_class', 'yk-category-page')

@php
    $programmesEnabled = (bool) config('yuktione.programmes_enabled');
    $totalCount = method_exists($events, 'total') ? $events->total() : $events->count();
    $activeCategory = request('category');
    $activeCity = request('city');
    $activeMode = request('event_mode');
    $activePrice = $price_range ?? request('price_range');
    $activeFilter = request('filter');
    $hasFilters = $activeCategory || $activeCity || $activeMode || $activePrice || $activeFilter;
    $clearUrl = route('event.list');
@endphp

@section('content')
<main class="yk-cat-detail" id="programmes">
    @include('frontend.sections.partials.catalog-page-hero', [
        'title' => 'Programmes',
        'lead' => 'Discover learning experiences and expert-led sessions for your growth.',
        'backHref' => route('categories.index'),
        'backLabel' => 'All Categories',
    ])

    <section class="yk-cat-detail__tabs-wrap" aria-label="Programmes catalogue">
        <div class="yk-cat-detail__wrap">
            @include('frontend.sections.partials.catalog-section-nav', [
                'active' => 'programmes',
                'counts' => ['programmes' => $totalCount],
            ])

            <nav class="yk-cat-detail__tabs yk-cat-detail__tabs--filters" aria-label="Programme timing">
                <a class="yk-cat-detail__tab{{ ! $activeFilter ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['category' => $activeCategory, 'city' => $activeCity, 'event_mode' => $activeMode, 'price_range' => $activePrice])) }}">All</a>
                <a class="yk-cat-detail__tab{{ $activeFilter === 'today' ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['filter' => 'today', 'category' => $activeCategory])) }}">Today</a>
                <a class="yk-cat-detail__tab{{ $activeFilter === 'tomorrow' ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['filter' => 'tomorrow', 'category' => $activeCategory])) }}">Tomorrow</a>
                <a class="yk-cat-detail__tab{{ $activeFilter === 'weekend' ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['filter' => 'weekend', 'category' => $activeCategory])) }}">This weekend</a>
                <a class="yk-cat-detail__tab{{ $activeFilter === 'month' ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['filter' => 'month', 'category' => $activeCategory])) }}">This month</a>
            </nav>

            @if (! empty($categories) && count($categories))
                <nav class="yk-cat-detail__tabs yk-cat-detail__tabs--filters" aria-label="Programme categories">
                    <a class="yk-cat-detail__tab{{ ! $activeCategory ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['filter' => $activeFilter])) }}">All categories</a>
                    @foreach ($categories as $cat)
                        <a class="yk-cat-detail__tab{{ $activeCategory === $cat ? ' is-active' : '' }}" href="{{ route('event.list', array_filter(['category' => $cat, 'filter' => $activeFilter])) }}">{{ $cat }}</a>
                    @endforeach
                </nav>
            @endif

            <div class="yk-catalog-toolbar">
                <p class="yk-catalog-toolbar__count" aria-live="polite">
                    Showing {{ $totalCount }} {{ $totalCount === 1 ? 'programme' : 'programmes' }}
                    @if ($hasFilters)
                        <span>· matching filters</span>
                    @endif
                </p>
                @if ($hasFilters)
                    <a class="yk-catalog-toolbar__clear" href="{{ $clearUrl }}">Clear filters</a>
                @endif
            </div>

            @if (! $programmesEnabled)
                <div class="yk-cat-detail__empty" role="status">
                    <h2>Programmes unavailable</h2>
                    <p>Programmes are not enabled on the website right now.</p>
                </div>
            @elseif ($events->count() === 0)
                <div class="yk-cat-detail__empty" role="status">
                    <h2>No programmes found</h2>
                    <p>No programmes are available for the selected filters.</p>
                    @if ($hasFilters)
                        <a href="{{ $clearUrl }}">Clear filters</a>
                    @endif
                </div>
            @else
                <ul class="yk-rows yk-rows--programmes">
                    @foreach ($events as $event)
                        @php
                            $eventModel = $event->event ?? null;
                            if (! $eventModel) {
                                continue;
                            }
                            $isProfessionalEvent = isset($eventModel->created_by_type) && $eventModel->created_by_type === 'professional';
                            $eventImage = $eventModel->card_image ?? null;
                            $eventImage = is_array($eventImage) ? ($eventImage[0] ?? null) : $eventImage;
                            $eventImage = ltrim((string) $eventImage, '/');
                            $eventImageUrl = \Illuminate\Support\Str::startsWith($eventImage, ['http://', 'https://'])
                                ? $eventImage
                                : ($eventImage
                                    ? asset('storage/' . (\Illuminate\Support\Str::startsWith($eventImage, 'storage/')
                                        ? \Illuminate\Support\Str::after($eventImage, 'storage/')
                                        : $eventImage))
                                    : null);
                            $eventDate = $eventModel->date ?? ($eventModel->starting_date ?? null);
                            $isPastProgramme = $eventDate && \Carbon\Carbon::parse($eventDate)->startOfDay()->lt(\Carbon\Carbon::today());
                            $eventTime = $eventModel->time ?? null;
                            $title = $eventModel->heading ?? 'Programme';
                            $excerpt = trim((string) ($eventModel->short_description ?? $eventModel->mini_heading ?? ''));
                            if ($excerpt !== '') {
                                $excerpt = \Illuminate\Support\Str::limit($excerpt, 90, '…');
                            }
                            $metaBits = [];
                            if ($eventDate) {
                                $metaBits[] = \Carbon\Carbon::parse($eventDate)->format('M d, Y');
                            }
                            if ($eventTime) {
                                $metaBits[] = \Carbon\Carbon::parse($eventTime)->format('h:i A');
                            }
                            if ($isPastProgramme) {
                                $metaBits[] = 'Past';
                            }
                            $url = url('/allevent/' . $eventModel->id);
                            $initials = strtoupper(\Illuminate\Support\Str::substr(preg_replace('/[^A-Za-z]/', '', $title) ?: 'P', 0, 1));
                        @endphp
                        <li>
                            <a class="yk-row yk-row--programme" href="{{ $url }}">
                                <span class="yk-row__media" aria-hidden="true">
                                        @if ($eventImageUrl)
                                        <img src="{{ $eventImageUrl }}" alt="" width="48" height="48" loading="lazy" decoding="async">
                                            @else
                                        <span>{{ $initials }}</span>
                                            @endif
                                        </span>
                                <span class="yk-row__body">
                                    <span class="yk-row__title">{{ $title }}</span>
                                    @if ($excerpt !== '')
                                        <span class="yk-row__sub">{{ $excerpt }}</span>
                                    @endif
                                    @if (count($metaBits))
                                        <span class="yk-row__meta">{{ implode(' · ', $metaBits) }}</span>
                                    @endif
                                </span>
                                <span class="yk-row__aside">
                                    @if (is_numeric($eventModel->starting_fees ?? null))
                                        <span class="yk-row__price">{{ formatPrice($eventModel->starting_fees) }}</span>
                                    @endif
                                    <span class="yk-row__chev" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if (method_exists($events, 'hasPages') && $events->hasPages())
                    <nav class="yk-catalog-pages" aria-label="Programme pages">
                    @if ($events->onFirstPage())
                            <span aria-disabled="true">Previous</span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}">Previous</a>
                        @endif
                        <span>Page {{ $events->currentPage() }} of {{ $events->lastPage() }}</span>
                    @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}">Next</a>
                    @else
                            <span aria-disabled="true">Next</span>
                    @endif
                    </nav>
                @endif
                @endif
            </div>
    </section>
</main>
@endsection
