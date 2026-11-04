@extends('customer.layout.layout')

@section('title', 'Programme Bookings | Yuktione')

@section('content')
@php
    $hasActiveFilters = request()->filled('search_name')
        || request()->filled('search_type')
        || request()->filled('search_date_from')
        || request()->filled('search_date_to');
@endphp

<div class="mx-auto grid max-w-7xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'Programmes'],
    ]" />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Your reservations</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">Programme Bookings</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
                Manage your programme reservations, review upcoming sessions and access your booked programmes.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-secondary">
                <x-tazen-v2.panel.icon name="calendar" class="size-4 text-brand-primary" />
                {{ $bookings->count() }} {{ Str::plural('programme booking', $bookings->count()) }}
            </span>
            <a href="{{ route('user.customer-event.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 11.25a8.25 8.25 0 0 0-14.4-5.46L3.75 8.25m0 0V3.75m0 4.5h4.5m-4.5 4.5a8.25 8.25 0 0 0 14.4 5.46l2.1-2.46m0 0v4.5m0-4.5h-4.5" />
                </svg>
                Refresh List
            </a>
        </div>
    </header>

    <section class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6" aria-labelledby="event-filters-heading">
        <div class="mb-5">
            <h2 id="event-filters-heading" class="font-display text-card-title font-semibold text-brand-secondary">Filter Programme Bookings</h2>
            <p class="mt-1 text-sm text-text-secondary">Find a reservation by programme name, type or date.</p>
        </div>

        <form action="{{ route('user.customer-event.index') }}" method="GET" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="grid gap-1.5">
                <label for="search_name" class="text-sm font-semibold text-text-primary">Search</label>
                <div class="relative">
                    <x-tazen-v2.panel.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-text-muted" />
                    <input type="search" name="search_name" id="search_name" value="{{ request('search_name') }}" placeholder="Search programmes"
                        class="min-h-11 w-full rounded-control border border-border bg-surface py-2 pl-10 pr-3 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary">
                </div>
            </div>

            <div class="grid gap-1.5">
                <label for="search_type" class="text-sm font-semibold text-text-primary">Programme type</label>
                <select name="search_type" id="search_type" class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
                    <option value="">All types</option>
                    <option value="online" @selected(request('search_type') === 'online')>Online</option>
                    <option value="offline" @selected(request('search_type') === 'offline')>Offline</option>
                </select>
            </div>

            <div class="grid gap-1.5">
                <label for="search_date_from" class="text-sm font-semibold text-text-primary">From date</label>
                <input type="date" name="search_date_from" id="search_date_from" value="{{ request('search_date_from') }}"
                    class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
            </div>

            <div class="grid gap-1.5">
                <label for="search_date_to" class="text-sm font-semibold text-text-primary">To date</label>
                <input type="date" name="search_date_to" id="search_date_to" value="{{ request('search_date_to') }}"
                    class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
            </div>

            <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row xl:col-span-4">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-brand-primary bg-brand-primary px-4 text-sm font-semibold text-white transition-colors hover:border-brand-800 hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    <x-tazen-v2.panel.icon name="search" class="size-4" />
                    Apply Filters
                </button>
                <a href="{{ route('user.customer-event.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-panel border border-border bg-surface shadow-card" aria-labelledby="event-bookings-heading">
        <div class="flex flex-col gap-1 border-b border-divider px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="event-bookings-heading" class="font-display text-card-title font-semibold text-brand-secondary">Your Programme Bookings</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    {{ $bookings->count() }} {{ Str::plural('booking', $bookings->count()) }} found
                    @if(request('search_name'))
                        <span>· Search: {{ request('search_name') }}</span>
                    @endif
                    @if(request('search_type'))
                        <span>· {{ ucfirst(request('search_type')) }}</span>
                    @endif
                    @if(request('search_date_from'))
                        <span>· From {{ \Carbon\Carbon::parse(request('search_date_from'))->format('M d, Y') }}</span>
                    @endif
                    @if(request('search_date_to'))
                        <span>· To {{ \Carbon\Carbon::parse(request('search_date_to'))->format('M d, Y') }}</span>
                    @endif
                </p>
            </div>
        </div>

        @if($bookings->isEmpty())
            <div class="p-5 sm:p-8">
                <x-tazen-v2.panel.empty-state
                    icon="calendar"
                    :title="$hasActiveFilters ? 'No Matching Programme Bookings' : 'No Programmes Found'"
                    :description="$hasActiveFilters
                        ? 'Try adjusting your filters or clear them to see all of your programme bookings.'
                        : 'You have not booked any programmes yet. Explore available programmes to find your next session.'"
                >
                    <x-slot:actions>
                        @if($hasActiveFilters)
                            <a href="{{ route('user.customer-event.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary hover:bg-surface-muted">
                                Clear Filters
                            </a>
                        @elseif(config('yuktione.programmes_enabled'))
                            <x-tazen-v2.ui.button href="{{ route('programmes.index') }}" size="sm">Explore Programmes</x-tazen-v2.ui.button>
                        @else
                            <x-tazen-v2.ui.button href="{{ route('user.dashboard') }}" size="sm" variant="outline">Back to Dashboard</x-tazen-v2.ui.button>
                        @endif
                    </x-slot:actions>
                </x-tazen-v2.panel.empty-state>
            </div>
        @else
            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
                @foreach($bookings as $booking)
                    @php
                        $eventTitle = $booking->event?->heading ?: 'Programme title unavailable';
                        $eventDate = $booking->event_date;
                        $eventTime = $booking->event?->time;
                        $joinLink = $booking->event?->meet_link ?: $booking->gmeet_link;
                    @endphp
                    <article class="flex min-w-0 flex-col rounded-card border border-border bg-surface p-4 shadow-card transition-shadow hover:shadow-popover sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="min-w-0 break-words font-display text-base font-semibold leading-6 text-brand-secondary">{{ $eventTitle }}</h3>
                            @if($booking->payment_status === 'success')
                                <span class="inline-flex shrink-0 rounded-full border border-success bg-success-surface px-2.5 py-1 text-xs font-semibold text-success">Confirmed</span>
                            @elseif($booking->payment_status === 'failed')
                                <span class="inline-flex shrink-0 rounded-full border border-warning bg-warning-surface px-2.5 py-1 text-xs font-semibold text-warning">Failed</span>
                            @else
                                <span class="inline-flex shrink-0 rounded-full border border-border bg-surface-muted px-2.5 py-1 text-xs font-semibold text-text-secondary">Unknown</span>
                            @endif
                        </div>

                        <dl class="mt-4 grid gap-3 border-t border-divider pt-4 text-sm">
                            <div class="flex items-start gap-3">
                                <x-tazen-v2.panel.icon name="calendar" class="mt-0.5 size-4 text-brand-primary" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-text-muted">Programme date{{ $eventTime ? ' and time' : '' }}</dt>
                                    <dd class="mt-0.5 font-medium text-text-primary">{{ $eventDate ? \Carbon\Carbon::parse($eventDate)->format('M d, Y') : 'Date unavailable' }}@if($eventTime) · {{ $eventTime }}@endif</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <x-tazen-v2.panel.icon name="spark" class="mt-0.5 size-4 text-brand-primary" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-text-muted">Programme type</dt>
                                    <dd class="mt-0.5 font-medium text-text-primary">{{ $booking->type ? ucfirst($booking->type) : 'Not specified' }}</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <x-tazen-v2.panel.icon name="inbox" class="mt-0.5 size-4 text-brand-primary" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-text-muted">Venue / address</dt>
                                    <dd class="mt-0.5 break-words text-text-primary">{{ $booking->location ?: 'Not provided' }}</dd>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <dt class="text-xs font-medium text-text-muted">Attendees</dt>
                                    <dd class="mt-0.5 font-medium text-text-primary">{{ $booking->persons ?? 'Not specified' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-text-muted">Total price</dt>
                                    <dd class="mt-0.5 font-semibold text-brand-secondary">{{ getCurrencySymbol() }}{{ number_format(convertCurrency($booking->total_price), 2) }}</dd>
                                </div>
                            </div>
                        </dl>

                        <div class="mt-5 border-t border-divider pt-4">
                            @if($joinLink)
                                <a href="{{ $joinLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-control border border-brand-primary bg-brand-primary px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5 20.25 8.25v7.5l-4.5-2.25M4.5 6.75h9A2.25 2.25 0 0 1 15.75 9v6A2.25 2.25 0 0 1 13.5 17.25h-9A2.25 2.25 0 0 1 2.25 15V9A2.25 2.25 0 0 1 4.5 6.75Z" />
                                    </svg>
                                    Join Programme
                                </a>
                            @else
                                <p class="text-center text-xs text-text-muted">No programme link available</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
