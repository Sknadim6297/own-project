@extends('professional.layout.layout')

@section('title', 'Edit Service | YuktiOne')

@section('content')
<div class="service-edit-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="service-edit-shell mx-auto w-full space-y-5">
        <section class="service-edit-hero flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.service.index') }}">Services</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Edit Service</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="fas fa-tools" aria-hidden="true"></i>Edit Service</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Edit Service</h1>
                <p class="mt-1 text-sm text-text-secondary">Update sub-services, tags, and client requirements for your offering.</p>
            </div>
            <a href="{{ route('professional.service.index') }}" class="btn-pill btn-pill--outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Back to Services
            </a>
        </section>

        <section class="form-card rounded-panel border border-border bg-white p-5 shadow-card sm:p-7">
            <form id="serviceForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="serviceDuration" value="{{ old('serviceDuration', $service->duration ?? 60) }}">

                <div class="form-section space-y-5 border-b border-divider pb-6">
                    <header>
                        <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Service Details</h2>
                        <p class="mt-1 text-sm text-text-secondary">The service category cannot be changed after creation.</p>
                    </header>
                    <div class="form-grid grid gap-5 md:grid-cols-2">
                        <div class="form-group space-y-2 md:col-span-2 lg:col-span-1">
                            <label for="serviceCategory" class="block text-sm font-medium text-text-secondary">
                                Service Category *
                                <span class="ml-1 inline-flex rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-semibold text-text-secondary">Not editable</span>
                            </label>
                            <select name="serviceId_disabled" id="serviceCategory" class="form-control w-full rounded-control border border-border bg-surface-muted px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" disabled>
                                <option value="">Select Category</option>
                                @foreach($services as $serviceItem)
                                    <option value="{{ $serviceItem->id }}" {{ $service->service_id == $serviceItem->id ? 'selected' : (isset($matchingServiceId) && $matchingServiceId == $serviceItem->id ? 'selected' : '') }}>
                                        {{ $serviceItem->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="serviceId" value="{{ old('serviceId', $service->service_id ?? $matchingServiceId) }}">
                            <span class="muted-note mt-2 block text-sm text-text-muted">Service category is read-only on this page and cannot be changed.</span>
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
                            <div class="sub-service-loading flex items-center gap-2 rounded-control border border-border bg-surface-muted px-4 py-5 text-sm text-text-secondary">
                                <i class="fas fa-spinner fa-spin text-brand-primary" aria-hidden="true"></i> Loading sub-services...
                            </div>
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
                                @php
                                    $selectedFeatures = is_array($service->features) ? $service->features : json_decode($service->features ?? '[]', true);
                                    $allFeatures = ['online' => 'Online Sessions'];
                                @endphp
                                @foreach($allFeatures as $key => $label)
                                    <label class="checkbox-item flex items-center gap-3 rounded-control border border-border bg-surface-muted px-3 py-3 text-sm font-medium text-text-secondary">
                                        <input type="checkbox" name="features[]" value="{{ $key }}" {{ in_array($key, old('features', $selectedFeatures)) ? 'checked' : 'checked' }} class="h-4 w-4 rounded border-border text-brand-600 focus:ring-brand-500"> {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group space-y-2">
                            <label for="serviceTags" class="block text-sm font-medium text-text-secondary">Tags</label>
                            <input type="text" name="serviceTags" id="serviceTags" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" value="{{ old('serviceTags', $service->tags ?? '') }}" placeholder="Add tags separated by commas">
                            <span class="muted-note mt-2 block text-sm text-text-muted">Example: coaching, business, career</span>
                        </div>
                    </div>
                    <div class="form-group space-y-2">
                        <label for="serviceRequirements" class="block text-sm font-medium text-text-secondary">Client Requirements</label>
                        <textarea name="serviceRequirements" id="serviceRequirements" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="List any requirements clients should know before booking" rows="3">{{ old('serviceRequirements', $service->requirements ?? '') }}</textarea>
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

    loadSubServices($('#serviceCategory').val(), @json($service->subServices->pluck('id')->toArray()));

    $('#serviceForm').submit(function(e) {
        e.preventDefault();
        let form = this;
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton.disabled) {
            return;
        }
        let formData = new FormData(form);
        formData.append('_method', 'PUT');
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        $.ajax({
            url: "{{ route('professional.service.update', $service->id) }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
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
});

function loadSubServices(serviceId, selectedSubServices = []) {
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
                    const isSelected = selectedSubServices.includes(subService.id);
                    html += `
                        <div class="sub-service-item flex items-center gap-3 rounded-control border border-border bg-surface-muted p-3">
                            <input type="checkbox" name="subServices[]" value="${subService.id}" id="subService${subService.id}" ${isSelected ? 'checked' : ''} class="h-4 w-4 rounded border-border text-brand-600 focus:ring-brand-500">
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
        error: function() {
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
</script>
@endsection
