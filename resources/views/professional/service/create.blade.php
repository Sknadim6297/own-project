@extends('professional.layout.layout')

@section('title', 'Add Service | YuktiOne')

@section('content')
<div class="service-create-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="service-create-shell mx-auto w-full space-y-5">
        <section class="service-create-hero flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.service.index') }}">Services</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Add Service</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="fas fa-plus-circle" aria-hidden="true"></i>Add Service</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Add Service</h1>
                <p class="mt-1 text-sm text-text-secondary">Create the service offering clients will see when booking with you.</p>
            </div>
            <a href="{{ route('professional.service.index') }}" class="btn-pill btn-pill--outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Back to Services
            </a>
        </section>

        <section class="form-card rounded-panel border border-border bg-white p-5 shadow-card sm:p-7">
            <form id="serviceForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="serviceDuration" value="60">

                <div class="form-section space-y-5 border-b border-divider pb-6">
                    <header>
                        <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Service Details</h2>
                        <p class="mt-1 text-sm text-text-secondary">Your category is limited to the specialization on your registration.</p>
                    </header>
                    <div class="form-grid grid gap-5 md:grid-cols-2">
                        <div class="form-group space-y-2 md:col-span-2 lg:col-span-1">
                            <label for="serviceCategory" class="block text-sm font-medium text-text-secondary">
                                Service Category *
                                <span class="ml-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-800">Based on Your Registration</span>
                            </label>
                            <select name="serviceId" id="serviceCategory" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" required>
                                <option value="">Select Category</option>
                                @forelse($services as $service)
                                    <option value="{{ $service->id }}" {{ (isset($matchingServiceId) && $matchingServiceId == $service->id) ? 'selected' : '' }}>{{ $service->name }}</option>
                                @empty
                                    <option value="" disabled>No service categories available for your specialization</option>
                                @endforelse
                            </select>
                            <input type="hidden" name="serviceIdBackup" value="{{ $matchingServiceId ?? '' }}">
                            <span class="muted-note mt-2 block text-sm text-text-muted"><strong>Note:</strong> You can only offer services in your registered specialization: {{ $specialization ?? 'Not specified' }}. If you need to change your specialization, please contact support.</span>
                        </div>
                    </div>
                </div>

                <div class="form-section space-y-5 border-b border-divider py-6">
                    <header>
                        <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Sub-Services</h2>
                        <p class="mt-1 text-sm text-text-secondary">Optionally select up to two sub-services within your category.</p>
                    </header>
                    <div class="form-group space-y-2">
                        <label for="subServices" class="block text-sm font-medium text-text-secondary">Sub-Services (Optional)</label>
                        <div id="subServiceContainer" class="sub-service-container grid gap-3 sm:grid-cols-2">
                            <p class="text-muted rounded-control border border-dashed border-border bg-surface-muted px-4 py-5 text-sm text-text-muted">Please select a service category first to see available sub-services.</p>
                        </div>
                        <span class="muted-note mt-2 block text-sm text-text-muted">Select the specific sub-services you offer within your service category.</span>
                    </div>
                </div>

                <div class="form-section space-y-5 pt-6">
                    <header>
                        <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Additional Information</h2>
                        <p class="mt-1 text-sm text-text-secondary">Help clients understand what to expect before booking.</p>
                    </header>
                    <div class="form-grid grid gap-5 md:grid-cols-2">
                        <div class="form-group space-y-2">
                            <label class="block text-sm font-medium text-text-secondary">Service Features</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item flex items-center gap-3 rounded-control border border-border bg-surface-muted px-3 py-3 text-sm font-medium text-text-secondary">
                                    <input type="checkbox" name="features[]" value="online" checked class="h-4 w-4 rounded border-border text-brand-600 focus:ring-brand-500"> Online Sessions
                                </label>
                            </div>
                        </div>
                        <div class="form-group space-y-2">
                            <label for="serviceTags" class="block text-sm font-medium text-text-secondary">Tags</label>
                            <input type="text" name="serviceTags" id="serviceTags" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="Add tags separated by commas">
                            <span class="muted-note mt-2 block text-sm text-text-muted">Example: coaching, business, career</span>
                        </div>
                    </div>
                    <div class="form-group space-y-2">
                        <label for="serviceRequirements" class="block text-sm font-medium text-text-secondary">Client Requirements</label>
                        <textarea name="serviceRequirements" id="serviceRequirements" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="List any requirements clients should know before booking" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-actions flex flex-col-reverse gap-3 border-t border-divider pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('professional.service.index') }}" class="btn btn-outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-300">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Save Service
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#serviceCategory').change(function() {
        loadSubServices($(this).val());
    });

    var serviceId = $('#serviceCategory').val();
    if (serviceId) {
        loadSubServices(serviceId);
    }
});

function loadSubServices(serviceId) {
    const container = $('#subServiceContainer');

    if (!serviceId) {
        container.html('<p class="text-muted rounded-control border border-dashed border-border bg-surface-muted px-4 py-5 text-sm text-text-muted">Please select a service category first to see available sub-services.</p>');
        return;
    }

    container.html('<div class="sub-service-loading flex items-center gap-2 rounded-control border border-border bg-surface-muted px-4 py-5 text-sm text-text-secondary"><i class="fas fa-spinner fa-spin text-brand-primary" aria-hidden="true"></i> Loading sub-services...</div>');

    $.ajax({
        url: "{{ route('professional.service.getSubServices') }}",
        type: "GET",
        data: { service_id: serviceId },
        success: function(response) {
            if (response.success && response.subServices.length > 0) {
                let html = '';
                response.subServices.forEach(function(subService) {
                    html += `
                        <div class="sub-service-item flex items-center gap-3 rounded-control border border-border bg-surface-muted p-3">
                            <input type="checkbox" name="subServices[]" value="${subService.id}" id="subService${subService.id}" class="h-4 w-4 rounded border-border text-brand-600 focus:ring-brand-500">
                            <label for="subService${subService.id}" class="block text-sm font-medium text-text-secondary">${subService.name}</label>
                        </div>
                    `;
                });
                container.html(html);
                enforceSubServiceLimit(container);
            } else {
                container.html('<p class="text-muted rounded-control border border-dashed border-border bg-surface-muted px-4 py-5 text-sm text-text-muted">No sub-services available for this category.</p>');
            }
        },
        error: function(xhr) {
            container.html('<p class="text-danger rounded-control border border-error/20 bg-error-surface px-4 py-5 text-sm text-error">Error loading sub-services. Please try again.</p>');
        }
    });
}

function enforceSubServiceLimit(container) {
    container.find('input[type="checkbox"]').off('change').on('change', function() {
        const selected = container.find('input[type="checkbox"]:checked').length;
        if (selected > 2) {
            this.checked = false;
            toastr.error('You can select up to two sub-services.');
        }
    });
}

$('#serviceForm').submit(function(e) {
    e.preventDefault();
    let form = this;
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton.disabled) {
        return;
    }
    let formData = new FormData(form);
    submitButton.disabled = true;
    submitButton.dataset.originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    $.ajax({
        url: "{{ route('professional.service.store') }}",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                form.reset();
                setTimeout(() => {
                    window.location.href = "{{ route('professional.service.index') }}";
                }, 1500);
            } else {
                toastr.error(response.message || "Something went wrong");
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.dataset.originalText;
            }
        },
        error: function(xhr) {
            submitButton.disabled = false;
            submitButton.innerHTML = submitButton.dataset.originalText;
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors || {};
                if (Object.keys(errors).length) {
                    $.each(errors, function(key, value) {
                        toastr.error(value[0]);
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Please check the submitted information.');
                }
            } else {
                toastr.error(xhr.responseJSON?.message || "An unexpected error occurred");
            }
        }
    });
});
</script>
@endsection
