@extends('professional.layout.layout')

@section('title', 'Availability | YuktiOne')

@section('content')
<div class="availability-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="availability-shell mx-auto w-full space-y-5">
        <header class="availability-header flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Availability</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="ri-calendar-line" aria-hidden="true"></i>Availability</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">My Availability</h1>
                <p class="mt-1 text-sm text-text-secondary">Review monthly schedules and keep your booking windows up to date.</p>
            </div>
            <a href="{{ route('professional.availability.create') }}" class="btn-pill btn-pill--primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                <i class="ri-add-line" aria-hidden="true"></i>
                Add New Availability
            </a>
        </header>

        <section class="filter-card rounded-panel border border-border bg-white p-5 shadow-card">
            <form method="GET" action="{{ route('professional.availability.index') }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <div class="space-y-2">
                    <label for="search_month" class="block text-sm font-medium text-text-secondary">Search by Month</label>
                    <select name="search_month" id="search_month" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <option value="">All Months</option>
                        @foreach($availableMonths as $month)
                            <option value="{{ $month }}" {{ request('search_month') == $month ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-outline-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-brand-600 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">
                        <i class="ri-search-line" aria-hidden="true"></i>Search
                    </button>
                    @if(request('search_month'))
                        <a href="{{ route('professional.availability.index') }}" class="btn btn-outline-secondary inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted">
                            <i class="ri-refresh-line" aria-hidden="true"></i>Clear
                        </a>
                    @endif
                </div>
            </form>
        </section>

        @if($availability->count() > 0)
            <section class="availability-grid grid gap-5 lg:grid-cols-2">
                @foreach($availability as $avail)
                    <article class="availability-card overflow-hidden rounded-panel border border-border bg-white shadow-card">
                        <div class="card-top border-b border-divider bg-surface-muted p-5">
                            <div class="card-meta flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h2 class="month-title text-base font-semibold text-brand-950">{{ \Carbon\Carbon::parse($avail->month . '-01')->format('F Y') }}</h2>
                                    <p class="month-info mt-1 text-sm text-text-secondary">Session Duration: {{ $avail->session_duration }} minutes</p>
                                </div>
                                <div class="card-actions flex flex-wrap gap-2">
                                    <a href="{{ route('professional.availability.edit', $avail->id) }}"
                                       class="btn btn-outline-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-brand-600 bg-white px-4 py-2 text-sm font-semibold text-brand-800 transition hover:bg-brand-50" title="Edit Availability">
                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                        <span class="label-text">Edit</span>
                                    </a>
                                    <button type="button"
                                            class="btn btn-outline-danger delete-availability inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-error/40 bg-white px-4 py-2 text-sm font-semibold text-error transition hover:bg-error-surface"
                                            data-id="{{ $avail->id }}"
                                            data-month="{{ \Carbon\Carbon::parse($avail->month . '-01')->format('F Y') }}"
                                            title="Delete Availability">
                                        <i class="ri-delete-bin-line" aria-hidden="true"></i>
                                        <span class="label-text">Delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-5">
                            @if($avail->slots && $avail->slots->count() > 0)
                                <div class="weekly-block space-y-4">
                                    <div class="weekly-title flex items-center justify-between text-sm font-semibold text-brand-950">
                                        <span>Weekly Schedule</span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">
                                            <i class="ri-calendar-check-line" aria-hidden="true"></i>
                                            {{ $avail->slots->count() }} {{ $avail->slots->count() === 1 ? 'slot' : 'slots' }}
                                        </span>
                                    </div>
                                    @php
                                        $weekdays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                                        $dayNames = [
                                            'mon' => 'Monday',
                                            'tue' => 'Tuesday',
                                            'wed' => 'Wednesday',
                                            'thu' => 'Thursday',
                                            'fri' => 'Friday',
                                            'sat' => 'Saturday',
                                            'sun' => 'Sunday'
                                        ];
                                        $slotsByDay = $avail->slots->groupBy('weekday');
                                    @endphp

                                    <div class="day-schedule space-y-3">
                                        @foreach($weekdays as $day)
                                            @if(isset($slotsByDay[$day]) && $slotsByDay[$day]->count() > 0)
                                                <div class="day-entry grid gap-2 border-b border-divider pb-3 last:border-0 sm:grid-cols-[8rem_1fr]">
                                                    <div class="day-name text-sm font-medium text-text-secondary">{{ $dayNames[$day] }}</div>
                                                    <div class="time-pill-group flex flex-wrap gap-2">
                                                        @foreach($slotsByDay[$day] as $slot)
                                                            <span class="time-pill rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">
                                                                {{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} –
                                                                {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <p class="no-slots rounded-control border border-dashed border-border bg-surface-muted p-4 text-sm text-text-muted">No time slots configured for this month.</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <section class="empty-state rounded-panel border border-dashed border-border bg-white p-8 text-center sm:p-12">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                    <i class="ri-calendar-line text-xl" aria-hidden="true"></i>
                </div>
                <h4 class="text-base font-semibold text-brand-950">No Availability Set</h4>
                <p class="mx-auto mt-2 max-w-md text-sm text-text-secondary">You haven't configured your availability yet. Set up your schedule to start receiving bookings.</p>
                <a href="{{ route('professional.availability.create') }}" class="btn-pill btn-pill--primary mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    <span>Add Your First Availability</span>
                </a>
            </section>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-availability').forEach(button => {
        button.addEventListener('click', function() {
            const availabilityId = this.getAttribute('data-id');
            const monthName = this.getAttribute('data-month');
            const card = this.closest('.availability-card');

            const runDelete = () => {
                fetch(`{{ route('professional.availability.index') }}/${availabilityId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (card) card.remove();
                        if (window.Swal) {
                            Swal.fire('Deleted!', data.message || 'Availability deleted successfully!', 'success');
                        } else {
                            alert('Availability deleted successfully!');
                        }
                        if (document.querySelectorAll('.availability-card').length === 0) {
                            window.location.reload();
                        }
                    } else {
                        if (window.Swal) {
                            Swal.fire('Error!', data.message || 'Failed to delete availability', 'error');
                        } else {
                            alert('Error: ' + (data.message || 'Failed to delete availability'));
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (window.Swal) {
                        Swal.fire('Error!', 'An error occurred while deleting the availability', 'error');
                    } else {
                        alert('An error occurred while deleting the availability');
                    }
                });
            };

            if (window.Swal) {
                Swal.fire({
                    title: 'Delete availability?',
                    text: `Are you sure you want to delete availability for ${monthName}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#C85B65',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) runDelete();
                });
            } else if (confirm(`Are you sure you want to delete availability for ${monthName}? This action cannot be undone.`)) {
                runDelete();
            }
        });
    });
});
</script>
@endsection
