@extends('professional.layout.layout')

@section('title', 'Services | YuktiOne')

@section('content')
<div class="services-index-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="services-shell mx-auto w-full space-y-5">
        <section class="services-hero flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Services</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="fas fa-briefcase" aria-hidden="true"></i>Services</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">All Services</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage the service category and sub-services linked to your professional profile.</p>
            </div>
            <div class="hero-actions flex w-full flex-col gap-3 sm:w-auto sm:items-end">
                @if(count($services) == 0)
                    <a href="{{ route('professional.service.create') }}" class="btn-pill btn-pill--primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                        Add Service
                    </a>
                @else
                    <div class="rounded-control border border-info/20 bg-info-surface px-3 py-3 text-sm text-info" role="status">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                        You can only add one service. Edit your existing service below.
                    </div>
                @endif
            </div>
        </section>

        @if(session('error'))
            <div class="rounded-control border border-error/20 bg-error-surface px-4 py-3 text-sm text-error" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="rounded-control border border-success/20 bg-success-surface px-4 py-3 text-sm text-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <section class="services-card overflow-hidden rounded-panel border border-border bg-white shadow-card">
            <header class="services-card__head flex flex-col gap-3 border-b border-divider p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Service List</h2>
                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">{{ count($services) }} {{ count($services) === 1 ? 'service' : 'services' }}</span>
            </header>
            <div class="services-card__body p-0 sm:p-0">
                @if($services->count() > 0)
                    <div class="table-wrapper w-full overflow-x-auto">
                        <table class="data-table w-full min-w-[48rem] border-collapse text-left text-sm">
                            <thead>
                                <tr>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Service Category</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Sub-Services</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Session Type</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Tags</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Client Requirements</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($services as $service)
                                    <tr class="align-top">
                                        <td data-label="Service Category" class="border-b border-divider px-4 py-4 font-medium text-text-primary">{{ $service->service?->name ?? $service->service_name ?? 'Service' }}</td>
                                        <td data-label="Sub-Services" class="border-b border-divider px-4 py-4">
                                            @if($service->subServices->count() > 0)
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($service->subServices as $subService)
                                                        <span class="inline-flex rounded-full bg-info-surface px-2.5 py-1 text-xs font-medium text-info">{{ $subService->name }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-text-muted">No specific sub-services</span>
                                            @endif
                                        </td>
                                        <td data-label="Session Type" class="border-b border-divider px-4 py-4">
                                            <span class="inline-flex rounded-full bg-success-surface px-2.5 py-1 text-xs font-medium text-success">Online Session</span>
                                        </td>
                                        <td data-label="Tags" class="border-b border-divider px-4 py-4 text-text-secondary">{{ $service->tags ?: '—' }}</td>
                                        <td data-label="Client Requirements" class="border-b border-divider px-4 py-4 text-text-secondary">{{ $service->requirements ?: '—' }}</td>
                                        <td data-label="Actions" class="actions-cell whitespace-nowrap border-b border-divider px-4 py-4">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('professional.service.edit', $service->id) }}" class="action-btn edit inline-flex h-9 w-9 items-center justify-center rounded-control border border-border text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800" title="Edit">
                                                    <i class="fas fa-edit" aria-hidden="true"></i>
                                                </a>
                                                <a href="javascript:void(0)" data-url="{{ route('professional.service.destroy', $service->id) }}" class="action-btn delete delete-service inline-flex h-9 w-9 items-center justify-center rounded-control border border-border text-text-secondary transition hover:border-error/30 hover:bg-error-surface hover:text-error" title="Delete">
                                                    <i class="far fa-trash-alt" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state p-8 text-center sm:p-12">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <i class="fas fa-briefcase text-xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base font-semibold text-brand-950">No services added yet</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-text-secondary">Click “Add Service” above to create your service offering and start configuring rates.</p>
                        <a href="{{ route('professional.service.create') }}" class="btn-pill btn-pill--primary mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                            <i class="fas fa-plus-circle" aria-hidden="true"></i>
                            Add Service
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).on('click', '.delete-service', function(e) {
    e.preventDefault();
    const url = $(this).data('url');

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C85B65',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message || 'Service has been deleted.', 'success')
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to delete service.', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred while deleting the service.', 'error');
                }
            });
        }
    });
});
</script>
@endsection
