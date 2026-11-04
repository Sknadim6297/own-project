@extends('customer.layout.layout')

@section('title', 'My Bookings | Yuktione')

@section('content')
<div class="mx-auto grid max-w-7xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'My Bookings'],
    ]" />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Consultations</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">My Bookings</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
                Review your consultation bookings, payment status and related appointments.
            </p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-secondary">
            <x-tazen-v2.panel.icon name="inbox" class="size-4 text-brand-primary" />
            {{ $bookings->total() }} {{ Str::plural('booking', $bookings->total()) }}
        </span>
    </header>

    @if($bookings && $bookings->count() > 0)
        <div class="grid gap-4">
            @foreach($bookings as $booking)
                @php
                    $status = strtolower((string) ($booking->status ?? 'pending'));
                    $professionalName = $booking->professional->name ?? 'Not Assigned';
                    $serviceName = $booking->service_name ?? optional($booking->service)->name ?? 'N/A';
                @endphp
                <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6">
                    <div class="flex flex-col gap-4 border-b border-divider pb-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-brand-primary">Booking #{{ $booking->id }}</p>
                            <h2 class="mt-1 font-display text-lg font-semibold text-brand-secondary">{{ $serviceName }}</h2>
                        </div>
                        <x-tazen-v2.panel.status-badge :status="$status" />
                    </div>

                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Professional</dt>
                            <dd class="mt-1 text-sm font-medium text-text-primary">{{ $professionalName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Booking Date</dt>
                            <dd class="mt-1 text-sm font-medium text-text-primary">
                                {{ $booking->booking_date ? date('M d, Y', strtotime($booking->booking_date)) : 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Amount</dt>
                            <dd class="mt-1 text-sm font-medium text-text-primary">
                                {{ getCurrencySymbol() }}{{ number_format(convertCurrency($booking->amount), 2) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Payment</dt>
                            <dd class="mt-1 text-sm font-medium text-text-primary">
                                {{ ucfirst((string) ($booking->payment_status ?? 'N/A')) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex flex-wrap gap-2 border-t border-divider pt-4">
                        <a
                            href="{{ route('user.all-appointment.index') }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary transition-colors hover:bg-surface-muted"
                        >
                            View appointments
                        </a>
                        @if($booking->professional_id)
                            <a
                                href="{{ route('user.chat.open', $booking->id) }}"
                                class="inline-flex min-h-10 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white transition-colors hover:border-brand-800 hover:bg-brand-800"
                            >
                                Open chat
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-2">
            {{ $bookings->links() }}
        </div>
    @else
        <x-tazen-v2.panel.empty-state
            title="No bookings found"
            description="You haven't made any bookings yet. Find a professional to get started."
            icon="calendar"
        >
            <x-slot:actions>
                <x-tazen-v2.ui.button href="{{ route('professionals') }}" size="sm">Browse professionals</x-tazen-v2.ui.button>
            </x-slot:actions>
        </x-tazen-v2.panel.empty-state>
    @endif
</div>
@endsection
