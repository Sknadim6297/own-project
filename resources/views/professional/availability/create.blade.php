@extends('professional.layout.layout')

@section('title', 'Add Availability | YuktiOne')

@section('content')
<div class="availability-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="availability-shell mx-auto w-full space-y-5">
        <header class="availability-header flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.availability.index') }}">Availability</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Add New</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="ri-calendar-line" aria-hidden="true"></i>Availability</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Add New Availability</h1>
                <p class="mt-1 text-sm text-text-secondary">Choose months, session duration, and weekly time slots clients can book.</p>
            </div>
            <a href="{{ route('professional.availability.index') }}" class="btn-pill btn-pill--outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">
                <i class="ri-arrow-left-line" aria-hidden="true"></i>
                Back to Availability
            </a>
        </header>

        <div id="error-messages" class="space-y-3"></div>

        <form id="availability-form" class="availability-form space-y-5">
            @csrf

            <section class="form-card rounded-panel border border-border bg-white p-5 shadow-card sm:p-7">
                <header class="mb-5 border-b border-divider pb-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-brand-950">
                        <i class="ri-settings-line text-brand-primary" aria-hidden="true"></i>
                        Basic Information
                    </h2>
                    <p class="mt-1 text-sm text-text-secondary">Set how long each session lasts for the schedule below.</p>
                </header>
                <div class="form-grid grid gap-5 md:grid-cols-2">
                    <div class="form-group space-y-2">
                        <label for="session_duration" class="block text-sm font-medium text-text-secondary">Session Duration (in minutes)</label>
                        <select name="session_duration" id="session_duration" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" required>
                            @foreach([30, 45, 60, 90, 120] as $duration)
                                <option value="{{ $duration }}" {{ $duration == 30 ? 'selected' : '' }}>
                                    {{ $duration }} {{ $duration == 120 ? 'minutes (2 hours)' : 'minutes' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="form-card rounded-panel border border-border bg-white p-5 shadow-card sm:p-7">
                <header class="mb-5 border-b border-divider pb-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-brand-950">
                        <i class="ri-calendar-line text-brand-primary" aria-hidden="true"></i>
                        Select Months
                    </h2>
                    <p class="description mt-1 text-sm leading-6 text-text-secondary">Choose the months for which you want to set this availability schedule.</p>
                </header>
                <div class="months-grid grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @php
                        $currentYear = date('Y');
                        $currentMonth = date('n');
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $endDate = strtotime('+6 months');
                        $endYear = date('Y', $endDate);
                        $endMonth = date('n', $endDate);
                    @endphp

                    @for($year = $currentYear; $year <= $endYear; $year++)
                        @foreach($months as $monthNum => $monthName)
                            @php
                                if($year == $currentYear && $monthNum < $currentMonth) {
                                    continue;
                                }
                                if($year == $endYear && $monthNum > $endMonth) {
                                    continue;
                                }
                                if($year > $endYear) {
                                    continue;
                                }
                                $monthValue = sprintf('%04d-%02d', $year, $monthNum);
                            @endphp
                            <div class="month-card flex cursor-pointer items-center gap-3 rounded-control border border-border bg-white p-4 text-sm font-medium text-text-secondary transition hover:border-brand-400 hover:bg-brand-50" onclick="toggleMonth('{{ $monthValue }}')">
                                <input type="checkbox" name="months[]" value="{{ $monthValue }}" id="month_{{ $monthValue }}" class="h-4 w-4 rounded border-border text-brand-600 focus:ring-brand-500">
                                <label for="month_{{ $monthValue }}" class="mb-0 block text-sm font-medium text-text-secondary">
                                    {{ $monthName }} {{ $year }}
                                </label>
                            </div>
                        @endforeach
                    @endfor
                </div>
            </section>

            <section class="form-card schedule-card rounded-panel border border-border bg-white p-5 shadow-card sm:p-7">
                <header class="mb-5 border-b border-divider pb-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-brand-950">
                        <i class="ri-time-line text-brand-primary" aria-hidden="true"></i>
                        Weekly Schedule
                    </h2>
                    <p class="description mt-1 text-sm leading-6 text-text-secondary">Set your available time slots for each day of the week. You can add multiple time slots per day.</p>
                </header>
                <div class="weekly-scheduler grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @php
                        $days = [
                            'mon' => 'Monday',
                            'tue' => 'Tuesday',
                            'wed' => 'Wednesday',
                            'thu' => 'Thursday',
                            'fri' => 'Friday',
                            'sat' => 'Saturday',
                            'sun' => 'Sunday'
                        ];
                    @endphp

                    @foreach($days as $dayCode => $dayName)
                        <div class="day-column space-y-4 rounded-control border border-border bg-surface-muted/40 p-4">
                            <div class="day-header border-b border-divider pb-3 text-sm font-semibold text-brand-950">{{ $dayName }}</div>
                            <div class="day-slots space-y-2" id="slots-{{ $dayCode }}"></div>
                            <div class="add-slot-form space-y-3 rounded-control border border-border bg-white p-3">
                                <div class="time-inputs grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                    <input type="time" id="start-{{ $dayCode }}" onchange="calculateEndTime('{{ $dayCode }}')" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <span class="text-xs text-text-muted">to</span>
                                    <input type="time" id="end-{{ $dayCode }}" readonly class="w-full rounded-control border border-border bg-surface-muted px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                </div>
                                <button type="button" class="add-slot-btn inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-control bg-brand-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-800" onclick="addSlot('{{ $dayCode }}')">
                                    <i class="ri-add-line" aria-hidden="true"></i>
                                    Add Slot
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="form-actions flex flex-col-reverse gap-3 border-t border-divider pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('professional.availability.index') }}" class="btn btn-secondary inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <i class="ri-save-line" aria-hidden="true"></i>Save Availability
                </button>
            </section>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let weeklySlots = {
        mon: [], tue: [], wed: [], thu: [], fri: [], sat: [], sun: []
    };

    window.toggleMonth = function(monthValue) {
        const checkbox = document.getElementById('month_' + monthValue);
        const card = checkbox.closest('.month-card');

        checkbox.checked = !checkbox.checked;
        card.classList.toggle('selected', checkbox.checked);
        card.classList.toggle('border-brand-600', checkbox.checked);
        card.classList.toggle('bg-brand-50', checkbox.checked);
        card.classList.toggle('bg-white', !checkbox.checked);
        card.classList.toggle('border-border', !checkbox.checked);
        card.classList.toggle('ring-2', checkbox.checked);
        card.classList.toggle('ring-brand-200', checkbox.checked);
    };

    window.calculateEndTime = function(day) {
        const startInput = document.getElementById('start-' + day);
        const endInput = document.getElementById('end-' + day);
        const sessionDuration = document.getElementById('session_duration').value;

        if (!startInput.value || !sessionDuration) {
            endInput.value = '';
            return;
        }

        const [hours, minutes] = startInput.value.split(':').map(Number);
        const startDate = new Date();
        startDate.setHours(hours, minutes, 0, 0);

        const endDate = new Date(startDate.getTime() + parseInt(sessionDuration) * 60000);

        const endHours = String(endDate.getHours()).padStart(2, '0');
        const endMinutes = String(endDate.getMinutes()).padStart(2, '0');
        endInput.value = `${endHours}:${endMinutes}`;
    };

    document.getElementById('session_duration').addEventListener('change', function() {
        const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        days.forEach(day => {
            const startInput = document.getElementById('start-' + day);
            if (startInput.value) {
                calculateEndTime(day);
            }
        });
    });

    window.addSlot = function(day) {
        const startInput = document.getElementById('start-' + day);
        const endInput = document.getElementById('end-' + day);

        const startTime = startInput.value;
        const endTime = endInput.value;

        if (!startTime || !endTime) {
            alert('Please select both start and end times');
            return;
        }

        if (startTime >= endTime) {
            alert('Start time must be before end time');
            return;
        }

        weeklySlots[day].push({
            start_time: startTime,
            end_time: endTime
        });

        renderDaySlots(day);

        startInput.value = '';
        endInput.value = '';
    };

    window.deleteSlot = function(day, index) {
        weeklySlots[day].splice(index, 1);
        renderDaySlots(day);
    };

    function renderDaySlots(day) {
        const container = document.getElementById('slots-' + day);
        container.innerHTML = '';

        weeklySlots[day].forEach((slot, index) => {
            const slotDiv = document.createElement('div');
            slotDiv.className = 'slot-item flex items-center justify-between gap-3 rounded-control border border-border bg-white px-3 py-2';
            slotDiv.innerHTML = `
                <span class="slot-time text-sm font-medium text-text-primary">${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</span>
                <button type="button" class="delete-slot inline-flex h-8 w-8 items-center justify-center rounded-full text-error transition hover:bg-error-surface" onclick="deleteSlot('${day}', ${index})" aria-label="Remove slot">
                    <i class="ri-delete-bin-line" aria-hidden="true"></i>
                </button>
            `;
            container.appendChild(slotDiv);
        });
    }

    function formatTime(time) {
        return new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    document.getElementById('availability-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const selectedMonths = document.querySelectorAll('input[name="months[]"]:checked');
        if (selectedMonths.length === 0) {
            showError('Please select at least one month');
            return;
        }

        const totalSlots = Object.values(weeklySlots).reduce((total, daySlots) => total + daySlots.length, 0);
        if (totalSlots === 0) {
            showError('Please add at least one time slot');
            return;
        }

        const formData = new FormData(this);

        const slotsArray = [];
        Object.keys(weeklySlots).forEach(day => {
            weeklySlots[day].forEach(slot => {
                slotsArray.push({
                    weekday: day,
                    start_time: slot.start_time,
                    end_time: slot.end_time
                });
            });
        });

        formData.append('weekly_slots', JSON.stringify(slotsArray));

        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton.disabled) {
            return;
        }
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Saving...';

        fetch('{{ route("professional.availability.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("professional.availability.index") }}';
                }, 1500);
            } else {
                if (data.errors) {
                    const errors = Array.isArray(data.errors)
                        ? data.errors
                        : Object.values(data.errors).flat();
                    showError(errors.join(' '));
                } else {
                    showError(data.message || 'An error occurred');
                }
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.dataset.originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while saving availability');
            submitButton.disabled = false;
            submitButton.innerHTML = submitButton.dataset.originalText;
        });
    });

    function showError(message) {
        const errorContainer = document.getElementById('error-messages');
        const alert = document.createElement('div');
        alert.className = 'rounded-control border border-error/20 bg-error-surface p-4 text-sm text-error';
        alert.setAttribute('role', 'alert');
        alert.textContent = message;
        errorContainer.replaceChildren(alert);
        errorContainer.scrollIntoView({ behavior: 'smooth' });
    }

    function showSuccess(message) {
        const errorContainer = document.getElementById('error-messages');
        errorContainer.innerHTML = `
            <div class="rounded-control border border-success/20 bg-success-surface p-4 text-sm text-success" role="alert">
                ${message}
            </div>
        `;
        errorContainer.scrollIntoView({ behavior: 'smooth' });
    }
});
</script>
@endsection
