@extends('professional.layout.layout')

@section('title', 'Edit Rate | YuktiOne')

@section('content')
@php
    $isServiceRate = !$rates->sub_service_id;
    $isSubServiceRate = $rates->sub_service_id;
    $currentSubService = null;
    if ($isSubServiceRate && $professionalService && $professionalService->subServices) {
        $currentSubService = $professionalService->subServices->where('id', $rates->sub_service_id)->first();
    }
@endphp

<div class="rates-edit-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="rates-shell mx-auto w-full space-y-5">
        <header class="rates-header flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="header-info min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.rate.index') }}">Rates</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Edit Rate</li>
                    </ol>
                </nav>
                <span class="pretitle text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">Rates & Packages</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Edit Rate</h1>
                <p class="mt-1 text-sm text-text-secondary">Fine-tune your pricing details to keep your services competitive and clear for clients.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('professional.rate.index') }}" class="header-btn btn-pill btn-pill--outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Back to Rates
                </a>
            </div>
        </header>

        <section class="rates-card overflow-hidden rounded-panel border border-border bg-white shadow-card">
            <div class="rates-card__head border-b border-divider bg-surface-muted p-5 sm:p-7">
                <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Update Rate Information</h2>
                <p class="subtitle mt-1 text-sm text-text-secondary">
                    @if($professionalService)
                        Editing <strong class="text-text-primary">{{ $professionalService->service_name }}</strong>
                        @if($isSubServiceRate && $currentSubService)
                            <span class="dot-divider mx-1 text-border" aria-hidden="true">·</span>
                            <span>Sub-service: <strong class="text-text-primary">{{ $currentSubService->name }}</strong></span>
                        @endif
                    @else
                        Configure your rate details below.
                    @endif
                </p>
            </div>

            <div class="rates-card__body space-y-6 p-5 sm:p-7">
                <form id="rateForm" action="{{ route('professional.rate.update', $rates->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="professional_service_id" value="{{ $professionalService ? $professionalService->id : '' }}">
                    @if($isSubServiceRate)
                        <input type="hidden" name="sub_service_id" value="{{ $rates->sub_service_id }}">
                    @endif

                    @if($professionalService)
                        @if($isServiceRate)
                            <div class="rate-section service-section space-y-4 rounded-panel border border-border bg-surface-muted p-4 sm:p-5">
                                <div class="section-heading flex flex-col gap-3 border-b border-divider pb-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-brand-950">{{ $professionalService->service_name }}</h3>
                                        @if($professionalService->service_type)
                                            <small class="text-sm text-text-muted">{{ $professionalService->service_type }}</small>
                                        @endif
                                    </div>
                                    <span class="section-badge inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">Service level</span>
                                </div>

                                <div class="table-shell overflow-hidden rounded-control border border-border bg-white">
                                    <div class="table-responsive w-full overflow-x-auto">
                                        <table class="styled-table w-full min-w-[42rem] border-collapse text-left text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Session Type</th>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">No. of Sessions</th>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Rate Per Session (₹)</th>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Final Rate (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td data-label="Session Type" class="border-b border-divider px-4 py-3 align-top">
                                                        <select class="form-control session-type w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="session_type">
                                                            <option value="One Time" {{ $rates->session_type == 'One Time' ? 'selected' : '' }}>One Time</option>
                                                            <option value="Monthly" {{ $rates->session_type == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                            <option value="Quarterly" {{ $rates->session_type == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                            <option value="Free Hand" {{ $rates->session_type == 'Free Hand' ? 'selected' : '' }}>Free Hand</option>
                                                        </select>
                                                    </td>
                                                    <td data-label="No. of Sessions" class="border-b border-divider px-4 py-3 align-top">
                                                        <input type="number" class="form-control num-sessions w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="num_sessions" value="{{ $rates->num_sessions }}" min="1">
                                                    </td>
                                                    <td data-label="Rate Per Session (₹)" class="border-b border-divider px-4 py-3 align-top">
                                                        <input type="number" class="form-control rate-per-session w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="rate_per_session" value="{{ $rates->rate_per_session }}" min="0" step="100">
                                                    </td>
                                                    <td data-label="Final Rate (₹)" class="border-b border-divider px-4 py-3 align-top">
                                                        <input type="number" class="form-control final-rate w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="final_rate" value="{{ $rates->final_rate }}" readonly>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rate-section sub-service-section space-y-4 rounded-panel border border-border bg-surface-muted p-4 sm:p-5">
                                <div class="section-heading flex flex-col gap-3 border-b border-divider pb-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-brand-950">{{ $professionalService->service_name }}</h3>
                                        @if($professionalService->service_type)
                                            <small class="text-sm text-text-muted">{{ $professionalService->service_type }}</small>
                                        @endif
                                    </div>
                                    <span class="section-badge inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">Sub-service level</span>
                                </div>

                                <div class="sub-service-banner rounded-control border border-info/20 bg-info-surface px-4 py-3">
                                    <div class="sub-service-title">
                                        <span class="label text-xs font-semibold uppercase tracking-wide text-info">Sub-service</span>
                                        <h4 class="mt-1 text-sm font-semibold text-brand-950">{{ $currentSubService->name ?? 'Sub-Service' }}</h4>
                                    </div>
                                </div>

                                <div class="table-shell overflow-hidden rounded-control border border-border bg-white">
                                    <div class="table-responsive w-full overflow-x-auto">
                                        <table class="styled-table w-full min-w-[42rem] border-collapse text-left text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Session Type</th>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">No. of Sessions</th>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Rate Per Session (₹)</th>
                                                    <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Final Rate (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td data-label="Session Type" class="border-b border-divider px-4 py-3 align-top">
                                                        <select class="form-control session-type w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="session_type">
                                                            <option value="One Time" {{ $rates->session_type == 'One Time' ? 'selected' : '' }}>One Time</option>
                                                            <option value="Monthly" {{ $rates->session_type == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                            <option value="Quarterly" {{ $rates->session_type == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                            <option value="Free Hand" {{ $rates->session_type == 'Free Hand' ? 'selected' : '' }}>Free Hand</option>
                                                        </select>
                                                    </td>
                                                    <td data-label="No. of Sessions" class="border-b border-divider px-4 py-3 align-top">
                                                        <input type="number" class="form-control num-sessions w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="num_sessions" value="{{ $rates->num_sessions }}" min="1">
                                                    </td>
                                                    <td data-label="Rate Per Session (₹)" class="border-b border-divider px-4 py-3 align-top">
                                                        <input type="number" class="form-control rate-per-session w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="rate_per_session" value="{{ $rates->rate_per_session }}" min="0" step="100">
                                                    </td>
                                                    <td data-label="Final Rate (₹)" class="border-b border-divider px-4 py-3 align-top">
                                                        <input type="number" class="form-control final-rate w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="final_rate" value="{{ $rates->final_rate }}" readonly>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="features-section space-y-4 rounded-panel border border-border bg-surface-muted p-4 sm:p-5">
                            <div>
                                <h5 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="fas fa-list-ul text-brand-primary" aria-hidden="true"></i> Package Features</h5>
                                <p class="helper-text mt-1 text-sm text-text-secondary">Highlight what this package includes to help customers choose confidently.</p>
                            </div>
                            <div id="featuresContainer" class="space-y-3">
                                @if($rates->features && count($rates->features) > 0)
                                    @foreach($rates->features as $feature)
                                        @if($feature)
                                            <div class="feature-item flex items-center gap-2">
                                                <input type="text" name="features[]" class="form-control feature-input w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="Enter feature" value="{{ $feature }}">
                                                <button type="button" class="remove-feature inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control border border-border bg-white text-lg text-text-secondary transition hover:border-error/30 hover:bg-error-surface hover:text-error" onclick="removeFeature(this)" aria-label="Remove feature">×</button>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="feature-item flex items-center gap-2">
                                        <input type="text" name="features[]" class="form-control feature-input w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="Enter feature">
                                        <button type="button" class="remove-feature inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control border border-border bg-white text-lg text-text-secondary transition hover:border-error/30 hover:bg-error-surface hover:text-error" onclick="removeFeature(this)" aria-label="Remove feature">×</button>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="add-feature inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800" onclick="addFeature()">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                Add Feature
                            </button>
                        </div>

                        <div class="form-footer flex flex-col-reverse gap-3 border-t border-divider pt-5 sm:flex-row sm:justify-end">
                            <a href="{{ route('professional.rate.index') }}" class="back-link inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Back to Rates
                            </a>
                            <button type="submit" class="btn primary-btn inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-300">
                                <i class="fas fa-save" aria-hidden="true"></i>
                                Update Rate
                            </button>
                        </div>
                    @else
                        <div class="empty-state rounded-panel border border-dashed border-border bg-white p-8 text-center sm:p-10">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-warning-surface text-warning">
                                <i class="fas fa-exclamation-triangle text-xl" aria-hidden="true"></i>
                            </div>
                            <strong class="block text-base font-semibold text-brand-950">You don't have any services set up yet.</strong>
                            <p class="mx-auto mt-2 max-w-md text-sm text-text-secondary">Please set up your services first to manage rates.</p>
                            <a href="{{ route('professional.service.create') }}" class="btn-pill btn-pill--primary mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                                Create Service
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </section>
    </div>
</div>


@endsection

@section('scripts')
<script>
    // Feature management functions
    function addFeature() {
        const container = document.getElementById('featuresContainer');
        const newFeature = document.createElement('div');
        newFeature.className = 'feature-item flex items-center gap-2';
        newFeature.innerHTML = `
            <input type="text" name="features[]" class="form-control feature-input w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="Enter feature">
            <button type="button" class="remove-feature inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control border border-border bg-white text-lg text-text-secondary transition hover:border-error/30 hover:bg-error-surface hover:text-error" onclick="removeFeature(this)" aria-label="Remove feature">×</button>
        `;
        container.appendChild(newFeature);
    }

    function removeFeature(button) {
        const featureItem = button.closest('.feature-item');
        const container = featureItem.closest('#featuresContainer');
        const featureItems = container.querySelectorAll('.feature-item');
        
        // Don't allow removing the last feature input
        if (featureItems.length > 1) {
            featureItem.remove();
        } else {
            // Just clear the input instead of removing it
            featureItem.querySelector('.feature-input').value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const row = document.querySelector('tbody tr');
        if (!row) {
            return;
        }

        const calculateFinalRate = () => {
            const numSessions = parseInt(row.querySelector('.num-sessions').value || 0);
            const ratePerSession = parseInt(row.querySelector('.rate-per-session').value || 0);
            const finalRateInput = row.querySelector('.final-rate');
            finalRateInput.value = numSessions * ratePerSession;
        };

        row.querySelector('.num-sessions').addEventListener('input', calculateFinalRate);
        row.querySelector('.rate-per-session').addEventListener('input', calculateFinalRate);

        calculateFinalRate();
    });
</script>

<script>
    $(document).ready(function () {
        $('#rateForm').submit(function (e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const submitBtn = $(form).find('button[type="submit"]');
            submitBtn.prop('disabled', true);

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function (response) {
                    toastr.success("Rate updated successfully.");
                    setTimeout(() => {
                        window.location.href = "{{ route('professional.rate.index') }}";
                    }, 1500);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            toastr.error(value[0]);
                        });
                    } else {
                        toastr.error("An unexpected error occurred.");
                    }
                },
                complete: function () {
                    submitBtn.prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection
