@extends('professional.layout.layout')

@section('title', 'Additional Service Details | YuktiOne')

@section('styles')
<style>
    .custom-modal { display: none; }
    .custom-modal.show {
        display: flex;
        position: fixed;
        inset: 0;
        z-index: 60;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgb(15 23 42 / .5);
        overflow-y: auto;
    }
    .custom-modal .modal-content {
        width: min(100%, 32rem);
        margin: auto;
        border: 1px solid #E2E9EE;
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 18px 42px -20px rgb(20 43 77 / .24);
    }
    .form-control.is-invalid { border-color: #C85B65 !important; }
    .price-error { color: #C85B65; font-size: .75rem; margin-top: .35rem; }
</style>
@endsection

@section('content')
@php
    $effectiveBasePrice = $additionalService->getEffectiveBasePrice();
    $cgst = $additionalService->cgst ?? ($effectiveBasePrice * 0.09);
    $sgst = $additionalService->sgst ?? ($effectiveBasePrice * 0.09);
    $totalGst = $cgst + $sgst;
    $effectiveTotalPrice = $additionalService->getEffectiveTotalPrice();
    $professional = $additionalService->professional;
    $finalPrice = $additionalService->final_price;
    $serviceRequestMargin = $professional->service_request_margin ?? 0;
    $platformCommission = ($finalPrice * $serviceRequestMargin) / 100;
    $professionalEarning = $finalPrice - $platformCommission;
@endphp

<div class="mx-auto w-full max-w-7xl space-y-5">
    <header class="flex flex-col gap-4 rounded-panel border border-border bg-white p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
        <div class="min-w-0">
            <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.additional-services.index') }}">Additional Services</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li aria-current="page" class="text-text-secondary">Details</li>
                </ol>
            </nav>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Service management</p>
            <h1 class="mt-1 truncate text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">{{ $additionalService->service_name }}</h1>
            <p class="mt-1 text-sm text-text-secondary">Review request details, negotiation, payment and delivery actions.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if($additionalService->admin_status === 'pending')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-semibold text-warning">Pending review</span>
                @elseif($additionalService->admin_status === 'approved')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success-surface px-2.5 py-1 text-xs font-semibold text-success">Approved</span>
                @elseif($additionalService->admin_status === 'rejected')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-error-surface px-2.5 py-1 text-xs font-semibold text-error">Rejected</span>
                @endif

                @if($additionalService->payment_status === 'paid')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">Paid</span>
                @elseif($additionalService->payment_status === 'pending')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-semibold text-warning">Payment pending</span>
                @elseif($additionalService->payment_status === 'failed')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-error-surface px-2.5 py-1 text-xs font-semibold text-error">Payment failed</span>
                @endif

                @if($additionalService->consulting_status === 'pending')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold text-text-secondary">Consultation pending</span>
                @elseif($additionalService->consulting_status === 'in_progress')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-info-surface px-2.5 py-1 text-xs font-semibold text-info">In progress</span>
                @elseif($additionalService->consulting_status === 'done')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success-surface px-2.5 py-1 text-xs font-semibold text-success">Completed</span>
                @endif
            </div>
        </div>
        <a href="{{ route('professional.additional-services.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
            <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
            Back to list
        </a>
    </header>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.9fr)]">
        <div class="space-y-5">
            <section class="overflow-hidden rounded-panel border border-border bg-white shadow-card" aria-labelledby="overview-title">
                <header class="border-b border-divider px-5 py-4 sm:px-6">
                    <h2 id="overview-title" class="text-base font-semibold text-brand-950">Service overview</h2>
                    <p class="mt-1 text-sm text-text-muted">Core request details and pricing.</p>
                </header>
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium text-text-muted">Service name</p>
                            <p class="mt-1 text-sm font-semibold text-text-primary">{{ $additionalService->service_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-text-muted">Created</p>
                            <p class="mt-1 text-sm text-text-primary">{{ $additionalService->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        @if($additionalService->delivery_date)
                            <div>
                                <p class="text-xs font-medium text-text-muted">Delivery date</p>
                                <p class="mt-1 text-sm text-text-primary">{{ \Carbon\Carbon::parse($additionalService->delivery_date)->format('M d, Y') }}</p>
                            </div>
                        @endif
                        @if($additionalService->booking)
                            <div>
                                <p class="text-xs font-medium text-text-muted">Related booking</p>
                                <p class="mt-1 text-sm text-text-primary">#{{ $additionalService->booking->id }} · {{ $additionalService->booking->service->name ?? 'Booking' }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="rounded-control border border-divider bg-surface-muted p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Reason for additional service</p>
                        <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-text-secondary">{{ $additionalService->reason }}</p>
                    </div>

                    <div class="rounded-control border border-brand-100 bg-brand-50/50 p-4 sm:p-5">
                        <h3 class="text-sm font-semibold text-brand-950">Price breakdown</h3>
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-text-secondary">Base price</dt>
                                <dd class="font-medium text-text-primary">₹{{ number_format($effectiveBasePrice, 2) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-text-secondary">GST (18%)</dt>
                                <dd class="text-right font-medium text-text-primary">
                                    ₹{{ number_format($totalGst, 2) }}
                                    <span class="mt-0.5 block text-[11px] font-normal text-text-muted">CGST {{ number_format($cgst, 2) }} · SGST {{ number_format($sgst, 2) }}</span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-text-secondary">Negotiation status</dt>
                                <dd class="font-medium text-text-primary">
                                    @if($additionalService->negotiation_status === 'none')
                                        No negotiation
                                    @elseif($additionalService->negotiation_status === 'user_negotiated')
                                        Awaiting response
                                    @else
                                        Completed
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3 border-t border-brand-100 pt-2.5">
                                <dt class="font-semibold text-brand-950">Final price</dt>
                                <dd class="text-base font-semibold text-brand-800">₹{{ number_format($effectiveTotalPrice, 2) }}</dd>
                            </div>
                        </dl>
                        @if($additionalService->user_negotiated_price || $additionalService->admin_final_negotiated_price)
                            <div class="mt-3 rounded-control border border-divider bg-white px-3 py-2.5 text-xs leading-5 text-text-secondary">
                                @if($additionalService->user_negotiated_price)
                                    <p>Customer requested: <strong class="text-text-primary">₹{{ number_format($additionalService->user_negotiated_price, 2) }}</strong></p>
                                @endif
                                @if($additionalService->admin_final_negotiated_price)
                                    <p>Final negotiated: <strong class="text-text-primary">₹{{ number_format($additionalService->admin_final_negotiated_price, 2) }}</strong></p>
                                @else
                                    <p>No admin update yet.</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($additionalService->negotiation_status === 'admin_responded')
                        <div class="rounded-control border border-success/20 bg-success-surface p-4">
                            <p class="text-sm font-semibold text-success">Negotiation completed</p>
                            <p class="mt-1 text-sm text-text-secondary">
                                Original price: ₹{{ number_format($additionalService->total_price, 2) }}
                                → New price: ₹{{ number_format($additionalService->final_price, 2) }}
                            </p>
                            @if($additionalService->admin_negotiation_response)
                                <p class="mt-2 whitespace-pre-wrap text-sm text-text-secondary">{{ $additionalService->admin_negotiation_response }}</p>
                            @endif
                        </div>
                    @elseif($additionalService->negotiation_status === 'user_negotiated')
                        <div class="rounded-control border border-warning/20 bg-warning-surface p-4 sm:p-5">
                            <p class="text-sm font-semibold text-warning">Customer price negotiation pending</p>
                            <p class="mt-1 text-sm text-text-secondary">Requested price: ₹{{ number_format($additionalService->user_negotiated_price, 2) }}</p>
                            @if($additionalService->user_negotiation_reason)
                                <p class="mt-2 whitespace-pre-wrap text-sm text-text-secondary">{{ $additionalService->user_negotiation_reason }}</p>
                            @endif

                            <form id="negotiationResponseForm" class="mt-4 space-y-3 rounded-control border border-border bg-white p-4">
                                @csrf
                                <h3 class="text-sm font-semibold text-brand-950">Respond to negotiation</h3>
                                <div class="space-y-1.5">
                                    <label for="professional_final_price" class="block text-xs font-medium text-text-primary">Your final price (₹)</label>
                                    <input type="number" id="professional_final_price" name="professional_final_price" class="form-control min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" step="0.01" min="{{ $additionalService->base_price * 0.5 }}" value="{{ $additionalService->user_negotiated_price }}" required>
                                    <div class="price-error" style="display:none"></div>
                                </div>
                                <div class="space-y-1.5">
                                    <label for="professional_response" class="block text-xs font-medium text-text-primary">Your response</label>
                                    <textarea id="professional_response" name="professional_response" class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" rows="4" maxlength="1000" placeholder="Explain your final decision..." required></textarea>
                                    <p class="text-[11px] text-text-muted">Max 1000 characters</p>
                                </div>
                                <button type="submit" class="action-btn success inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                                    <i class="fas fa-paper-plane text-xs" aria-hidden="true"></i> Send response
                                </button>
                            </form>
                        </div>
                    @endif

                    @if($additionalService->admin_status === 'approved' && $additionalService->negotiation_status !== 'user_negotiated')
                        <form id="priceUpdateForm" class="space-y-3 rounded-control border border-border bg-surface-muted p-4 sm:p-5">
                            @csrf
                            <h3 class="text-sm font-semibold text-brand-950">Update service price</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label for="new_price" class="block text-xs font-medium text-text-primary">New price (₹)</label>
                                    <input type="number" id="new_price" name="new_price" class="form-control min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" step="0.01" min="{{ $additionalService->base_price * 0.5 }}" value="{{ $additionalService->final_price }}" required>
                                    <div class="price-error" style="display:none"></div>
                                    <p class="text-[11px] text-text-muted">Minimum price enforced automatically.</p>
                                </div>
                                <div class="space-y-1.5 sm:col-span-2">
                                    <label for="price_reason" class="block text-xs font-medium text-text-primary">Reason for update</label>
                                    <textarea id="price_reason" name="reason" class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" rows="4" maxlength="1000" placeholder="Explain why you're updating the price..." required></textarea>
                                    <p class="text-[11px] text-text-muted">Customers will be notified of your reasoning.</p>
                                </div>
                            </div>
                            <button type="submit" class="action-btn primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                                <i class="fas fa-save text-xs" aria-hidden="true"></i> Update price
                            </button>
                        </form>
                    @endif

                    @if($additionalService->admin_status === 'rejected' && $additionalService->admin_reason)
                        <div class="rounded-control border border-error/20 bg-error-surface p-4">
                            <p class="text-sm font-semibold text-error">Rejection reason</p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-text-secondary">{{ $additionalService->admin_reason }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-panel border border-border bg-white p-5 shadow-card" aria-labelledby="customer-title">
                <h2 id="customer-title" class="text-base font-semibold text-brand-950">Customer information</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-text-muted">Customer name</dt>
                        <dd class="mt-1 font-medium text-text-primary">{{ $additionalService->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-text-muted">Email</dt>
                        <dd class="mt-1 break-all text-text-primary">{{ $additionalService->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-text-muted">Phone</dt>
                        <dd class="mt-1 text-text-primary">{{ $additionalService->user->phone ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-panel border border-border bg-white p-5 shadow-card" aria-labelledby="payment-title">
                <h2 id="payment-title" class="text-base font-semibold text-brand-950">Payment & commission</h2>
                <div class="mt-4">
                    <p class="text-xs font-medium text-text-muted">Payment status</p>
                    <p class="mt-1">
                        @if($additionalService->payment_status === 'pending')
                            <span class="inline-flex rounded-full bg-warning-surface px-2.5 py-1 text-xs font-semibold text-warning">Pending</span>
                        @elseif($additionalService->payment_status === 'paid')
                            <span class="inline-flex rounded-full bg-success-surface px-2.5 py-1 text-xs font-semibold text-success">Paid</span>
                        @elseif($additionalService->payment_status === 'failed')
                            <span class="inline-flex rounded-full bg-error-surface px-2.5 py-1 text-xs font-semibold text-error">Failed</span>
                        @else
                            <span class="text-sm text-text-muted">—</span>
                        @endif
                    </p>
                </div>

                <div class="mt-4 grid gap-3">
                    <div class="rounded-control border border-divider bg-surface-muted p-3">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-text-muted">Platform margin</p>
                        <p class="mt-1 text-lg font-semibold text-brand-950">{{ number_format($serviceRequestMargin, 2) }}%</p>
                        <p class="mt-0.5 text-[11px] text-text-muted">Applied on service price</p>
                    </div>
                    <div class="rounded-control border border-error/15 bg-error-surface/60 p-3">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-error">Commission amount</p>
                        <p class="mt-1 text-lg font-semibold text-brand-950">₹{{ number_format($platformCommission, 2) }}</p>
                        <p class="mt-0.5 text-[11px] text-text-muted">Deducted by platform</p>
                    </div>
                    <div class="rounded-control border border-success/15 bg-success-surface p-3">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-success">Your earnings</p>
                        <p class="mt-1 text-lg font-semibold text-brand-950">₹{{ number_format($professionalEarning, 2) }}</p>
                        <p class="mt-0.5 text-[11px] text-text-muted">After commission</p>
                    </div>
                </div>

                @if($additionalService->payment_status === 'paid')
                    <div class="mt-4 rounded-control border border-success/20 bg-success-surface px-3 py-3 text-sm text-text-secondary">
                        <p class="font-semibold text-success">Payment received</p>
                        <p class="mt-1">You will receive ₹{{ number_format($professionalEarning, 2) }} ({{ number_format(100 - $serviceRequestMargin, 2) }}% of ₹{{ number_format($finalPrice, 2) }}).</p>
                    </div>
                @endif

                @if($additionalService->professional_payment_status === 'processed')
                    <div class="mt-4 rounded-control border border-divider bg-surface-muted px-3 py-3 text-sm text-text-secondary">
                        <p class="font-semibold text-brand-950">Payment released</p>
                        <p class="mt-1">Processed on {{ $additionalService->professional_payment_processed_at->format('M d, Y h:i A') }}</p>
                        @if($additionalService->payment_transaction_id)
                            <p class="mt-1 break-all">Transaction ID: {{ $additionalService->payment_transaction_id }}</p>
                        @endif
                    </div>
                @endif
            </section>

            <section class="rounded-panel border border-border bg-white p-5 shadow-card" aria-labelledby="actions-title">
                <h2 id="actions-title" class="text-base font-semibold text-brand-950">Actions</h2>
                <div class="mt-4 flex flex-col gap-2">
                    @if(method_exists($additionalService, 'canStartConsultation') && $additionalService->canStartConsultation())
                        <button type="button" class="start-consultation inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-play text-xs" aria-hidden="true"></i> Start consultation
                        </button>
                    @endif

                    @if($additionalService->canSetDeliveryDate())
                        <button type="button" class="set-delivery-date inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:border-brand-primary hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-calendar-plus text-xs" aria-hidden="true"></i> Set delivery date
                        </button>
                    @endif

                    @if($additionalService->canUpdateDeliveryDate() && $additionalService->delivery_date)
                        <button type="button" class="update-delivery inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:border-brand-primary hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-calendar text-xs" aria-hidden="true"></i> Update delivery date
                        </button>
                    @endif

                    @if($additionalService->canBeCompletedByProfessional())
                        <button type="button" class="mark-completed inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-success/30 px-4 text-sm font-semibold text-success transition hover:bg-success-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-check text-xs" aria-hidden="true"></i> Mark completed
                        </button>
                    @endif

                    @if(method_exists($additionalService, 'canCompleteConsultation') && $additionalService->canCompleteConsultation())
                        <button type="button" class="complete-consultation inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:border-brand-primary hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-flag-checkered text-xs" aria-hidden="true"></i> Complete consultation
                        </button>
                    @endif

                    @if($additionalService->consulting_status === 'done' && $additionalService->payment_status === 'paid')
                        <a href="{{ route('professional.additional-services.invoice', $additionalService->id) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:border-brand-primary hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-file-invoice text-xs" aria-hidden="true"></i> View invoice
                        </a>
                        <a href="{{ route('professional.additional-services.invoice.pdf', $additionalService->id) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:border-brand-primary hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-download text-xs" aria-hidden="true"></i> Download invoice
                        </a>
                    @endif

                    @if(
                        !(method_exists($additionalService, 'canStartConsultation') && $additionalService->canStartConsultation())
                        && !$additionalService->canSetDeliveryDate()
                        && !($additionalService->canUpdateDeliveryDate() && $additionalService->delivery_date)
                        && !$additionalService->canBeCompletedByProfessional()
                        && !(method_exists($additionalService, 'canCompleteConsultation') && $additionalService->canCompleteConsultation())
                        && !($additionalService->consulting_status === 'done' && $additionalService->payment_status === 'paid')
                    )
                        <p class="rounded-control border border-divider bg-surface-muted px-3 py-3 text-sm text-text-muted">No actions are available for this service in its current status.</p>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>

{{-- Modals: IDs, field names, and open/close hooks preserved --}}
<div class="custom-modal" id="startConsultationModal">
    <div class="modal-content">
        <div class="modal-header flex items-start justify-between gap-3 border-b border-divider px-5 py-4">
            <div>
                <h5 class="modal-title text-base font-semibold text-brand-950">Start consultation</h5>
                <p class="mt-1 text-xs text-text-muted">Mark this service as in progress.</p>
            </div>
            <button type="button" class="modal-close inline-flex size-9 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" onclick="closeModal('startConsultationModal')" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form id="startConsultationForm">
            <div class="modal-body space-y-3 px-5 py-4">
                <div class="rounded-control border border-info/20 bg-info-surface px-3 py-3 text-sm text-text-secondary">
                    <p class="font-semibold text-info">Heads up</p>
                    <p class="mt-1">This will mark the consultation as “In Progress” and notify the customer.</p>
                </div>
                <div class="space-y-1.5">
                    <label for="consultation_notes" class="block text-xs font-medium text-text-primary">Consultation notes <span class="font-normal text-text-muted">(optional)</span></label>
                    <textarea class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" id="consultation_notes" name="consultation_notes" rows="4" placeholder="Add any initial notes or instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer flex flex-col-reverse gap-2 border-t border-divider px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="action-btn outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted" onclick="closeModal('startConsultationModal')">
                    Cancel
                </button>
                <button type="submit" class="action-btn success inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <i class="fas fa-play text-xs" aria-hidden="true"></i> Start consultation
                </button>
            </div>
        </form>
    </div>
</div>

<div class="custom-modal" id="markCompletedModal">
    <div class="modal-content">
        <div class="modal-header flex items-start justify-between gap-3 border-b border-divider px-5 py-4">
            <div>
                <h5 class="modal-title text-base font-semibold text-brand-950">Mark consultation completed</h5>
                <p class="mt-1 text-xs text-text-muted">Notify the customer to confirm completion.</p>
            </div>
            <button type="button" class="modal-close inline-flex size-9 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" onclick="closeModal('markCompletedModal')" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form id="markCompletedForm">
            <div class="modal-body space-y-3 px-5 py-4">
                <div class="rounded-control border border-success/20 bg-success-surface px-3 py-3 text-sm text-text-secondary">
                    <p class="font-semibold text-success">Confirmation</p>
                    <p class="mt-1">This action notifies the customer to confirm the consultation completion.</p>
                </div>
                <div class="space-y-1.5">
                    <label for="completion_notes" class="block text-xs font-medium text-text-primary">Completion summary <span class="text-error">*</span></label>
                    <textarea class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" id="completion_notes" name="completion_notes" rows="4" placeholder="Provide a summary of the work completed..." required></textarea>
                </div>
            </div>
            <div class="modal-footer flex flex-col-reverse gap-2 border-t border-divider px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="action-btn outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted" onclick="closeModal('markCompletedModal')">
                    Cancel
                </button>
                <button type="submit" class="action-btn success inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <i class="fas fa-check-circle text-xs" aria-hidden="true"></i> Mark completed
                </button>
            </div>
        </form>
    </div>
</div>

<div class="custom-modal" id="updateDeliveryModal">
    <div class="modal-content">
        <div class="modal-header flex items-start justify-between gap-3 border-b border-divider px-5 py-4">
            <div>
                <h5 class="modal-title text-base font-semibold text-brand-950">Update delivery date</h5>
                <p class="mt-1 text-xs text-text-muted">Change the planned delivery date.</p>
            </div>
            <button type="button" class="modal-close inline-flex size-9 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" onclick="closeModal('updateDeliveryModal')" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form id="updateDeliveryForm">
            <div class="modal-body space-y-3 px-5 py-4">
                @if($additionalService->delivery_date)
                    <div class="rounded-control border border-divider bg-surface-muted px-3 py-3 text-sm text-text-secondary">
                        <p class="font-semibold text-brand-950">Current delivery date</p>
                        <p class="mt-1">{{ \Carbon\Carbon::parse($additionalService->delivery_date)->format('M d, Y') }}</p>
                    </div>
                @endif
                <div class="space-y-1.5">
                    <label for="new_delivery_date" class="block text-xs font-medium text-text-primary">New delivery date <span class="text-error">*</span></label>
                    <input type="date" class="form-control min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" id="new_delivery_date" name="delivery_date" min="{{ date('Y-m-d') }}" value="{{ $additionalService->delivery_date }}" required>
                </div>
                <div class="space-y-1.5">
                    <label for="delivery_notes" class="block text-xs font-medium text-text-primary">Reason for date change <span class="text-error">*</span></label>
                    <textarea class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" id="delivery_notes" name="delivery_notes" rows="3" placeholder="Explain why the delivery date is being changed..." required></textarea>
                </div>
            </div>
            <div class="modal-footer flex flex-col-reverse gap-2 border-t border-divider px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="action-btn outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted" onclick="closeModal('updateDeliveryModal')">
                    Cancel
                </button>
                <button type="submit" class="action-btn warning inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <i class="fas fa-calendar text-xs" aria-hidden="true"></i> Update date
                </button>
            </div>
        </form>
    </div>
</div>

<div class="custom-modal" id="setDeliveryDateModal">
    <div class="modal-content">
        <div class="modal-header flex items-start justify-between gap-3 border-b border-divider px-5 py-4">
            <div>
                <h5 class="modal-title text-base font-semibold text-brand-950">Set delivery date</h5>
                <p class="mt-1 text-xs text-text-muted">Customers and admin will be notified.</p>
            </div>
            <button type="button" class="modal-close inline-flex size-9 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" onclick="closeModal('setDeliveryDateModal')" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form id="setDeliveryDateForm">
            <div class="modal-body space-y-3 px-5 py-4">
                <div class="rounded-control border border-info/20 bg-info-surface px-3 py-3 text-sm text-text-secondary">
                    <p class="font-semibold text-info">Note</p>
                    <p class="mt-1">Customers and admin will be notified once you set a delivery date.</p>
                </div>
                <div class="space-y-1.5">
                    <label for="delivery_date" class="block text-xs font-medium text-text-primary">Delivery date <span class="text-error">*</span></label>
                    <input type="date" class="form-control min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" id="delivery_date" name="delivery_date" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                </div>
                <div class="space-y-1.5">
                    <label for="delivery_reason" class="block text-xs font-medium text-text-primary">Reason / notes <span class="text-error">*</span></label>
                    <textarea class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20" id="delivery_reason" name="delivery_reason" rows="3" placeholder="Explain the delivery plan..." required></textarea>
                </div>
            </div>
            <div class="modal-footer flex flex-col-reverse gap-2 border-t border-divider px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="action-btn outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 text-sm font-semibold text-text-secondary transition hover:bg-surface-muted" onclick="closeModal('setDeliveryDateModal')">
                    Cancel
                </button>
                <button type="submit" class="action-btn info inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <i class="fas fa-calendar-plus text-xs" aria-hidden="true"></i> Set date
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const currentServiceId = {{ $additionalService->id }};

    function openModal(modalId) {
        const $modal = $('#' + modalId);
        $modal.addClass('show');
        $('body').css('overflow', 'hidden');
        setTimeout(() => {
            const $firstInput = $modal.find('input:not([type="hidden"]), textarea').first();
            if ($firstInput.length) $firstInput.focus();
        }, 120);
    }

    function closeModal(modalId) {
        $('#' + modalId).removeClass('show');
        $('body').css('overflow', 'auto');
    }

    window.closeModal = closeModal;

    $('.custom-modal').on('click', function(e) {
        if ($(e.target).hasClass('custom-modal')) {
            closeModal(this.id);
        }
    });

    function showButtonLoading($btn, loadingText) {
        const originalText = $btn.html();
        $btn.data('original-text', originalText);
        $btn.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin"></i> ${loadingText}`);
        return originalText;
    }

    function hideButtonLoading($btn) {
        const originalText = $btn.data('original-text');
        $btn.prop('disabled', false).html(originalText);
    }

    $(document).on('click', '.start-consultation', function(e) {
        e.preventDefault();
        openModal('startConsultationModal');
    });

    $('#startConsultationForm').submit(function(e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        showButtonLoading($submitBtn, 'Starting...');

        $.ajax({
            url: `/professional/additional-services/${currentServiceId}/start-consultation`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                consultation_notes: $('#consultation_notes').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal('startConsultationModal');
                    toastr?.success(response.message || 'Consultation started successfully!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr?.error(response.message || 'Failed to start consultation');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                toastr?.error(message);
            },
            complete: function() {
                hideButtonLoading($submitBtn);
            }
        });
    });

    $(document).on('click', '.mark-completed', function(e) {
        e.preventDefault();
        openModal('markCompletedModal');
    });

    $('#markCompletedForm').submit(function(e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        showButtonLoading($submitBtn, 'Completing...');

        $.ajax({
            url: `/professional/additional-services/${currentServiceId}/mark-completed`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                completion_notes: $('#completion_notes').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal('markCompletedModal');
                    toastr?.success(response.message || 'Consultation marked as completed!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr?.error(response.message || 'Failed to mark consultation as completed');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(error) {
                        const message = Array.isArray(error) ? error[0] : error;
                        toastr?.error(message);
                    });
                } else {
                    toastr?.error(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                }
            },
            complete: function() {
                hideButtonLoading($submitBtn);
            }
        });
    });

    $(document).on('click', '.set-delivery-date', function(e) {
        e.preventDefault();
        openModal('setDeliveryDateModal');
    });

    $('#setDeliveryDateForm').submit(function(e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        showButtonLoading($submitBtn, 'Setting...');

        $.ajax({
            url: `/professional/additional-services/${currentServiceId}/set-delivery-date`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                delivery_date: $('#delivery_date').val(),
                delivery_reason: $('#delivery_reason').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal('setDeliveryDateModal');
                    toastr?.success(response.message || 'Delivery date set successfully!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr?.error(response.message || 'Failed to set delivery date');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(error) {
                        const message = Array.isArray(error) ? error[0] : error;
                        toastr?.error(message);
                    });
                } else {
                    toastr?.error(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                }
            },
            complete: function() {
                hideButtonLoading($submitBtn);
            }
        });
    });

    $(document).on('click', '.update-delivery', function(e) {
        e.preventDefault();
        openModal('updateDeliveryModal');
    });

    $('#updateDeliveryForm').submit(function(e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        showButtonLoading($submitBtn, 'Updating...');

        $.ajax({
            url: `/professional/additional-services/${currentServiceId}/update-delivery-date`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                delivery_date: $('#new_delivery_date').val(),
                delivery_notes: $('#delivery_notes').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal('updateDeliveryModal');
                    toastr?.success(response.message || 'Delivery date updated successfully!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr?.error(response.message || 'Failed to update delivery date');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(error) {
                        const message = Array.isArray(error) ? error[0] : error;
                        toastr?.error(message);
                    });
                } else {
                    toastr?.error(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                }
            },
            complete: function() {
                hideButtonLoading($submitBtn);
            }
        });
    });

    $(document).on('click', '.complete-consultation', function(e) {
        e.preventDefault();
        const $btn = $(this);
        if (confirm('Confirm marking this consultation as completed? The customer will be notified.')) {
            showButtonLoading($btn, 'Completing...');
            $.ajax({
                url: `/professional/additional-services/${currentServiceId}/complete-consultation`,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        toastr?.success(response.message || 'Consultation completed successfully!');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        toastr?.error(response.message || 'Failed to complete consultation');
                    }
                },
                error: function(xhr) {
                    toastr?.error(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                },
                complete: function() {
                    hideButtonLoading($btn);
                }
            });
        }
    });

    function validatePrice(priceInput, minPrice) {
        const price = parseFloat(priceInput.val());
        const minimum = parseFloat(minPrice);
        const errorDiv = priceInput.siblings('.price-error');
        if (price < minimum) {
            errorDiv.text('Price cannot be below the allowed minimum').show();
            priceInput.addClass('is-invalid');
            return false;
        }
        errorDiv.hide();
        priceInput.removeClass('is-invalid');
        return true;
    }

    $('#professional_final_price, #new_price').on('input change', function() {
        const minPrice = $(this).attr('min');
        validatePrice($(this), minPrice);
    });

    $('#negotiationResponseForm').submit(function(e) {
        e.preventDefault();
        const priceInput = $('#professional_final_price');
        const minPrice = priceInput.attr('min');
        if (!validatePrice(priceInput, minPrice)) { return false; }
        const $submitBtn = $(this).find('button[type="submit"]');
        showButtonLoading($submitBtn, 'Sending...');

        $.ajax({
            url: `/professional/additional-services/${currentServiceId}/respond-negotiation`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                professional_final_price: priceInput.val(),
                professional_response: $('#professional_response').val(),
                min_price: minPrice
            },
            success: function(response) {
                if (response.success) {
                    toastr?.success(response.message || 'Negotiation response sent!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr?.error(response.message || 'Failed to send negotiation response');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(error) {
                        const message = Array.isArray(error) ? error[0] : error;
                        toastr?.error(message);
                    });
                } else {
                    toastr?.error(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                }
            },
            complete: function() {
                hideButtonLoading($submitBtn);
            }
        });
    });

    $('#priceUpdateForm').submit(function(e) {
        e.preventDefault();
        const priceInput = $('#new_price');
        const minPrice = priceInput.attr('min');
        if (!validatePrice(priceInput, minPrice)) { return false; }
        const $submitBtn = $(this).find('button[type="submit"]');
        showButtonLoading($submitBtn, 'Updating...');

        $.ajax({
            url: `/professional/additional-services/${currentServiceId}/update-price`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                new_price: priceInput.val(),
                reason: $('#price_reason').val(),
                min_price: minPrice
            },
            success: function(response) {
                if (response.success) {
                    toastr?.success(response.message || 'Service price updated successfully!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr?.error(response.message || 'Failed to update service price');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(error) {
                        const message = Array.isArray(error) ? error[0] : error;
                        toastr?.error(message);
                    });
                } else {
                    toastr?.error(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                }
            },
            complete: function() {
                hideButtonLoading($submitBtn);
            }
        });
    });
});
</script>
@endsection
