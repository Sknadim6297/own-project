@extends('professional.layout.layout')

@section('title', 'Add Rate | YuktiOne')

@section('content')
<div class="rates-create-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="rates-shell mx-auto w-full space-y-5">
        <header class="rates-header flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="header-info min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.rate.index') }}">Rates</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Add Rate</li>
                    </ol>
                </nav>
                <span class="pretitle text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">Rates & Packages</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Add Rate</h1>
                <p class="mt-1 text-sm text-text-secondary">Set up clear, structured pricing so clients immediately understand the value of your offerings.</p>
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
                <h2 class="text-base font-semibold text-brand-950 sm:text-lg">Create Rate Packages</h2>
                <p class="subtitle mt-1 text-sm text-text-secondary">Configure pricing for your service and any sub-services. Add multiple session bundles as needed.</p>
            </div>

            <div class="rates-card__body space-y-6 p-5 sm:p-7">
                <form id="rateForm">
                    @csrf

                    @if($professionalServices && count($professionalServices) > 0)
                        @php $currentService = $professionalServices[0]; @endphp
                        <div class="info-card rounded-panel border border-brand-200 bg-brand-50/60 p-5">
                            <div class="info-card__header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-base font-semibold text-brand-950">{{ $currentService->service_name }}</h3>
                                <span class="info-badge inline-flex items-center gap-1.5 rounded-full bg-success-surface px-3 py-1 text-xs font-semibold text-success">
                                    <i class="fas fa-briefcase" aria-hidden="true"></i>
                                    Active Service
                                </span>
                            </div>
                            <p class="info-card__hint mt-2 text-sm text-text-secondary">All rate plans you create below will attach to this service. You can add as many session-based packages as you need.</p>

                            <div class="info-grid mt-4 grid gap-5 md:grid-cols-2">
                                <div class="info-field space-y-2">
                                    <label class="block text-sm font-medium text-text-secondary">Your Service</label>
                                    <input type="text" class="form-control info-input w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" value="{{ $currentService->service_name }}" readonly>
                                    <input type="hidden" id="serviceId" value="{{ $currentService->id }}">
                                    <span class="small-note mt-2 block text-xs leading-5 text-text-muted">This field is locked to the primary service linked to your professional profile.</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="empty-state rounded-panel border border-dashed border-border bg-white p-8 text-center sm:p-10">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-warning-surface text-warning">
                                <i class="fas fa-exclamation-triangle text-xl" aria-hidden="true"></i>
                            </div>
                            <strong class="block text-base font-semibold text-brand-950">No service found yet.</strong>
                            <p class="mx-auto mt-2 max-w-md text-sm text-text-secondary">Please create a service first before setting up rate packages.</p>
                            <a href="{{ route('professional.service.create') }}" class="btn-pill btn-pill--primary mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                                Create Service
                            </a>
                        </div>
                    @endif

                    <div class="rate-section space-y-4 rounded-panel border border-border bg-surface-muted p-4 sm:p-5" id="serviceRateTableContainer" style="display: none">
                        <div class="section-heading flex flex-col gap-1 border-b border-divider pb-4">
                            <h4 class="text-base font-semibold text-brand-950">Service Level Rates</h4>
                            <p class="text-sm text-text-secondary">Add session-based plans for your main service offering.</p>
                        </div>
                        <div class="table-shell overflow-hidden rounded-control border border-border bg-white">
                            <div class="table-responsive w-full overflow-x-auto">
                                <table class="table table-bordered styled-table w-full min-w-[42rem] border-collapse text-left text-sm">
                                    <thead>
                                        <tr>
                                            <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Select Service</th>
                                            <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Session Type</th>
                                            <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">No of Sessions</th>
                                            <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Rate Per Session</th>
                                            <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Final Rate</th>
                                            <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="serviceRateTableBody">
                                        <!-- Service level rates will be added here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="inline-actions">
                            <button type="button" class="btn btn-outline add-btn inline-flex items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted" id="addServiceRateBtn">
                                <i class="fas fa-plus"></i>
                                Add New Rate
                            </button>
                        </div>
                    </div>

                    <div class="rate-section space-y-4 rounded-panel border border-border bg-surface-muted p-4 sm:p-5" id="subServiceRateContainer" style="display: none">
                        <div class="section-heading flex flex-col gap-1 border-b border-divider pb-4">
                            <h4 class="text-base font-semibold text-brand-950">Sub-Service Level Rates</h4>
                            <p class="text-sm text-text-secondary">Need different pricing for specific sub-services? Configure them here.</p>
                        </div>
                        <div id="subServiceRatesList" class="space-y-5">
                            <!-- Sub-service rate tables will be dynamically added here -->
                        </div>
                    </div>

                    <div class="form-footer flex flex-col-reverse gap-3 border-t border-divider pt-5 sm:flex-row sm:justify-end" id="saveButtonContainer" style="display: none">
                        <button type="submit" class="btn primary-btn inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-300">
                            <i class="fas fa-save" aria-hidden="true"></i>
                            Save Rates
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionTypes = ['One Time', 'Monthly', 'Quarterly', 'Free Hand']; 
    let currentServiceId = null;
    let currentService = null;
    let serviceRateCounter = 0;
    let subServiceRateCounters = {};
    let usedSessionTypes = [];
    let usedForSubMap = {}; // subServiceId => array of used session types from DB
    
    // Auto-initialize with the professional's service
    const serviceId = document.getElementById('serviceId');
    if (serviceId && serviceId.value) {
        currentServiceId = serviceId.value;
        currentService = @json($professionalServices).find(s => s.id == currentServiceId);
        
        if (currentService) {
            // Show service level rate table
            document.getElementById('serviceRateTableContainer').style.display = 'block';
            document.getElementById('saveButtonContainer').style.display = 'block';
            
            // Check if service has sub-services
            if (currentService && currentService.sub_services && currentService.sub_services.length > 0) {
                document.getElementById('subServiceRateContainer').style.display = 'block';
                createSubServiceRateTables();
            } else {
                document.getElementById('subServiceRateContainer').style.display = 'none';
            }
            
            // Add initial service rate row
            // fetch used session types for this service (service-level) and then add rows
            (async () => {
                usedSessionTypes = await fetchUsedSessionTypes();
                addServiceRateRow();
            })();
        }
    }
    
    // Service selection handler (removed as no longer needed)
    /*
    document.getElementById('serviceSelect').addEventListener('change', function() {
        currentServiceId = this.value;
        currentService = null;
        
        if (currentServiceId) {
            // Find the selected service
            currentService = @json($professionalServices).find(s => s.id == currentServiceId);
            
            // Show service level rate table
            document.getElementById('serviceRateTableContainer').style.display = 'block';
            document.getElementById('saveButtonContainer').style.display = 'block';
            
            // Clear existing tables
            document.getElementById('serviceRateTableBody').innerHTML = '';
            document.getElementById('subServiceRatesList').innerHTML = '';
            
            // Reset counters
            serviceRateCounter = 0;
            subServiceRateCounters = {};
            
            // Check if service has sub-services
            if (currentService && currentService.sub_services && currentService.sub_services.length > 0) {
                document.getElementById('subServiceRateContainer').style.display = 'block';
                createSubServiceRateTables();
            } else {
                document.getElementById('subServiceRateContainer').style.display = 'none';
            }
            
            // Add initial service rate row
            addServiceRateRow();
            
        } else {
            document.getElementById('serviceRateTableContainer').style.display = 'none';
            document.getElementById('subServiceRateContainer').style.display = 'none';
            document.getElementById('saveButtonContainer').style.display = 'none';
        }
    });
    */
    
    // Create sub-service rate tables
    function createSubServiceRateTables() {
        const container = document.getElementById('subServiceRatesList');
        
        currentService.sub_services.forEach(subService => {
            subServiceRateCounters[subService.id] = 0;
            
            const subServiceDiv = document.createElement('div');
            subServiceDiv.className = 'sub-service-rate-section space-y-4 rounded-panel border border-border bg-white p-4';
            subServiceDiv.innerHTML = `
                <div class="sub-service-heading flex flex-col gap-1">
                    <h5 class="text-sm font-semibold text-brand-950">${subService.name}</h5>
                    <span class="text-sm text-text-secondary">Add session plans specific to this sub-service.</span>
                </div>
                <div class="table-shell overflow-hidden rounded-control border border-border bg-white">
                    <div class="table-responsive w-full overflow-x-auto">
                        <table class="table table-bordered styled-table w-full min-w-[42rem] border-collapse text-left text-sm">
                            <thead>
                            <tr>
                                <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Select Sub-service</th>
                                <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Session Type</th>
                                <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">No of Sessions</th>
                                <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Rate Per Session</th>
                                <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Final Rate</th>
                                <th class="border-b border-divider px-4 py-3 align-top bg-brand-50 text-xs font-semibold uppercase tracking-wide text-text-secondary">Action</th>
                            </tr>
                        </thead>
                        <tbody data-subservice-id="${subService.id}">
                            <!-- Sub-service rates will be added here -->
                        </tbody>
                        </table>
                    </div>
                </div>
                <div class="inline-actions">
                    <button type="button" class="btn btn-outline add-subservice-rate-btn inline-flex items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2.5 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted" data-subservice-id="${subService.id}" data-subservice-name="${subService.name}">
                        <i class="fas fa-plus"></i> Add New Rate
                    </button>
                </div>
            `;
            
            container.appendChild(subServiceDiv);

            // Add initial sub-service rate row after fetching used session types for that sub-service
            (async () => {
                const usedForSub = await fetchUsedSessionTypes(subService.id);
                usedForSubMap[subService.id] = usedForSub;
                addSubServiceRateRow(subService.id, subService.name, usedForSub);
            })();
        });
        
        // Add event listeners for sub-service add buttons
        document.querySelectorAll('.add-subservice-rate-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const subServiceId = this.dataset.subserviceId;
                const subServiceName = this.dataset.subserviceName;
                // ensure we have latest used list for this sub-service
                const usedForSub = await fetchUsedSessionTypes(subServiceId);
                usedForSubMap[subServiceId] = usedForSub;
                // check if any remaining session type is available
                const combined = Array.from(new Set([...(usedForSubMap[subServiceId] || []), ...Array.from(document.querySelectorAll(`tbody[data-subservice-id="${subServiceId}"] .session-type`)).map(s=>s.value).filter(Boolean)]));
                const remaining = sessionTypes.filter(t => !combined.includes(t));
                if (remaining.length === 0) {
                    toastr.error('All session types already added for this sub-service.');
                    return;
                }
                addSubServiceRateRow(subServiceId, subServiceName, usedForSub);
            });
        });
    }

    // Fetch already used session types for current service/sub-service scope
    async function fetchUsedSessionTypes(subServiceId = null) {
        try {
            const url = new URL(`{{ route('professional.rate.get-session-types') }}`);
            url.searchParams.set('professional_service_id', currentServiceId);
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) return [];
            const data = await resp.json();
            if (data.status === 'success' && Array.isArray(data.rates)) {
                // Filter rates based on service_id and sub_service_id
                const filteredRates = data.rates.filter(rate => {
                    if (subServiceId) {
                        // For sub-service rates, match sub_service_id
                        return rate.sub_service_id == subServiceId;
                    } else {
                        // For service rates, sub_service_id should be null
                        return rate.sub_service_id == null;
                    }
                });
                return filteredRates.map(rate => rate.session_type);
            }
        } catch (err) {
            console.error('Error fetching used session types:', err);
        }
        return [];
    }
    
    // Add service level rate row
    function addServiceRateRow() {
        const tbody = document.getElementById('serviceRateTableBody');
        serviceRateCounter++;
        
        const newRow = document.createElement('tr');
        newRow.dataset.type = 'service';
        newRow.dataset.counter = serviceRateCounter;
        
        newRow.innerHTML = `
            <td data-label="Service" class="border-b border-divider px-4 py-3 align-top">
                <input type="text" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" value="${currentService.service_name}" readonly>
            </td>
            <td data-label="Session Type" class="border-b border-divider px-4 py-3 align-top">
                <select class="form-control session-type w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="service_rates[${serviceRateCounter}][session_type]" >
                    <option value="">Select Session Type</option>
                    ${sessionTypes.map(type => {
                        const disabled = usedSessionTypes.includes(type) ? 'disabled' : '';
                        return `<option value="${type}" ${disabled}>${type}${disabled ? ' - Already Added' : ''}</option>`;
                    }).join('')}
                </select>
            </td>
            <td data-label="No of Sessions" class="border-b border-divider px-4 py-3 align-top">
                <input type="number" class="form-control num-sessions w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="service_rates[${serviceRateCounter}][num_sessions]" value="1" min="1" >
            </td>
            <td data-label="Rate Per Session" class="border-b border-divider px-4 py-3 align-top">
                <input type="number" class="form-control rate-per-session w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="service_rates[${serviceRateCounter}][rate_per_session]" value="0" min="0" step="100" >
            </td>
            <td data-label="Final Rate" class="border-b border-divider px-4 py-3 align-top">
                <input type="number" class="form-control final-rate w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="service_rates[${serviceRateCounter}][final_rate]" readonly>
            </td>
            <td data-label="Action" class="border-b border-divider px-4 py-3 align-top">
                <button type="button" class="btn btn-danger btn-sm delete-row inline-flex items-center justify-center gap-2 rounded-control bg-error px-3 py-2 text-sm font-semibold text-white hover:bg-red-800" title="Remove rate">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        attachRateCalculationEvents(newRow);
        updateUsedState();
    }
    
    // Add sub-service level rate row
    function addSubServiceRateRow(subServiceId, subServiceName, usedForSub = []) {
        const tbody = document.querySelector(`tbody[data-subservice-id="${subServiceId}"]`);
        subServiceRateCounters[subServiceId]++;
        const counter = subServiceRateCounters[subServiceId];
        
        const newRow = document.createElement('tr');
        newRow.dataset.type = 'subservice';
        newRow.dataset.subserviceId = subServiceId;
        newRow.dataset.counter = counter;
        
        newRow.innerHTML = `
            <td data-label="Sub-service" class="border-b border-divider px-4 py-3 align-top">
                <input type="text" class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" value="${subServiceName}" readonly>
            </td>
            <td data-label="Session Type" class="border-b border-divider px-4 py-3 align-top">
                <select class="form-control session-type w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="subservice_rates[${subServiceId}][${counter}][session_type]" >
                    <option value="">Select Session Type</option>
                    ${sessionTypes.map(type => {
                        const disabled = (usedForSub.length ? usedForSub.includes(type) : usedSessionTypes.includes(type)) ? 'disabled' : '';
                        return `<option value="${type}" ${disabled}>${type}${disabled ? ' - Already Added' : ''}</option>`;
                    }).join('')}
                </select>
            </td>
            <td data-label="No of Sessions" class="border-b border-divider px-4 py-3 align-top">
                <input type="number" class="form-control num-sessions w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="subservice_rates[${subServiceId}][${counter}][num_sessions]" value="1" min="1">
            </td>
            <td data-label="Rate Per Session" class="border-b border-divider px-4 py-3 align-top">
                <input type="number" class="form-control rate-per-session w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="subservice_rates[${subServiceId}][${counter}][rate_per_session]" value="0" min="0" step="100">
            </td>
            <td data-label="Final Rate" class="border-b border-divider px-4 py-3 align-top">
                <input type="number" class="form-control final-rate w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200" name="subservice_rates[${subServiceId}][${counter}][final_rate]" readonly>
            </td>
            <td data-label="Action" class="border-b border-divider px-4 py-3 align-top">
                <button type="button" class="btn btn-danger btn-sm delete-row inline-flex items-center justify-center gap-2 rounded-control bg-error px-3 py-2 text-sm font-semibold text-white hover:bg-red-800" title="Remove rate">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        attachRateCalculationEvents(newRow);
        updateUsedState();
    }

    // Update UI state: disable options already used (DB + current form), and disable Add buttons when none remain
    function updateUsedState() {
        // Service-level
        const serviceSelects = Array.from(document.querySelectorAll('#serviceRateTableBody .session-type'));
        const serviceFormUsed = serviceSelects.map(s => s.value).filter(Boolean);
        const serviceCombined = Array.from(new Set([...(usedSessionTypes || []), ...serviceFormUsed]));

        serviceSelects.forEach(select => {
            const currentVal = select.value;
            Array.from(select.options).forEach(opt => {
                if (!opt.value) return;
                const isUsed = opt.value !== currentVal && serviceCombined.includes(opt.value);
                opt.disabled = isUsed;
                
                // Update the text content to show "Already Added"
                if (isUsed && !opt.textContent.includes(' - Already Added')) {
                    opt.textContent = opt.value + ' - Already Added';
                } else if (!isUsed && opt.textContent.includes(' - Already Added')) {
                    opt.textContent = opt.value;
                }
            });
        });

        // Disable service add button if no remaining
        const serviceRemaining = sessionTypes.filter(t => !serviceCombined.includes(t));
        const addServiceBtn = document.getElementById('addServiceRateBtn');
        if (serviceRemaining.length === 0) {
            addServiceBtn.disabled = true;
        } else {
            addServiceBtn.disabled = false;
        }

        // Sub-service level
        document.querySelectorAll('#subServiceRatesList tbody[data-subservice-id]').forEach(tbody => {
            const subId = tbody.dataset.subserviceId;
            const selects = Array.from(tbody.querySelectorAll('.session-type'));
            const formUsed = selects.map(s => s.value).filter(Boolean);
            const dbUsed = usedForSubMap[subId] || [];
            const combined = Array.from(new Set([...(dbUsed), ...formUsed]));

            selects.forEach(select => {
                const currentVal = select.value;
                Array.from(select.options).forEach(opt => {
                    if (!opt.value) return;
                    const isUsed = opt.value !== currentVal && combined.includes(opt.value);
                    opt.disabled = isUsed;
                    
                    // Update the text content to show "Already Added"
                    if (isUsed && !opt.textContent.includes(' - Already Added')) {
                        opt.textContent = opt.value + ' - Already Added';
                    } else if (!isUsed && opt.textContent.includes(' - Already Added')) {
                        opt.textContent = opt.value;
                    }
                });
            });

            // disable sub-service add button if none remaining
            const remaining = sessionTypes.filter(t => !combined.includes(t));
            const btn = document.querySelector(`.add-subservice-rate-btn[data-subservice-id="${subId}"]`);
            if (btn) btn.disabled = (remaining.length === 0);
        });
    }

    // When any session-type select changes, recompute used state
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('session-type')) {
            updateUsedState();
        }
    });
    
    // Attach calculation events to a row
    function attachRateCalculationEvents(row) {
        const numSessionsInput = row.querySelector('.num-sessions');
        const ratePerSessionInput = row.querySelector('.rate-per-session');
        const finalRateInput = row.querySelector('.final-rate');
        
        function calculateFinalRate() {
            const numSessions = parseInt(numSessionsInput.value) || 0;
            const ratePerSession = parseInt(ratePerSessionInput.value) || 0;
            finalRateInput.value = numSessions * ratePerSession;
        }
        
        numSessionsInput.addEventListener('input', calculateFinalRate);
        ratePerSessionInput.addEventListener('input', calculateFinalRate);
        
        // Initial calculation
        calculateFinalRate();
    }
    
    // Add service rate button event listener
    document.getElementById('addServiceRateBtn').addEventListener('click', function() {
        addServiceRateRow();
    });
    
    // Row deletion event listener
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('delete-row') || e.target.closest('.delete-row'))) {
            const rowToDelete = e.target.closest('tr');
            
            // Check if this is the last row in service rates
            if (rowToDelete.dataset.type === 'service') {
                const serviceRows = document.querySelectorAll('#serviceRateTableBody tr');
                if (serviceRows.length <= 1) {
                    toastr.error('At least one service rate is required.');
                    return;
                }
            }
            
            // Check if this is the last row in a sub-service
            if (rowToDelete.dataset.type === 'subservice') {
                const subServiceId = rowToDelete.dataset.subserviceId;
                const subServiceRows = document.querySelectorAll(`tbody[data-subservice-id="${subServiceId}"] tr`);
                if (subServiceRows.length <= 1) {
                    toastr.error('At least one rate is required for each sub-service.');
                    return;
                }
            }
            
            rowToDelete.remove();
            updateUsedState();
        }
    });
    
    // Form submission
    document.getElementById('rateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!currentServiceId) {
            toastr.error('Please select a service.');
            return;
        }
        
        // Collect form data and build rateData array expected by backend
        const formData = new FormData(this);
        formData.append('professional_id', "{{ Auth::guard('professional')->id() }}");
        formData.append('professional_service_id', currentServiceId);

        // Build rateData from all rows (service-level and sub-service-level)
        // Only include rows that have session_type selected
        const rateData = [];
        document.querySelectorAll('#serviceRateTableBody tr').forEach(row => {
            const sessionType = row.querySelector('.session-type').value;
            // Only add if session type is selected
            if (sessionType) {
                rateData.push({
                    session_type: sessionType,
                    num_sessions: row.querySelector('.num-sessions').value,
                    rate_per_session: row.querySelector('.rate-per-session').value,
                    final_rate: row.querySelector('.final-rate').value,
                    sub_service_id: null
                });
            }
        });

        document.querySelectorAll('#subServiceRatesList tbody').forEach(tbody => {
            const subServiceId = tbody.dataset.subserviceId;
            tbody.querySelectorAll('tr').forEach(row => {
                const sessionType = row.querySelector('.session-type').value;
                // Only add if session type is selected
                if (sessionType) {
                    rateData.push({
                        session_type: sessionType,
                        num_sessions: row.querySelector('.num-sessions').value,
                        rate_per_session: row.querySelector('.rate-per-session').value,
                        final_rate: row.querySelector('.final-rate').value,
                        sub_service_id: subServiceId
                    });
                }
            });
        });
        
        // Validate that we have at least one complete rate after filtering
        if (rateData.length === 0) {
            toastr.error('Please fill in at least one complete rate with session type selected.');
            return;
        }
        
        // NOW check what complete rates were added and show confirmation alerts
        const hasSubServicesAvailable = currentService && currentService.sub_services && currentService.sub_services.length > 0;
        const hasCompleteMainServiceRates = rateData.some(r => r.sub_service_id === null);
        const hasCompleteSubServiceRates = rateData.some(r => r.sub_service_id !== null);
        
        // Show confirmation alerts for skipped sections (only if sub-services are available)
        if (hasSubServicesAvailable && hasCompleteMainServiceRates && !hasCompleteSubServiceRates) {
            if (!confirm('You skipped the sub-service add rate. Do you want to continue saving only main service rates?')) {
                return;
            }
        }
        
        if (hasSubServicesAvailable && !hasCompleteMainServiceRates && hasCompleteSubServiceRates) {
            if (!confirm('You skipped the main service add rate. Do you want to continue saving only sub-service rates?')) {
                return;
            }
        }

        // Check for duplicate session types with same sub-service combination
        const combinations = rateData.map(r => {
            return r.session_type.toLowerCase() + '|' + (r.sub_service_id || 'null');
        });
        const duplicates = combinations.filter((item, index) => combinations.indexOf(item) !== index);
        
        if (duplicates.length > 0) {
            toastr.error('Duplicate session types with the same sub-service are not allowed. Please ensure each combination is unique.');
            return;
        }

        // Append rateData entries to FormData as rateData[0][field]
        rateData.forEach((r, idx) => {
            formData.append(`rateData[${idx}][session_type]`, r.session_type);
            formData.append(`rateData[${idx}][num_sessions]`, r.num_sessions);
            formData.append(`rateData[${idx}][rate_per_session]`, r.rate_per_session);
            formData.append(`rateData[${idx}][final_rate]`, r.final_rate);
            if (r.sub_service_id !== null) {
                formData.append(`rateData[${idx}][sub_service_id]`, r.sub_service_id);
            }
        });
        
        // Show loading indicator
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        // AJAX request
        $.ajax({
            url: "{{ route('professional.rate.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    setTimeout(() => {
                        window.location.href = "{{ route('professional.rate.index') }}";
                    }, 1500);
                } else {
                    toastr.error(response.message || "Something went wrong");
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            },
            error: function(xhr) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    if (xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        $.each(errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    }
                } else {
                    toastr.error(xhr.responseJSON?.message || "An unexpected error occurred");
                }
            }
        });
    });
});
</script>
@endsection
