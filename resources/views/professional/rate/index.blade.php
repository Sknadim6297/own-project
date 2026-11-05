@extends('professional.layout.layout')

@section('title', 'Rate Management | YuktiOne')

@section('content')
<div class="rates-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="rates-shell mx-auto w-full space-y-5">
        <header class="rates-header flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="hero-meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Rates</li>
                    </ol>
                </nav>
                <span class="hero-eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="fas fa-rupee-sign" aria-hidden="true"></i>Rates</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Rate Management</h1>
                <p class="mt-1 text-sm text-text-secondary">Review and manage pricing for your services and sub-services.</p>
            </div>
            <a href="{{ route('professional.rate.create') }}" class="btn-pill btn-pill--primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Add New Rate
            </a>
        </header>

        <section class="filters-card rounded-panel border border-border bg-white p-5 shadow-card">
            <div class="filters-header flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="fas fa-filter text-brand-primary" aria-hidden="true"></i> Filter Rates</h2>
                <button type="button" class="filters-toggle inline-flex items-center gap-2 rounded-control border border-border bg-white px-3 py-2 text-sm font-medium text-text-secondary transition hover:bg-surface-muted" id="toggleFilters">
                    <i class="fas fa-chevron-down" aria-hidden="true"></i> Toggle Filters
                </button>
            </div>
            <div class="filters-body mt-5 hidden space-y-4" id="filtersBody">
                <div class="filters-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="space-y-2">
                        <label for="filterService" class="block text-sm font-medium text-text-secondary">Service</label>
                        <select id="filterService" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">All Services</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="filterSubService" class="block text-sm font-medium text-text-secondary">Sub-Service</label>
                        <select id="filterSubService" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">All Sub-Services</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="filterSessionType" class="block text-sm font-medium text-text-secondary">Session Type</label>
                        <select id="filterSessionType" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">All Session Types</option>
                            <option value="One Time">One Time</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Free Hand">Free Hand</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="filterRate" class="block text-sm font-medium text-text-secondary">Rate Range</label>
                        <select id="filterRate" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">All Rates</option>
                            <option value="0-500">₹0 - ₹500</option>
                            <option value="501-1000">₹501 - ₹1000</option>
                            <option value="1001-2000">₹1001 - ₹2000</option>
                            <option value="2001-5000">₹2001 - ₹5000</option>
                            <option value="5000+">₹5000+</option>
                        </select>
                    </div>
                </div>
                <div class="filters-actions mt-5 flex flex-wrap items-center gap-3">
                    <button type="button" id="applyFilters" class="btn btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-300">Apply Filters</button>
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted">Clear All</button>
                    <span id="filterResults" class="filters-results text-sm text-text-muted"></span>
                </div>
            </div>
        </section>

        @php
            $groupedRates = $rates->groupBy(function($rate) {
                if ($rate->professionalService && $rate->professionalService->service_id) {
                    return $rate->professionalService->service_id;
                }
                if ($rate->subService && $rate->subService->service_id) {
                    return $rate->subService->service_id;
                }
                return 'unknown';
            });
        @endphp

        @if($groupedRates->count() > 0)
            @foreach($groupedRates as $serviceId => $serviceRates)
                @php
                    $firstRate = $serviceRates->first();
                    $serviceName = null;

                    if ($firstRate && $firstRate->professionalService) {
                        if ($firstRate->professionalService->service) {
                            $serviceName = $firstRate->professionalService->service->name;
                        } elseif ($firstRate->professionalService->service_name) {
                            $serviceName = $firstRate->professionalService->service_name;
                        }
                    }

                    if (!$serviceName && $firstRate && $firstRate->subService) {
                        $subServiceModel = $firstRate->subService;
                        if ($subServiceModel && $subServiceModel->service_id) {
                            $serviceFromSubService = \App\Models\Service::find($subServiceModel->service_id);
                            if ($serviceFromSubService) {
                                $serviceName = $serviceFromSubService->name;
                            }
                        }
                    }

                    if (!$serviceName) {
                        $serviceName = 'Service';
                    }

                    $serviceOnlyRates = $serviceRates->where('sub_service_id', null);
                    $subServiceRates = $serviceRates->where('sub_service_id', '!=', null)->groupBy('sub_service_id');
                @endphp

                <section class="service-section overflow-hidden rounded-panel border border-border bg-white shadow-card" data-service="{{ $serviceName }}">
                    <div class="service-title flex flex-col gap-2 border-b border-divider bg-surface-muted px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <h3 class="text-base font-semibold text-brand-950">{{ $serviceName }}</h3>
                        <span class="filters-results inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">Total rates: {{ $serviceRates->count() }}</span>
                    </div>

                    <div class="table-responsive w-full overflow-x-auto">
                        <table class="rates-table w-full min-w-[48rem] border-collapse text-left text-sm">
                            <thead>
                                <tr>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Type</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Session Type</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">No. of Sessions</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Rate/Session (₹)</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Final Rate (₹)</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Features</th>
                                    <th class="border-b border-divider bg-brand-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-text-secondary">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($serviceOnlyRates as $rate)
                                    <tr data-service="{{ $serviceName }}" data-sub-service="" data-session-type="{{ $rate->session_type }}" data-final-rate="{{ $rate->final_rate }}">
                                        <td data-label="Type" class="border-b border-divider px-4 py-4">
                                            <span class="rate-type-badge inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-800"><i class="fas fa-briefcase" aria-hidden="true"></i> {{ $serviceName }}</span>
                                        </td>
                                        <td data-label="Session Type" class="border-b border-divider px-4 py-4 text-text-primary">{{ $rate->session_type }}</td>
                                        <td data-label="No. of Sessions" class="border-b border-divider px-4 py-4 text-text-secondary">{{ $rate->num_sessions }}</td>
                                        <td data-label="Rate/Session" class="border-b border-divider px-4 py-4 text-text-secondary">₹{{ number_format($rate->rate_per_session, 2) }}</td>
                                        <td data-label="Final Rate" class="border-b border-divider px-4 py-4 font-semibold text-brand-950">₹{{ number_format($rate->final_rate, 2) }}</td>
                                        <td data-label="Features" class="border-b border-divider px-4 py-4">
                                            @if($rate->features && count($rate->features) > 0)
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($rate->features as $feature)
                                                        @if(trim($feature))
                                                            <span class="feature-pill inline-flex rounded-full bg-surface-muted px-2.5 py-1 text-xs font-medium text-text-secondary">{{ $feature }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <small class="text-muted text-text-muted">No features</small>
                                            @endif
                                        </td>
                                        <td data-label="Actions" class="border-b border-divider px-4 py-4">
                                            <div class="table-actions flex items-center gap-2">
                                                <a href="{{ route('professional.rate.edit', $rate->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-border text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800" title="Edit">
                                                    <i class="fas fa-edit" aria-hidden="true"></i>
                                                </a>
                                                <a href="javascript:void(0)" data-url="{{ route('professional.rate.destroy', $rate->id) }}" class="delete-item inline-flex h-9 w-9 items-center justify-center rounded-control border border-border text-text-secondary transition hover:border-error/30 hover:bg-error-surface hover:text-error" title="Delete">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach($subServiceRates as $subServiceId => $subRates)
                                    @php
                                        $subService = $subRates->first()->subService;
                                    @endphp
                                    @foreach($subRates as $rate)
                                        <tr data-service="{{ $serviceName }}" data-sub-service="{{ $subService->name ?? 'Sub-Service' }}" data-session-type="{{ $rate->session_type }}" data-final-rate="{{ $rate->final_rate }}">
                                            <td data-label="Type" class="border-b border-divider px-4 py-4">
                                                <span class="rate-type-badge sub inline-flex items-center gap-1.5 rounded-full bg-info-surface px-2.5 py-1 text-xs font-medium text-info"><i class="fas fa-layer-group" aria-hidden="true"></i> {{ $subService->name ?? 'Sub-Service' }}</span>
                                            </td>
                                            <td data-label="Session Type" class="border-b border-divider px-4 py-4 text-text-primary">{{ $rate->session_type }}</td>
                                            <td data-label="No. of Sessions" class="border-b border-divider px-4 py-4 text-text-secondary">{{ $rate->num_sessions }}</td>
                                            <td data-label="Rate/Session" class="border-b border-divider px-4 py-4 text-text-secondary">₹{{ number_format($rate->rate_per_session, 2) }}</td>
                                            <td data-label="Final Rate" class="border-b border-divider px-4 py-4 font-semibold text-brand-950">₹{{ number_format($rate->final_rate, 2) }}</td>
                                            <td data-label="Features" class="border-b border-divider px-4 py-4">
                                                @if($rate->features && count($rate->features) > 0)
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach($rate->features as $feature)
                                                            @if(trim($feature))
                                                                <span class="feature-pill inline-flex rounded-full bg-surface-muted px-2.5 py-1 text-xs font-medium text-text-secondary">{{ $feature }}</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <small class="text-muted text-text-muted">No features</small>
                                                @endif
                                            </td>
                                            <td data-label="Actions" class="border-b border-divider px-4 py-4">
                                                <div class="table-actions flex items-center gap-2">
                                                    <a href="{{ route('professional.rate.edit', $rate->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-border text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800" title="Edit">
                                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" data-url="{{ route('professional.rate.destroy', $rate->id) }}" class="delete-item inline-flex h-9 w-9 items-center justify-center rounded-control border border-border text-text-secondary transition hover:border-error/30 hover:bg-error-surface hover:text-error" title="Delete">
                                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            <section class="summary-grid grid gap-4 md:grid-cols-2">
                <div class="summary-card rounded-panel border border-border bg-white p-5 shadow-card">
                    <h4 class="mb-3 flex items-center gap-2 text-base font-semibold text-brand-950"><i class="fas fa-chart-bar text-brand-primary" aria-hidden="true"></i> Rate Summary</h4>
                    <p class="text-sm text-text-secondary"><strong class="text-text-primary">Total Rate Entries:</strong> {{ $rates->count() }}</p>
                    <p class="mt-1 text-sm text-text-secondary"><strong class="text-text-primary">Services with Rates:</strong> {{ $rates->pluck('service_id')->unique()->count() }}</p>
                    <p class="mt-1 text-sm text-text-secondary"><strong class="text-text-primary">Sub-Services with Rates:</strong> {{ $rates->whereNotNull('sub_service_id')->pluck('sub_service_id')->unique()->count() }}</p>
                </div>
                <div class="summary-card rounded-panel border border-border bg-white p-5 shadow-card">
                    <h4 class="mb-3 flex items-center gap-2 text-base font-semibold text-brand-950"><i class="fas fa-info-circle text-brand-primary" aria-hidden="true"></i> Quick Info</h4>
                    <p class="text-sm text-text-secondary">Each service can include up to four session types: One Time, Monthly, Quarterly, and Free Hand.</p>
                    <p class="mt-2 text-sm text-text-secondary">Use the filters above to quickly find rates for a specific offering.</p>
                </div>
            </section>
        @else
            <section class="service-section">
                <div class="empty-state rounded-panel border border-dashed border-border bg-white p-8 text-center sm:p-12">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <i class="fas fa-money-bill-wave text-xl" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-base font-semibold text-brand-950">No rate details found</h4>
                    <p class="mx-auto mt-2 max-w-md text-sm text-text-secondary">You haven't set up any rates yet. Start by adding rates for your services.</p>
                    <a href="{{ route('professional.rate.create') }}" class="btn-pill btn-pill--primary mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add Your First Rate
                    </a>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const filtersBody = document.getElementById('filtersBody');
    const filterService = document.getElementById('filterService');
    const filterSubService = document.getElementById('filterSubService');
    const filterSessionType = document.getElementById('filterSessionType');
    const filterRate = document.getElementById('filterRate');
    const applyFiltersBtn = document.getElementById('applyFilters');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const filterResults = document.getElementById('filterResults');

    let allRates = [];

    toggleFiltersBtn.addEventListener('click', function() {
        const isHidden = filtersBody.classList.contains('hidden');
        filtersBody.classList.toggle('hidden', !isHidden);
        const icon = this.querySelector('i');
        icon.className = isHidden ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
    });

    function collectRateData() {
        allRates = [];
        document.querySelectorAll('.service-section').forEach(section => {
            section.querySelectorAll('tbody tr[data-service]').forEach(row => {
                const serviceName = row.getAttribute('data-service') || '';
                const subServiceName = row.getAttribute('data-sub-service') || '';
                const sessionType = row.getAttribute('data-session-type') || '';
                const finalRate = parseFloat(row.getAttribute('data-final-rate')) || 0;
                const isSubService = subServiceName !== '';
                
                if (serviceName && sessionType) {
                    allRates.push({
                        element: row,
                        section: section,
                        serviceName,
                        subServiceName,
                        sessionType,
                        finalRate,
                        isSubService
                    });
                }
            });
        });
    }

    function populateFilters() {
        const services = new Set();
        const subServices = new Set();
        
        allRates.forEach(rate => {
            services.add(rate.serviceName);
            if (rate.isSubService && rate.subServiceName) {
                subServices.add(rate.subServiceName);
            }
        });
        
        filterService.innerHTML = '<option value="">All Services</option>';
        Array.from(services).sort().forEach(service => {
            const option = document.createElement('option');
            option.value = service;
            option.textContent = service;
            filterService.appendChild(option);
        });
        
        filterSubService.innerHTML = '<option value="">All Sub-Services</option>';
        Array.from(subServices).sort().forEach(subService => {
            const option = document.createElement('option');
            option.value = subService;
            option.textContent = subService;
            filterSubService.appendChild(option);
        });
    }

    function applyFilters() {
        const serviceFilter = filterService.value.toLowerCase();
        const subServiceFilter = filterSubService.value.toLowerCase();
        const sessionTypeFilter = filterSessionType.value.toLowerCase();
        const rateFilter = filterRate.value;
        let visibleCount = 0;
        
        allRates.forEach(rate => {
            let visible = true;
            
            if (serviceFilter && !rate.serviceName.toLowerCase().includes(serviceFilter)) {
                visible = false;
            }
            
            if (subServiceFilter && (!rate.isSubService || !rate.subServiceName.toLowerCase().includes(subServiceFilter))) {
                visible = false;
            }
            
            if (sessionTypeFilter && !rate.sessionType.toLowerCase().includes(sessionTypeFilter)) {
                visible = false;
            }
            
            if (rateFilter) {
                const val = rate.finalRate;
                let match = false;
                switch(rateFilter) {
                    case '0-500': match = val >= 0 && val <= 500; break;
                    case '501-1000': match = val >= 501 && val <= 1000; break;
                    case '1001-2000': match = val >= 1001 && val <= 2000; break;
                    case '2001-5000': match = val >= 2001 && val <= 5000; break;
                    case '5000+': match = val > 5000; break;
                    default: match = true;
                }
                if (!match) visible = false;
            }
            
            rate.element.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });
        
        document.querySelectorAll('.service-section').forEach(section => {
            const visibleRows = Array.from(section.querySelectorAll('tbody tr[data-service]')).filter(row => row.style.display !== 'none');
            section.style.display = visibleRows.length > 0 ? '' : 'none';
        });
        
        filterResults.textContent = `Showing ${visibleCount} of ${allRates.length} rates`;
    }

    function clearAllFilters() {
        filterService.value = '';
        filterSubService.value = '';
        filterSessionType.value = '';
        filterRate.value = '';
        
        allRates.forEach(rate => {
            rate.element.style.display = '';
        });
        
        document.querySelectorAll('.service-section').forEach(section => {
            section.style.display = '';
        });
        
        filterResults.textContent = '';
    }

    applyFiltersBtn.addEventListener('click', applyFilters);
    clearFiltersBtn.addEventListener('click', clearAllFilters);
    [filterService, filterSubService, filterSessionType, filterRate].forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    collectRateData();
    populateFilters();
});
</script>
@endsection
