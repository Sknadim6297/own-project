@extends('customer.layout.layout')

@section('title', 'Additional Service Details | Yuktione')

@section('styles')
<style>
.custom-modal{display:none;align-items:center;justify-content:center;position:fixed;inset:0;z-index:50;background:rgb(20 43 77 / 0.45);padding:1rem}
.custom-modal.show{display:flex}
.custom-modal .custom-modal-content,.custom-modal .modal-content{
    background:#fff;border:1px solid #E2E9EE;border-radius:1rem;box-shadow:0 18px 42px -24px rgb(20 43 77 / 0.28);
    max-height:90vh;max-width:32rem;overflow:auto;width:100%
}
.custom-modal .modal-header{align-items:center;border-bottom:1px solid #E2E9EE;display:flex;justify-content:space-between;padding:1rem 1.25rem}
.custom-modal .modal-title{color:#142B4D;font-size:1.05rem;font-weight:700;margin:0}
.custom-modal .modal-close{background:transparent;border:0;color:#718096;cursor:pointer;font-size:1rem}
.custom-modal .modal-body{padding:1.25rem}
.custom-modal .modal-footer{border-top:1px solid #E2E9EE;display:flex;gap:.75rem;justify-content:flex-end;padding:1rem 1.25rem}
.yk-as-price-table{width:100%}
.yk-as-price-table td{border-bottom:1px solid #E2E9EE;color:#142B4D;font-size:.9rem;padding:.55rem 0}
.yk-as-price-table tr:last-child td{border-bottom:0}
.yk-as-actions .pay-now-btn,
.yk-as-actions .negotiate-btn,
.yk-as-actions .confirm-completion-btn{
    align-items:center;border-radius:.75rem;display:inline-flex;font-weight:600;gap:.5rem;justify-content:center;min-height:2.75rem;padding:.65rem 1rem;width:100%
}
.yk-as-actions .pay-now-btn{background:#36877F;border:1px solid #36877F;color:#fff}
.yk-as-actions .pay-now-btn:hover{background:#286E67;border-color:#286E67;color:#fff}
.yk-as-actions .negotiate-btn{background:#fff;border:1px solid #E2E9EE;color:#142B4D}
.yk-as-actions .negotiate-btn:hover{border-color:#36877F;color:#36877F}
.yk-as-actions .confirm-completion-btn{background:#36877F;border:1px solid #36877F;color:#fff}
</style>
@endsection

@section('content')
@php
    $originalProfessionalBasePrice = $additionalService->original_professional_price;
    $originalCGST = $originalProfessionalBasePrice * 0.09;
    $originalSGST = $originalProfessionalBasePrice * 0.09;
    $originalTotalGST = $originalCGST + $originalSGST;
    $originalFinalPrice = $originalProfessionalBasePrice + $originalTotalGST;
    $hasNegotiation = in_array($additionalService->negotiation_status, ['user_negotiated', 'admin_responded'], true);

    if ($hasNegotiation) {
        if ($additionalService->negotiation_status === 'admin_responded') {
            $negotiatedBasePrice = $additionalService->admin_final_negotiated_price;
        } else {
            $negotiatedBasePrice = $additionalService->user_negotiated_price;
        }
        $discountAmount = $originalProfessionalBasePrice - $negotiatedBasePrice;
        $discountPercent = ($originalProfessionalBasePrice > 0) ? (($discountAmount / $originalProfessionalBasePrice) * 100) : 0;
        $negotiatedCGST = $negotiatedBasePrice * 0.09;
        $negotiatedSGST = $negotiatedBasePrice * 0.09;
        $negotiatedTotalGST = $negotiatedCGST + $negotiatedSGST;
        $negotiatedFinalPrice = $negotiatedBasePrice + $negotiatedTotalGST;
    }

    $statusLabel = $additionalService->status_text ?? ucfirst((string) ($additionalService->payment_status ?? 'pending'));
@endphp

<div class="mx-auto grid max-w-7xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'Additional Services', 'url' => route('user.additional-services.index')],
        ['label' => 'Details'],
    ]" />

    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Your services</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">Additional Service Details</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
                Review your add-on request, track negotiation updates, and complete payment when you are satisfied with the offer.
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-secondary">
            {{ $statusLabel }}
        </span>
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6">
                <div class="mb-4 flex items-center gap-2 border-b border-divider pb-3">
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-primary">
                        <i class="fas fa-info" aria-hidden="true"></i>
                    </span>
                    <h2 class="font-display text-lg font-semibold text-brand-secondary">Service overview</h2>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Service name</dt>
                        <dd class="mt-1 text-sm font-medium text-text-primary">{{ $additionalService->service_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Status</dt>
                        <dd class="mt-1 text-sm font-medium text-text-primary">{{ $statusLabel }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Reason</dt>
                        <dd class="mt-1 text-sm font-medium text-text-primary">{{ $additionalService->reason }}</dd>
                    </div>
                    @if($additionalService->delivery_date)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Delivery date</dt>
                            <dd class="mt-1 text-sm font-medium text-text-primary">{{ \Carbon\Carbon::parse($additionalService->delivery_date)->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </article>

            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6 price-breakdown">
                <div class="mb-4 rounded-control bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-secondary card-header">
                    Professional initial price breakdown
                </div>
                <table class="yk-as-price-table price-table">
                    <tbody>
                        <tr><td><strong>Description</strong></td><td class="text-end"><strong>Amount</strong></td></tr>
                        <tr><td><strong>Base price</strong></td><td class="text-end"><strong>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($originalProfessionalBasePrice), 2) }}</strong></td></tr>
                        <tr><td colspan="2"><strong>Tax breakdown</strong></td></tr>
                        <tr><td>CGST 9%</td><td class="text-end">{{ getCurrencySymbol() }}{{ number_format(convertCurrency($originalCGST), 2) }}</td></tr>
                        <tr><td>SGST 9%</td><td class="text-end">{{ getCurrencySymbol() }}{{ number_format(convertCurrency($originalSGST), 2) }}</td></tr>
                        <tr><td><strong>Total GST (18%)</strong></td><td class="text-end"><strong>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($originalTotalGST), 2) }}</strong></td></tr>
                        <tr><td><strong>Final amount</strong></td><td class="text-end"><strong>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($originalFinalPrice), 2) }}</strong></td></tr>
                    </tbody>
                </table>
            </article>

            @if($hasNegotiation)
            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6 negotiated-price-card">
                <div class="mb-4 flex flex-wrap items-center gap-2 rounded-control bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-secondary card-header">
                    Negotiated price breakdown
                    @if($additionalService->negotiation_status === 'admin_responded')
                        <span class="badge badge-paid rounded-full bg-brand-primary px-2 py-0.5 text-xs text-white">Accepted</span>
                    @else
                        <span class="badge badge-pending rounded-full border border-border bg-surface px-2 py-0.5 text-xs text-text-secondary">Pending</span>
                    @endif
                </div>
                <table class="yk-as-price-table price-table">
                    <tbody>
                        <tr><td>Base price</td><td class="text-end">{{ getCurrencySymbol() }}{{ number_format(convertCurrency($originalProfessionalBasePrice), 2) }}</td></tr>
                        <tr><td>Negotiation discount ({{ number_format($discountPercent, 1) }}%)</td><td class="text-end">− {{ getCurrencySymbol() }}{{ number_format(convertCurrency($discountAmount), 2) }}</td></tr>
                        <tr><td><strong>New price after discount</strong></td><td class="text-end"><strong>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($negotiatedBasePrice), 2) }}</strong></td></tr>
                        <tr><td>GST 18% (9%+9%)</td><td class="text-end">{{ getCurrencySymbol() }}{{ number_format(convertCurrency($negotiatedTotalGST), 2) }}</td></tr>
                        <tr><td><strong>Final price customer pays</strong></td><td class="text-end"><strong>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($negotiatedFinalPrice), 2) }}</strong></td></tr>
                    </tbody>
                </table>
                <div class="mt-4 text-sm text-text-secondary">
                    @if($additionalService->negotiation_status === 'admin_responded')
                        @php $totalSavings = $originalFinalPrice - $negotiatedFinalPrice; @endphp
                        <p>Negotiated price accepted — ready for payment.</p>
                        <p class="mt-1 font-semibold text-brand-primary">You save {{ getCurrencySymbol() }}{{ number_format(convertCurrency($totalSavings), 2) }} total.</p>
                    @else
                        <p>Your negotiated offer is pending professional review.</p>
                        <p class="mt-1">Once accepted, you'll pay {{ getCurrencySymbol() }}{{ number_format(convertCurrency($negotiatedFinalPrice), 2) }}.</p>
                    @endif
                </div>
            </article>
            @endif

            @if($additionalService->negotiation_status !== 'none')
            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6">
                <h2 class="mb-3 font-display text-lg font-semibold text-brand-secondary">Negotiation status</h2>
                @if($additionalService->negotiation_status === 'user_negotiated')
                    @php
                        $negotiationHandler = $additionalService->professional_id ? 'professional' : 'admin';
                        $handlerLabel = $negotiationHandler === 'professional' ? 'Professional' : 'Admin';
                    @endphp
                    <div class="rounded-control border border-border bg-surface-muted/40 p-4 text-sm">
                        <p><span class="font-semibold text-brand-secondary">Your offer:</span> ₹{{ number_format($additionalService->user_negotiated_price, 2) }}</p>
                        <p class="mt-2"><span class="font-semibold text-brand-secondary">Reason:</span> {{ $additionalService->user_negotiation_reason }}</p>
                        <p class="mt-3 text-text-secondary">Waiting for {{ strtolower($handlerLabel) }} response.</p>
                    </div>
                @elseif($additionalService->negotiation_status === 'admin_responded')
                    @php
                        $modifierLabel = $additionalService->professional_id ? 'Professional' : 'Admin';
                        $finalNegotiatedPrice = $additionalService->admin_final_negotiated_price;
                    @endphp
                    <div class="rounded-control border border-border bg-surface-muted/40 p-4 text-sm">
                        <p><span class="font-semibold text-brand-secondary">Your base offer:</span> ₹{{ number_format($additionalService->user_negotiated_price, 2) }}</p>
                        <p class="mt-2"><span class="font-semibold text-brand-secondary">{{ $modifierLabel }} final base offer:</span> ₹{{ number_format($finalNegotiatedPrice, 2) }}</p>
                        <p class="mt-2"><span class="font-semibold text-brand-secondary">Response:</span> {{ $additionalService->admin_negotiation_response }}</p>
                    </div>
                @endif
            </article>
            @endif
        </div>

        <aside class="grid gap-6 content-start">
            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6">
                <h2 class="mb-4 font-display text-lg font-semibold text-brand-secondary">Professional</h2>
                <div class="info-card space-y-2 text-sm">
                    <p class="font-semibold text-brand-secondary">{{ $additionalService->professional->name }}</p>
                    <p class="text-text-secondary"><i class="fas fa-envelope me-2" aria-hidden="true"></i>{{ $additionalService->professional->email }}</p>
                    <p class="text-text-secondary"><i class="fas fa-phone me-2" aria-hidden="true"></i>{{ $additionalService->professional->phone }}</p>
                    <p class="text-text-secondary"><i class="fas fa-bookmark me-2" aria-hidden="true"></i>Booking #{{ $additionalService->booking_id }}</p>
                </div>
            </article>

            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6">
                <h2 class="mb-4 font-display text-lg font-semibold text-brand-secondary">Quick actions</h2>
                <div class="d-grid gap-3 quick-actions yk-as-actions grid gap-3">
                    @if($additionalService->payment_status === 'pending')
                        @if($additionalService->negotiation_status === 'user_negotiated')
                            <button class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface-muted px-3 text-sm font-semibold text-text-muted" disabled>
                                Payment pending review
                            </button>
                            <p class="text-center text-xs text-text-muted">Payment unlocks after the negotiation response.</p>
                        @else
                            <button class="btn btn-success pay-now-btn" data-id="{{ $additionalService->id }}">
                                <i class="fas fa-credit-card" aria-hidden="true"></i>
                                Pay now — ₹{{ number_format($additionalService->final_price, 2) }}
                            </button>
                        @endif
                    @endif

                    @if($additionalService->canBeNegotiated())
                        <button class="btn btn-warning negotiate-btn" data-id="{{ $additionalService->id }}">
                            <i class="fas fa-handshake" aria-hidden="true"></i>
                            Negotiate price
                        </button>
                    @endif

                    @if($additionalService->consulting_status === 'done' && !$additionalService->customer_confirmed_at)
                        <button class="btn btn-primary confirm-completion-btn" data-id="{{ $additionalService->id }}">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            Confirm completion
                        </button>
                    @endif

                    @if($additionalService->consulting_status === 'done' && $additionalService->payment_status === 'paid')
                        <a href="{{ route('user.additional-services.invoice', $additionalService->id) }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white">
                            View invoice
                        </a>
                        <a href="{{ route('user.additional-services.invoice.pdf', $additionalService->id) }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary" target="_blank">
                            Download PDF
                        </a>
                    @endif

                    <a href="{{ route('user.additional-services.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary">
                        Back to list
                    </a>
                </div>
            </article>

            @if($additionalService->isPaid())
            <article class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6">
                <h2 class="mb-3 font-display text-lg font-semibold text-brand-secondary">Payment</h2>
                <div class="info-card payment-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="badge badge-paid rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-primary">Paid</span>
                        <p class="text-lg font-semibold text-brand-secondary">₹{{ number_format($additionalService->final_price, 2) }}</p>
                    </div>
                    @if($additionalService->payment_id)
                        <p class="text-xs text-text-muted">Transaction ID</p>
                        <code class="text-sm text-text-primary">{{ $additionalService->payment_id }}</code>
                    @endif
                </div>
            </article>
            @endif
        </aside>
    </div>
</div>

<div class="custom-modal" id="negotiationModal">
    <div class="modal-content custom-modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Negotiate service price</h5>
            <button type="button" class="modal-close" onclick="closeModal('negotiationModal')" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form id="negotiationForm">
            <div class="modal-body">
                @php
                    $professional = $additionalService->professional;
                    $maxDiscountPercent = $professional->service_request_offset ?? 10;
                    $basePrice = $additionalService->getEffectiveBasePrice();
                    $minBasePrice = $minPrice ?? round($basePrice * (1 - ($maxDiscountPercent / 100)), 2);
                    $minFinal = round($minBasePrice * 1.18, 2);
                    $currentFinal = round($basePrice * 1.18, 2);
                @endphp

                <div class="mb-4 grid gap-3 rounded-control border border-border bg-brand-50 p-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-text-muted">Current base price</p>
                        <p class="font-semibold text-brand-secondary">₹{{ number_format($basePrice, 2) }}</p>
                        <p class="text-xs text-text-muted">Total with GST: ₹{{ number_format($currentFinal, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-muted">Negotiation range</p>
                        <p class="font-semibold text-brand-secondary">₹{{ number_format($minBasePrice, 2) }} – ₹{{ number_format($basePrice, 2) }}</p>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="negotiated_price" class="form-label mb-1 block text-sm font-semibold text-brand-secondary">
                        Your proposed base price (₹) *
                        <span class="mt-1 block text-xs font-normal text-text-muted">
                            You are negotiating the base price (currently ₹{{ number_format($basePrice, 2) }}). GST (18%) will be calculated on your negotiated price.
                        </span>
                    </label>
                    <input type="number" class="form-control form-control-lg w-full rounded-control border border-border px-3 py-2" id="negotiated_price" name="negotiated_price"
                           data-min-price="{{ $minBasePrice }}"
                           data-max-price="{{ $basePrice }}"
                           min="{{ $minBasePrice }}"
                           max="{{ $basePrice }}"
                           step="0.01"
                           required
                           placeholder="Enter amount between ₹{{ number_format($minBasePrice, 2) }} - ₹{{ number_format($basePrice, 2) }}">
                </div>

                <div class="mb-3">
                    <label for="negotiation_reason" class="form-label mb-1 block text-sm font-semibold text-brand-secondary">
                        Reason for negotiation *
                    </label>
                    <textarea class="form-control w-full rounded-control border border-border px-3 py-2" id="negotiation_reason" name="negotiation_reason"
                              rows="4" maxlength="1000" required
                              placeholder="Please explain why you're requesting a price reduction..."></textarea>
                    <div class="form-text mt-1 text-xs text-text-muted">
                        <span id="char-count">0</span>/1000 characters
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary inline-flex min-h-10 items-center rounded-control border border-border px-3 text-sm font-semibold" onclick="closeModal('negotiationModal')">Cancel</button>
                <button type="submit" class="btn btn-warning inline-flex min-h-10 items-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white">Submit negotiation</button>
            </div>
        </form>
    </div>
</div>

<div class="custom-modal" id="confirmCompletionModal">
    <div class="modal-content custom-modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Confirm service completion</h5>
            <button type="button" class="modal-close" onclick="closeModal('confirmCompletionModal')" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="mb-4 text-center modal-illustration">
                <i class="fas fa-check-circle modal-illustration-icon text-3xl text-brand-primary" aria-hidden="true"></i>
            </div>
            <h6 class="mb-3 text-center font-semibold text-brand-secondary">Service completion confirmation</h6>
            <p class="text-center text-sm text-text-secondary">The professional has marked this consultation as completed. Do you confirm that the service has been delivered satisfactorily?</p>
            <div class="mt-4 rounded-control border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Once confirmed, this action cannot be undone and the service will be marked as fully completed.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary inline-flex min-h-10 items-center rounded-control border border-border px-3 text-sm font-semibold" onclick="closeModal('confirmCompletionModal')">Not yet</button>
            <button type="button" class="btn btn-success inline-flex min-h-10 items-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white" id="confirmCompletionBtn">Yes, confirm completion</button>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
$(document).ready(function() {
    let currentServiceId = {{ $additionalService->id }};

    // Custom Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    // Make closeModal available globally
    window.closeModal = closeModal;

    // Close modal when clicking outside
    $('.custom-modal').click(function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });

    // Close modal with Escape key
    $(document).keydown(function(e) {
        if (e.keyCode === 27) {
            $('.custom-modal.show').each(function() {
                closeModal(this.id);
            });
        }
    });

    // Character count for negotiation reason
    $('#negotiation_reason').on('input', function() {
        const length = $(this).val().length;
        $('#char-count').text(length);
        
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Pay Now functionality with enhanced UI feedback
    $('.pay-now-btn').click(function() {
        const serviceId = $(this).data('id');
        const $btn = $(this);
        const originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: `/user/additional-services/${serviceId}/create-payment-order`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    const rkey = response.key || response.razorpay_key || '{{ config('services.razorpay.key') }}';
                    const ramount = response.amount || response.order_amount || 0; // amount in paise
                    const rcurrency = response.currency || 'INR';
                    const rorderId = response.order_id || response.orderId || response.order?.id || null;

                    if (!rorderId || !ramount) {
                        toastr.error('Invalid payment order returned from server. Please try again.');
                        $btn.prop('disabled', false).html(originalText);
                        return;
                    }

                    const options = {
                        key: rkey,
                        amount: ramount,
                        currency: rcurrency,
                        name: '{{ config('app.name') }}',
                        description: response.service_name || response.description || 'Additional Service Payment',
                        order_id: rorderId,
                        handler: function (razorpayResponse) {
                            handlePaymentSuccess(serviceId, razorpayResponse);
                        },
                        prefill: {
                            name: '{{ Auth::guard('user')->user()->name }}',
                            email: '{{ Auth::guard('user')->user()->email }}',
                            contact: '{{ Auth::guard('user')->user()->phone }}'
                        },
                        theme: {
                            color: '#36877F'
                        },
                        modal: {
                            ondismiss: function() {
                                toastr.info('Payment cancelled by user');
                                $btn.prop('disabled', false).html(originalText);
                            }
                        }
                    };
                    
                    const rzp = new Razorpay(options);
                    rzp.open();
                    
                    rzp.on('payment.failed', function (response) {
                        toastr.error('Payment failed: ' + response.error.description);
                    });
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to initiate payment. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    function handlePaymentSuccess(serviceId, paymentResponse) {
        // Show success animation
        toastr.success('Payment initiated successfully! Verifying...');
        
        $.ajax({
            url: `/user/additional-services/${serviceId}/payment-success`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                razorpay_payment_id: paymentResponse.razorpay_payment_id,
                razorpay_order_id: paymentResponse.razorpay_order_id,
                razorpay_signature: paymentResponse.razorpay_signature
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Payment verification failed. Please contact support.');
            }
        });
    }

    // Negotiate functionality with custom modal
    $('.negotiate-btn').click(function() {
        openModal('negotiationModal');
        // Reset form
        $('#negotiationForm')[0].reset();
        $('#char-count').text('0');
    });

    // Submit negotiation with enhanced feedback
    $('#negotiationForm').submit(function(e) {
        e.preventDefault();
        
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        
        $.ajax({
            url: `/user/additional-services/${currentServiceId}/negotiate`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                negotiated_price: $('#negotiated_price').val(),
                negotiation_reason: $('#negotiation_reason').val()
            },
            success: function(response) {
                if (response.success) {
                    closeModal('negotiationModal');
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                // Handle validation errors
                const serverMessage = xhr.responseJSON?.message;
                const errors = xhr.responseJSON?.errors;
                
                if (serverMessage) {
                    toastr.error(serverMessage);
                } else if (errors) {
                    Object.values(errors).forEach(function(error) {
                        toastr.error(error[0]);
                    });
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Confirm completion with custom modal
    $('.confirm-completion-btn').click(function() {
        // ensure currentServiceId is set (show page has it prefilled, but set from button for consistency)
        currentServiceId = $(this).data('id') || currentServiceId;
        console.log('Opening confirm modal for service:', currentServiceId);
        openModal('confirmCompletionModal');
    });

    $('#confirmCompletionBtn').click(function() {
        const $btn = $(this);
        const originalText = $btn.html();
        
        // Debug log
        console.log('Confirm completion clicked for serviceId:', currentServiceId);

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Confirming...');
        
        $.ajax({
            url: `/user/additional-services/${currentServiceId}/confirm-consultation`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('confirm-consultation success response:', response);
                if (response.success) {
                    closeModal('confirmCompletionModal');
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                console.error('confirm-consultation error:', xhr);
                const serverMessage = xhr.responseJSON?.message;
                if (serverMessage) {
                    toastr.error(serverMessage);
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Add smooth animations to cards
    $('.card').each(function(index) {
        $(this).css({
            'animation-delay': (index * 0.1) + 's'
        });
    });

    // Price input validation with real-time feedback
    $('#negotiated_price').on('input', function() {
        const value = parseFloat($(this).val());
        const minPrice = parseFloat($(this).data('min-price'));
        const max = parseFloat($(this).attr('max'));
        const $errorDiv = $('#price-error');
        
        // Real-time GST calculation display
        if (value && !isNaN(value)) {
            const finalAmount = value * 1.18; // Add 18% GST
            const savings = max - value; // Savings from current base
            const savingsPercent = ((savings / max) * 100).toFixed(2);
            
            // Update or create a real-time display
            let $display = $('#real-time-calc');
            if ($display.length === 0) {
                $display = $('<div id="real-time-calc" class="alert alert-success mt-2" style="border-left: 4px solid #198754;"></div>');
                $(this).after($display);
            }
            
            if (value >= minPrice && value <= max) {
                $display.html(`
                    <div class="text-center">
                        <small class="text-muted d-block">Your proposed base price</small>
                        <strong style="font-size: 1.1em;">₹${value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                        <hr class="my-2">
                        <small class="text-success d-block"><strong>Final amount you'll pay (with GST):</strong></small>
                        <strong class="text-success" style="font-size: 1.2em;">₹${finalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                        <small class="d-block text-success mt-1"><i class="fas fa-piggy-bank"></i> You save: <strong>₹${savings.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${savingsPercent}%)</strong></small>
                    </div>
                `).show();
            } else {
                $display.hide();
            }
        }
    });
});
</script>
@endsection