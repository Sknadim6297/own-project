@extends('customer.layout.layout')

@section('styles')
<style>
    .custom-modal {
        display: none;
    }

    .custom-modal.show {
        display: flex;
    }
</style>
@endsection

@section('content')
@php
    $hasActiveFilters = request()->filled('start_date')
        || request()->filled('end_date')
        || request()->filled('status')
        || request()->filled('professional');
@endphp

<div class="mx-auto grid max-w-7xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'Additional Services'],
    ]" />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Your services</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">Additional Services</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
                Track additional consultation services, payments and service progress in one place.
            </p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-secondary">
            <x-tazen-v2.panel.icon name="spark" class="size-4 text-brand-primary" />
            {{ $additionalServices->total() }} {{ Str::plural('service', $additionalServices->total()) }}
        </span>
    </header>

    <section class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6" aria-labelledby="additional-service-filters-heading">
        <div class="mb-5">
            <h2 id="additional-service-filters-heading" class="font-display text-card-title font-semibold text-brand-secondary">Filter Services</h2>
            <p class="mt-1 text-sm text-text-secondary">Narrow results by date, service status or professional.</p>
        </div>

        <form action="{{ route('user.additional-services.index') }}" method="GET" id="filter-form" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="grid gap-1.5">
                <label for="start_date" class="text-sm font-semibold text-text-primary">Start date</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}"
                    class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
            </div>
            <div class="grid gap-1.5">
                <label for="end_date" class="text-sm font-semibold text-text-primary">End date</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}"
                    class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
            </div>
            <div class="grid gap-1.5">
                <label for="status" class="text-sm font-semibold text-text-primary">Status</label>
                <select id="status" name="status" class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(request('status') === 'pending')>Payment pending</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>In progress</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                </select>
            </div>
            <div class="grid gap-1.5">
                <label for="professional" class="text-sm font-semibold text-text-primary">Professional</label>
                <select id="professional" name="professional" class="min-h-11 w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text-primary focus-visible:border-brand-primary">
                    <option value="">All professionals</option>
                    @foreach($professionals as $professional)
                        <option value="{{ $professional->id }}" @selected((string) request('professional') === (string) $professional->id)>
                            {{ $professional->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row xl:col-span-4">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-brand-primary bg-brand-primary px-4 text-sm font-semibold text-white transition-colors hover:border-brand-800 hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    <x-tazen-v2.panel.icon name="search" class="size-4" />
                    Apply Filters
                </button>
                <a href="{{ route('user.additional-services.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-panel border border-border bg-surface shadow-card" aria-labelledby="additional-service-results-heading">
        <div class="flex flex-col gap-1 border-b border-divider px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="additional-service-results-heading" class="font-display text-card-title font-semibold text-brand-secondary">Your additional services</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    {{ $additionalServices->total() }} matching {{ Str::plural('service', $additionalServices->total()) }}
                    <span class="text-text-muted">· Sorted by most recent</span>
                </p>
            </div>
        </div>

        @if($additionalServices->isEmpty())
            <div class="p-5 sm:p-8">
                <x-tazen-v2.panel.empty-state
                    icon="spark"
                    :title="$hasActiveFilters ? 'No services match these filters' : 'No Additional Services Found'"
                    :description="$hasActiveFilters
                        ? 'Try adjusting your filters or clear them to see all of your additional services.'
                        : 'Professionals may offer extra services related to your existing bookings. Any available services will appear here.'"
                >
                    @if($hasActiveFilters)
                        <x-slot:actions>
                            <a href="{{ route('user.additional-services.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary hover:bg-surface-muted">
                                Clear Filters
                            </a>
                        </x-slot:actions>
                    @endif
                </x-tazen-v2.panel.empty-state>
            </div>
        @else
            <div class="grid gap-4 p-4 sm:p-5 xl:grid-cols-2">
                @foreach($additionalServices as $service)
                    @php
                        if ($service->consulting_status === 'in_progress') {
                            $consultationStatus = 'In Progress';
                            $consultationStatusClass = 'border-info bg-info-surface text-info';
                        } elseif ($service->consulting_status === 'done' && !$service->customer_confirmed_at) {
                            $consultationStatus = 'Awaiting Confirmation';
                            $consultationStatusClass = 'border-warning bg-warning-surface text-warning';
                        } elseif ($service->isConsultationCompleted()) {
                            $consultationStatus = 'Completed';
                            $consultationStatusClass = 'border-success bg-success-surface text-success';
                        } else {
                            $consultationStatus = 'Active';
                            $consultationStatusClass = 'border-brand-200 bg-brand-50 text-brand-secondary';
                        }

                        $paymentStatusClasses = [
                            'pending' => 'border-warning bg-warning-surface text-warning',
                            'paid' => 'border-success bg-success-surface text-success',
                            'failed' => 'border-error bg-error-surface text-error',
                        ];
                    @endphp
                    <article class="flex min-w-0 flex-col rounded-card border border-border bg-surface p-4 shadow-card transition-shadow hover:shadow-popover sm:p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="break-words font-display text-base font-semibold text-brand-secondary">{{ $service->service_name }}</h3>
                                <p class="mt-1 text-sm text-text-secondary">{{ $service->professional?->name ?? 'Professional unavailable' }}</p>
                            </div>
                            <span class="inline-flex w-fit shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $consultationStatusClass }}">
                                {{ $consultationStatus }}
                            </span>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-divider pt-4 text-sm">
                            <div class="min-w-0">
                                <dt class="text-xs font-medium text-text-muted">Related booking</dt>
                                <dd class="mt-1 font-semibold text-text-primary">#{{ $service->booking_id }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-medium text-text-muted">Created date</dt>
                                <dd class="mt-1 text-text-primary">{{ $service->created_at?->format('M d, Y') ?? '—' }}</dd>
                            </div>
                            @if($service->delivery_date)
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-text-muted">Delivery date</dt>
                                    <dd class="mt-1 text-text-primary">{{ $service->delivery_date->format('M d, Y') }}</dd>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <dt class="text-xs font-medium text-text-muted">Total price</dt>
                                <dd class="mt-1 font-semibold text-brand-secondary">{{ getCurrencySymbol() }}{{ number_format(convertCurrency($service->final_price), 2) }}</dd>
                                @if($service->negotiation_status !== 'none')
                                    <dd class="mt-1 text-xs font-medium text-warning">Negotiated</dd>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-medium text-text-muted">Payment status</dt>
                                <dd class="mt-1">
                                    @if(isset($paymentStatusClasses[$service->payment_status]))
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $paymentStatusClasses[$service->payment_status] }}">
                                            {{ ucfirst($service->payment_status) }}
                                        </span>
                                    @else
                                        <span class="text-text-muted">—</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex flex-wrap gap-2 border-t border-divider pt-4">
                            @if($service->payment_status === 'pending')
                                <button type="button" class="pay-now-btn inline-flex min-h-10 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white hover:bg-brand-800"
                                    data-id="{{ $service->id }}" data-amount="{{ $service->final_price }}">
                                    Pay {{ getCurrencySymbol() }}{{ number_format(convertCurrency($service->final_price), 2) }}
                                </button>
                            @endif
                            <a href="{{ route('user.additional-services.show', $service->id) }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary hover:bg-surface-muted">
                                View Details
                            </a>

                            @if($service->payment_status === 'paid')
                                @if($service->consulting_status === 'not_started')
                                    <span class="inline-flex min-h-10 items-center rounded-control border border-warning bg-warning-surface px-3 text-xs font-semibold text-warning">Awaiting Professional</span>
                                @elseif($service->consulting_status === 'in_progress')
                                    <span class="inline-flex min-h-10 items-center rounded-control border border-info bg-info-surface px-3 text-xs font-semibold text-info">Consulting in Progress</span>
                                @elseif($service->consulting_status === 'done' && !$service->customer_confirmed_at)
                                    <button type="button" class="confirm-completion-btn inline-flex min-h-10 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white hover:bg-brand-800" data-id="{{ $service->id }}">
                                        Confirm Completion
                                    </button>
                                @elseif($service->customer_confirmed_at)
                                    <span class="inline-flex min-h-10 items-center rounded-control border border-success bg-success-surface px-3 text-xs font-semibold text-success">Completed</span>
                                @endif
                            @endif

                            @if($service->negotiation_status === 'user_negotiated')
                                <span class="inline-flex min-h-10 items-center rounded-control border border-warning bg-warning-surface px-3 text-xs font-semibold text-warning">Negotiation Pending</span>
                            @elseif($service->negotiation_status === 'admin_responded')
                                <span class="inline-flex min-h-10 items-center rounded-control border border-info bg-info-surface px-3 text-xs font-semibold text-info">Professional Responded</span>
                            @endif

                            @if($service->consulting_status === 'done' && $service->payment_status === 'paid')
                                <a href="{{ route('user.additional-services.invoice', $service->id) }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary hover:bg-surface-muted">
                                    View Invoice
                                </a>
                                <a href="{{ route('user.additional-services.invoice.pdf', $service->id) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary hover:bg-surface-muted">
                                    Download PDF
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if($additionalServices->hasPages())
                <div class="border-t border-divider px-4 py-4 sm:px-6">
                    {{ $additionalServices->links() }}
                </div>
            @endif
        @endif
    </section>
</div>

<div id="confirmCompletionModal" class="custom-modal fixed inset-0 z-50 items-center justify-center overflow-y-auto bg-brand-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-completion-title">
    <div class="mx-auto w-full max-w-md rounded-panel border border-border bg-surface p-5 shadow-popover sm:p-6">
        <h2 id="confirm-completion-title" class="font-display text-card-title font-semibold text-brand-secondary">Confirm service completion</h2>
        <p class="mt-2 text-sm leading-6 text-text-secondary">Confirm that the additional consultation service has been completed.</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-surface px-4 text-sm font-semibold text-text-primary hover:bg-surface-muted" onclick="closeModal('confirmCompletionModal')">Cancel</button>
            <button type="button" id="confirmCompletionBtn" class="inline-flex min-h-10 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-4 text-sm font-semibold text-white hover:bg-brand-800">Confirm</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
$(document).ready(function() {
    let currentServiceId = null;

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

    // Remove negotiate button functionality from index page
    // Negotiation should only be available on the detail page
    
    // Enhanced Pay Now button with amount display
    $('.pay-now-btn').click(function() {
        const serviceId = $(this).data('id');
        const amount = $(this).data('amount');
        const $btn = $(this);
        const originalText = $btn.html();
        
        // Show loading state
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Add visual feedback
        toastr.info('Preparing payment for ₹' + parseFloat(amount).toFixed(2) + '...');
        
        $.ajax({
            url: `/user/additional-services/${serviceId}/create-payment-order`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Controller returns: { success, order_id, amount, currency, key, service_name }
                    const rkey = response.key || response.razorpay_key || '{{ config('services.razorpay.key') }}';
                    const ramount = response.amount || response.order_amount || 0; // amount in paise
                    const rcurrency = response.currency || 'INR';
                    const rorderId = response.order_id || response.orderId || null;

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
                            color: '#28a745'
                        },
                        modal: {
                            ondismiss: function() {
                                toastr.warning('Payment cancelled by user');
                                $btn.prop('disabled', false).html(originalText);
                            }
                        }
                    };

                    const rzp = new Razorpay(options);
                    rzp.open();
                    
                    rzp.on('payment.failed', function (response) {
                        toastr.error('Payment failed: ' + response.error.description);
                        $btn.prop('disabled', false).html(originalText);
                    });
                } else {
                    toastr.error(response.message);
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Unable to initiate payment. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
                $btn.prop('disabled', false).html(originalText);
            }
    });

    // Confirm completion button with enhanced feedback
    $('.confirm-completion-btn').click(function() {
        currentServiceId = $(this).data('id');
        console.log('Opening confirm modal for service (index):', currentServiceId);
        openModal('confirmCompletionModal');
    });

    // Handle payment success with enhanced feedback and consultation enabling
    function handlePaymentSuccess(serviceId, paymentResponse) {
        toastr.success('Payment successful! Verifying and enabling consultation...');
        
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
                    toastr.success(' ' + response.message + ' Professional can now start consulting!');
                    
                    // Show success animation and reload
                    setTimeout(() => {
                        toastr.info(' Refreshing page to show updated status...');
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Payment verification failed. Please contact support.');
            }
        });
    }
    // Confirm completion with enhanced feedback
    $('#confirmCompletionBtn').click(function() {
        if (!currentServiceId) return;
        
        const $btn = $(this);
        const originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Confirming...');
        
        $.ajax({
            url: `/user/additional-services/${currentServiceId}/confirm-consultation`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    closeModal('confirmCompletionModal');
                    toastr.success(' ' + response.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('An error occurred. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Enhanced mobile interactions
    if (window.innerWidth <= 768) {
        // Improve touch interactions on mobile
        $('.btn').on('touchstart', function() {
            $(this).addClass('active');
        }).on('touchend', function() {
            $(this).removeClass('active');
        });
    }

    // Add smooth animations to cards and enhance Pay Now button visibility
    $('.card').each(function(index) {
        $(this).css({
            'animation-delay': (index * 0.1) + 's'
        });
    });
    
    // Highlight Pay Now buttons for better visibility
    $('.pay-now-btn').addClass('pulse-animation');
});
});
</script>
@endsection
