@extends('customer.layout.layout')

@section('content')
@php
    $hasActiveFilters = request()->filled('search_name')
        || (request()->filled('service') && request('service') !== 'all')
        || (request()->filled('plan_type') && request('plan_type') !== 'all')
        || request()->filled('search_date_from')
        || request()->filled('search_date_to');

    if (! function_exists('customerFormatPlanType')) {
        function customerFormatPlanType($planType) {
            if (empty($planType)) {
                return null;
            }

            $value = strtolower($planType);

            return match ($value) {
                'one_time' => 'One Time',
                'no_plan' => 'No Plan',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'free_hand' => 'Free Hand',
                default => ucwords(str_replace('_', ' ', $planType)),
            };
        }
    }
@endphp
<div class="appointments-page mx-auto grid max-w-7xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'All Appointments'],
    ]" />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Consultations</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">All Appointments</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
                Manage your consultations, review booking history, access documents and communicate with professionals.
            </p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-secondary">
            <x-tazen-v2.panel.icon name="calendar" class="size-4 text-brand-primary" />
            {{ $bookings->count() }} {{ Str::plural('appointment', $bookings->count()) }}
        </span>
    </header>

    <section class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6" aria-labelledby="appointment-filters-heading">
        <div class="mb-5">
            <h2 id="appointment-filters-heading" class="font-display text-card-title font-semibold text-brand-secondary">Filter Appointments</h2>
            <p class="mt-1 text-sm text-text-secondary">Find a booking by professional, service, plan, or date.</p>
        </div>

        <form action="{{ route('user.all-appointment.index') }}" method="GET" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="grid gap-1.5">
                <label for="search_name" class="text-sm font-semibold text-text-primary">Search</label>
                <div class="relative">
                    <x-tazen-v2.panel.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-text-muted" />
                    <input type="search" name="search_name" id="search_name" value="{{ request('search_name') }}" placeholder="Professional, service or plan"
                        class="min-h-11 w-full rounded-control border border-border bg-surface py-2 pl-10 pr-3 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary">
                </div>
            </div>

            <div class="grid gap-1.5">
                <label for="service" class="text-sm font-semibold text-text-primary">Service</label>
                <select name="service" id="service" class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
                    <option value="all">All services</option>
                    @foreach($serviceOptions as $service)
                        <option value="{{ $service }}" @selected(request('service') == $service)>{{ $service }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-1.5">
                <label for="plan_type" class="text-sm font-semibold text-text-primary">Plan type</label>
                <select name="plan_type" id="plan_type" class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
                    <option value="all">All plans</option>
                    @foreach($planTypeOptions as $planType)
                        <option value="{{ $planType }}" @selected(request('plan_type') == $planType)>
                            {{ customerFormatPlanType($planType) }}
                        </option>
                    @endforeach
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

            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-control border border-brand-primary bg-brand-primary px-4 text-sm font-semibold text-white transition-colors hover:border-brand-800 hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    <x-tazen-v2.panel.icon name="search" class="size-4" />
                    Apply Filters
                </button>
                <a href="{{ route('user.all-appointment.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-panel border border-border bg-surface shadow-card" aria-labelledby="appointment-results-heading">
        <div class="flex flex-col gap-1 border-b border-divider px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="appointment-results-heading" class="font-display text-card-title font-semibold text-brand-secondary">Booking history</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    {{ $bookings->count() }} {{ Str::plural('appointment', $bookings->count()) }}
                    @if(request('service') && request('service') !== 'all')
                        <span>· {{ request('service') }}</span>
                    @endif
                    @if(request('plan_type') && request('plan_type') !== 'all')
                        <span>· {{ customerFormatPlanType(request('plan_type')) }}</span>
                    @endif
                </p>
            </div>
        </div>

        @if($bookings->isEmpty())
            <div class="p-5 sm:p-8">
                <x-tazen-v2.panel.empty-state
                    icon="calendar"
                    :title="$hasActiveFilters ? 'No Matching Appointments' : 'No Appointments Yet'"
                    :description="$hasActiveFilters
                        ? 'Try changing your search criteria or clear filters to view your booking history.'
                        : 'Your consultations and booking history will appear here after you book with a professional.'"
                >
                    <x-slot:actions>
                        @if($hasActiveFilters)
                            <a href="{{ route('user.all-appointment.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary hover:bg-surface-muted">
                                Clear Filters
                            </a>
                        @else
                            <x-tazen-v2.ui.button href="{{ route('gridlisting') }}" size="sm">Explore Professionals</x-tazen-v2.ui.button>
                        @endif
                    </x-slot:actions>
                </x-tazen-v2.panel.empty-state>
            </div>
        @else
            <div class="grid gap-4 p-4 sm:p-5 lg:hidden">
                @foreach($bookings as $key => $booking)
                    @php
                        $totalSessions = $booking->timedates->count();
                        $sessionsTaken = $booking->timedates->where('status', '!=', 'pending')->count();
                        $sessionsRemaining = $totalSessions - $sessionsTaken;
                        $appointmentDate = $booking->timedates->first()?->date;
                        $chatBadgeClass = 'chat-badge hidden ml-1 min-w-5 items-center justify-center rounded-full bg-error px-1.5 py-0.5 text-xs font-bold text-white';
                    @endphp
                    <article class="rounded-card border border-border bg-surface p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-primary">Professional</p>
                                <h3 class="mt-1 break-words font-display text-base font-semibold text-brand-secondary">{{ $booking->professional->name ?? 'No Professional' }}</h3>
                            </div>
                            <span class="shrink-0 text-xs font-medium text-text-muted">#{{ $key + 1 }}</span>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-medium text-text-muted">Booking date</dt>
                                <dd class="mt-1 font-medium text-text-primary">{{ $appointmentDate ? \Carbon\Carbon::parse($appointmentDate)->format('M d, Y') : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-text-muted">Service</dt>
                                <dd class="mt-1 break-words font-medium text-text-primary">{{ $booking->service_name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-text-muted">Sub-service</dt>
                                <dd class="mt-1 break-words text-text-primary">{{ $booking->sub_service_name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-text-muted">Plan</dt>
                                <dd class="mt-1 text-text-primary">{{ customerFormatPlanType($booking->plan_type) ?: 'No Plan' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-text-muted">Sessions taken</dt>
                                <dd class="mt-1 font-medium text-text-primary">{{ $sessionsTaken }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-text-muted">Sessions remaining</dt>
                                <dd class="mt-1 font-medium text-text-primary">{{ $sessionsRemaining }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-medium text-text-muted">Documents</dt>
                                <dd class="mt-1">
                                    @if($booking->professional_documents)
                                        <a href="{{ asset('storage/' . $booking->professional_documents) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-9 items-center gap-2 rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-secondary hover:bg-surface-muted">
                                            <svg class="size-4 text-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6l4.5 4.5v12H7.5a1.5 1.5 0 0 1-1.5-1.5v-13.5a1.5 1.5 0 0 1 1.5-1.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v4.5H18M9 13.5h6m-6 3h6"/></svg>
                                            View document
                                        </a>
                                    @else
                                        <span class="text-sm text-text-muted">No Documents</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-divider pt-4">
                            <a href="{{ route('user.chat.open', $booking->id) }}" target="_blank" rel="noopener noreferrer" title="Chat with Professional" data-booking-id="{{ $booking->id }}"
                                class="inline-flex min-h-10 items-center justify-center rounded-control border border-brand-200 bg-brand-50 px-3 text-sm font-semibold text-brand-primary hover:bg-brand-100">
                                Chat
                                <span id="chat-badge-mobile-{{ $booking->id }}" class="{{ $chatBadgeClass }}" data-chat-badge="{{ $booking->id }}" hidden></span>
                            </a>
                            <button type="button" class="view-details-btn inline-flex min-h-10 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white hover:bg-brand-800" data-id="{{ $booking->id }}">
                                View Details
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[1700px] text-left text-sm">
                    <thead class="bg-surface-muted text-xs uppercase tracking-wide text-text-secondary">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">S.No</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Booking date</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Professional Name</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Service Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Sub-Service</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Plan Type</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Sessions Taken</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Sessions Remaining</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Documents</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Chat</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-divider text-text-primary">
                        @foreach($bookings as $key => $booking)
                            @php
                                $totalSessions = $booking->timedates->count();
                                $sessionsTaken = $booking->timedates->where('status', '!=', 'pending')->count();
                                $sessionsRemaining = $totalSessions - $sessionsTaken;
                                $appointmentDate = $booking->timedates->first()?->date;
                                $chatBadgeClass = 'chat-badge hidden ml-1 min-w-5 items-center justify-center rounded-full bg-error px-1.5 py-0.5 text-xs font-bold text-white';
                            @endphp
                            <tr class="transition-colors hover:bg-surface-muted/70">
                                <td class="whitespace-nowrap px-4 py-4 text-text-secondary">{{ $key + 1 }}</td>
                                <td class="whitespace-nowrap px-4 py-4 font-medium">{{ $appointmentDate ? \Carbon\Carbon::parse($appointmentDate)->format('M d, Y') : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold">{{ $booking->professional->name ?? 'No Professional' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $booking->service_name ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $booking->sub_service_name ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <span class="inline-flex rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-secondary">
                                        {{ customerFormatPlanType($booking->plan_type) ?: 'No Plan' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $sessionsTaken }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $sessionsRemaining }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    @if($booking->professional_documents)
                                        <a href="{{ asset('storage/' . $booking->professional_documents) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-9 items-center gap-2 rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-secondary hover:bg-surface-muted">
                                            <svg class="size-4 text-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6l4.5 4.5v12H7.5a1.5 1.5 0 0 1-1.5-1.5v-13.5a1.5 1.5 0 0 1 1.5-1.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v4.5H18M9 13.5h6m-6 3h6"/></svg>
                                            View document
                                        </a>
                                    @else
                                        <span class="text-text-muted">No Documents</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <a href="{{ route('user.chat.open', $booking->id) }}" target="_blank" rel="noopener noreferrer" title="Chat with Professional" data-booking-id="{{ $booking->id }}"
                                        class="inline-flex min-h-9 items-center justify-center rounded-control border border-brand-200 bg-brand-50 px-3 text-sm font-semibold text-brand-primary hover:bg-brand-100">
                                        Chat
                                        <span id="chat-badge-desktop-{{ $booking->id }}" class="{{ $chatBadgeClass }}" data-chat-badge="{{ $booking->id }}" hidden></span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <button type="button" class="view-details-btn inline-flex min-h-9 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white hover:bg-brand-800" data-id="{{ $booking->id }}">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <dialog id="appointmentDetailsDialog" aria-labelledby="appointment-modal-title" aria-describedby="appointment-modal-description" class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-panel border border-border bg-surface p-0 text-text-primary shadow-popover backdrop:bg-brand-950/50">
        <div class="flex items-start justify-between gap-4 border-b border-divider px-5 py-4 sm:px-6">
            <div>
                <h2 id="appointment-modal-title" class="font-display text-card-title font-semibold text-brand-secondary">Appointment Details</h2>
                <p id="appointment-modal-description" class="mt-1 text-sm text-text-secondary">Dates, time slots and booking notes.</p>
            </div>
            <button type="button" id="closeAppointmentDetails" class="inline-flex size-10 shrink-0 items-center justify-center rounded-control text-text-secondary transition-colors hover:bg-surface-muted hover:text-brand-secondary focus-visible:outline-2 focus-visible:outline-focus-ring" aria-label="Close appointment details">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </div>
        <div id="appointmentDetailsContent" class="max-h-[calc(100dvh-10rem)] overflow-y-auto p-4 sm:p-6" aria-live="polite">
            <p id="appointmentDetailsLoading" class="py-10 text-center text-sm text-text-secondary" role="status">Loading appointment details…</p>
            <p id="appointmentDetailsError" class="hidden rounded-control border border-error bg-error-surface px-4 py-3 text-sm text-error" role="alert"></p>
            <div id="appointmentDetailsList" class="hidden space-y-3"></div>
            <p id="appointmentDetailsEmpty" class="hidden py-8 text-center text-sm text-text-secondary">No appointment details are available.</p>
        </div>
        <div class="flex justify-end border-t border-divider px-5 py-3 sm:px-6">
            <button type="button" id="closeAppointmentDetailsFooter" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-focus-ring">Close</button>
        </div>
    </dialog>
</div>
@endsection
@section('scripts')
<script>
(() => {
    const dialog = document.getElementById('appointmentDetailsDialog');
    const list = document.getElementById('appointmentDetailsList');
    const loading = document.getElementById('appointmentDetailsLoading');
    const error = document.getElementById('appointmentDetailsError');
    const empty = document.getElementById('appointmentDetailsEmpty');
    let lastTrigger = null;

    const resetDialog = () => {
        list.replaceChildren();
        list.classList.add('hidden');
        empty.classList.add('hidden');
        error.classList.add('hidden');
        error.textContent = '';
        loading.classList.remove('hidden');
    };

    const statusClasses = (status) => {
        switch (String(status).toLowerCase()) {
            case 'completed':
                return 'border-success bg-success-surface text-success';
            case 'pending':
                return 'border-warning bg-warning-surface text-warning';
            case 'cancelled':
                return 'border-error bg-error-surface text-error';
            default:
                return 'border-info bg-info-surface text-info';
        }
    };

    document.querySelectorAll('.view-details-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            lastTrigger = button;
            resetDialog();
            dialog.showModal();

            try {
                const response = await fetch(`/user/appointments/${encodeURIComponent(button.dataset.id)}/details`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Failed to load appointment details. Please try again.');
                const payload = await response.json();
                const dates = Array.isArray(payload.dates) ? payload.dates : [];

                loading.classList.add('hidden');
                if (!dates.length) {
                    empty.classList.remove('hidden');
                    return;
                }

                dates.forEach((item) => {
                    const card = document.createElement('article');
                    card.className = 'rounded-card border border-border bg-surface p-4 sm:p-5';

                    const top = document.createElement('div');
                    top.className = 'flex flex-wrap items-center justify-between gap-3 border-b border-divider pb-3';

                    const date = document.createElement('h3');
                    date.className = 'font-semibold text-brand-secondary';
                    const parsedDate = new Date(`${item.date}T00:00:00`);
                    date.textContent = Number.isNaN(parsedDate.getTime())
                        ? (item.date || 'Date unavailable')
                        : parsedDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    top.append(date);

                    const status = document.createElement('span');
                    status.className = `inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${statusClasses(item.status || 'Pending')}`;
                    status.textContent = item.status || 'Pending';
                    top.append(status);
                    card.append(top);

                    const slotsHeading = document.createElement('p');
                    slotsHeading.className = 'mt-4 text-xs font-semibold uppercase tracking-wide text-text-muted';
                    slotsHeading.textContent = 'Time slot(s)';
                    card.append(slotsHeading);

                    const slots = document.createElement('div');
                    slots.className = 'mt-2 flex flex-wrap gap-2';
                    const timeSlots = Array.isArray(item.time_slot) ? item.time_slot : [];
                    timeSlots.map((slot) => String(slot).trim()).filter(Boolean).forEach((slot) => {
                        const chip = document.createElement('span');
                        chip.className = 'rounded-control bg-brand-50 px-3 py-1.5 text-sm font-medium text-brand-secondary';
                        chip.textContent = slot;
                        slots.append(chip);
                    });
                    if (!slots.childElementCount) {
                        const noSlots = document.createElement('span');
                        noSlots.className = 'text-sm text-text-muted';
                        noSlots.textContent = 'No time slots available';
                        slots.append(noSlots);
                    }
                    card.append(slots);

                    const remarksLabel = document.createElement('p');
                    remarksLabel.className = 'mt-4 text-xs font-semibold uppercase tracking-wide text-text-muted';
                    remarksLabel.textContent = 'Summary / Remarks';
                    card.append(remarksLabel);

                    const remarks = document.createElement('p');
                    remarks.className = 'mt-1 whitespace-pre-line break-words text-sm leading-6 text-text-primary';
                    remarks.textContent = item.remarks || 'No remarks available';
                    card.append(remarks);

                    list.append(card);
                });
                list.classList.remove('hidden');
            } catch (requestError) {
                loading.classList.add('hidden');
                error.textContent = requestError.message || 'Failed to load appointment details. Please try again.';
                error.classList.remove('hidden');
            }
        });
    });

    ['closeAppointmentDetails', 'closeAppointmentDetailsFooter'].forEach((id) => {
        document.getElementById(id).addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus();
    });
})();
</script>
<script>
// Chat Functionality
function openBookingChat(bookingId) {
    // Prevent opening multiple chats
    if(currentBookingChatId === bookingId && $('#bookingChatModal').is(':visible')) {
        return;
    }
    
    // Clear any existing interval
    if(messageCheckInterval) {
        clearInterval(messageCheckInterval);
        messageCheckInterval = null;
    }
    
    currentBookingChatId = bookingId;
    isPolling = false;
    
    // Initialize or get existing chat
    $.ajax({
        url: '/user/booking-chat/initialize',
        type: 'POST',
        data: {
            booking_id: bookingId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if(response.success) {
                currentChatId = response.chat_id;
                
                // Update modal title with booking context
                const title = `
                    <div>
                        <strong>${response.booking.service_name}</strong><br>
                        <small>
                            Chat with ${response.participant.name} (${response.participant.type})
                        </small>
                    </div>
                `;
                $('#chatModalTitle').html(title);
                $('#bookingChatModal').fadeIn(300);
                
                // Load messages
                loadChatMessages(bookingId);
                
                // Attach send button click handler directly (not delegated)
                $('#sendMessageBtn').off('click').on('click', function() {
                    console.log('Send button clicked');
                    sendChatMessage();
                });
                
                // Attach Enter key handler directly
                $('#chatMessageInput').off('keypress').on('keypress', function(e) {
                    if(e.which === 13) {
                        console.log('Enter key pressed');
                        sendChatMessage();
                    }
                });
                
                // Start polling only if not already polling
                if(!messageCheckInterval && !isPolling) {
                    isPolling = true;
                    messageCheckInterval = setInterval(() => {
                        if($('#bookingChatModal').is(':visible')) {
                            loadChatMessages(bookingId);
                        }
                    }, 3000);
                }
            }
        },
        error: function(xhr) {
            alert('Error opening chat: ' + (xhr.responseJSON?.error || 'Unknown error'));
        }
    });
}

function loadChatMessages(bookingId) {
    $.ajax({
        url: `/user/booking-chat/${bookingId}/messages`,
        type: 'GET',
        success: function(response) {
            if(response.success) {
                displayChatMessages(response.messages);
            }
        },
        error: function(xhr) {
            console.error('Error loading messages:', xhr);
        }
    });
}

function displayChatMessages(messages) {
    const chatContainer = $('#chatMessages');
    const wasScrolledToBottom = chatContainer[0].scrollHeight - chatContainer.scrollTop() <= chatContainer.outerHeight() + 50;
    
    chatContainer.empty();
    
    if(messages.length === 0) {
        chatContainer.append('<div>No messages yet. Start the conversation!</div>');
    } else {
        messages.forEach(msg => {
            const isOwn = msg.sender_type === 'customer';
            const messageClass = isOwn ? 'message-own' : 'message-other';
            
            // Get sender name - fallback to type if name not available
            let senderName = msg.sender_name || 'Unknown';
            if (!msg.sender_name) {
                senderName = isOwn ? 'You' : (msg.sender_type === 'professional' ? 'Professional' : msg.sender_type);
            } else if (isOwn) {
                senderName = 'You';
            }
            
            const time = msg.formatted_time || new Date(msg.created_at).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'});
            
            let messageContent = '';
            
            // Handle different message types
            if(msg.message_type === 'image' && msg.file_path) {
                messageContent = `
                    <img src="/storage/${msg.file_path}" alt="Image" onclick="window.open('/storage/${msg.file_path}', '_blank')">
                    ${msg.message ? '<div>' + msg.message + '</div>' : ''}
                `;
            } else if(msg.message_type === 'file' && msg.file_path) {
                const fileName = msg.file_path.split('/').pop();
                messageContent = `
                    <a href="/storage/${msg.file_path}" target="_blank">
                        <i class="fas fa-file"></i> ${fileName}
                    </a>
                    ${msg.message ? '<div>' + msg.message + '</div>' : ''}
                `;
            } else {
                messageContent = msg.message;
            }
            
            chatContainer.append(`
                <div class="chat-message ${messageClass}">
                    <div class="message-sender">${senderName}</div>
                    <div class="message-content">${messageContent}</div>
                    <div class="message-time">${time}</div>
                </div>
            `);
        });
    }
    
    // Auto scroll to bottom
    if(wasScrolledToBottom) {
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }
}

function sendChatMessage() {
    const messageInput = $('#chatMessageInput');
    const fileInput = $('#chatFileInput')[0];
    
    console.log('sendChatMessage called');
    console.log('messageInput element:', messageInput);
    console.log('messageInput length:', messageInput.length);
    console.log('messageInput value:', messageInput.val());
    
    const message = messageInput.val()?.trim() || '';
    const file = fileInput?.files[0];
    
    console.log('After processing - message:', message);
    console.log('After processing - message length:', message.length);
    console.log('After processing - file:', file);
    
    console.log('Sending message:', { message: message, hasFile: !!file });
    
    if(!message && !file) {
        console.log('No message or file to send');
        return;
    }
    
    const formData = new FormData();
    if(message) formData.append('message', message);
    if(file) formData.append('file', file);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    
    console.log('FormData contents:', {
        hasMessage: formData.has('message'),
        hasFile: formData.has('file'),
        hasToken: formData.has('_token')
    });
    
    // Disable send button
    $('#sendMessageBtn').prop('disabled', true);
    
    $.ajax({
        url: `/user/booking-chat/${currentBookingChatId}/send`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Message sent successfully:', response);
            if(response.success) {
                messageInput.val('');
                if(fileInput) fileInput.value = '';
                $('#selectedFileName').text('');
                loadChatMessages(currentBookingChatId);
            } else {
                console.error('Response success was false:', response);
                alert('Failed to send message: ' + (response.error || 'Unknown error'));
            }
        },
        error: function(xhr) {
            console.error('Error sending message:', xhr);
            const errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Unknown error';
            const details = xhr.responseJSON?.details ? JSON.stringify(xhr.responseJSON.details) : '';
            alert('Error sending message: ' + errorMsg + (details ? '\n' + details : ''));
        },
        complete: function() {
            $('#sendMessageBtn').prop('disabled', false);
        }
    });
}

// Show selected file name
$(document).on('change', '#chatFileInput', function() {
    const fileName = this.files[0]?.name || '';
    $('#selectedFileName').text(fileName ? `Attached: ${fileName}` : '');
});

// Close modal and stop polling - Use OFF to prevent multiple bindings
$(document).off('click', '#closeChatModal').on('click', '#closeChatModal', function() {
    $('#bookingChatModal').fadeOut(300);
    if(messageCheckInterval) {
        clearInterval(messageCheckInterval);
        messageCheckInterval = null;
        isPolling = false;
    }
    currentBookingChatId = null;
    currentChatId = null;
});

// Close on outside click
$(window).off('click.chatModal').on('click.chatModal', function (e) {
    if ($(e.target).is('#bookingChatModal')) {
        $('#closeChatModal').trigger('click');
    }
});

// Load unread chat counts for all bookings
function loadUnreadChatCounts() {
    const bookingIds = [...new Set($('.chat-badge[data-chat-badge]').map(function() {
        return $(this).data('chat-badge');
    }).get())];

    bookingIds.forEach(bookingId => {
        $.ajax({
            url: `/user/chat/booking/${bookingId}/unread-count`,
            method: 'GET',
            success: function(response) {
                const badgeElements = $(`.chat-badge[data-chat-badge="${bookingId}"]`);
                if(response.success && response.unread_count > 0) {
                    badgeElements.text(response.unread_count).removeAttr('hidden').removeClass('hidden').addClass('inline-flex');
                } else {
                    badgeElements.text('').attr('hidden', true).removeClass('inline-flex').addClass('hidden');
                }
            },
            error: function() {
                // Silently fail
            }
        });
    });
}

// Load counts on page load
$(document).ready(function() {
    loadUnreadChatCounts();
    
    // Refresh counts every 30 seconds
    setInterval(loadUnreadChatCounts, 30000);
});

</script>

@endsection
