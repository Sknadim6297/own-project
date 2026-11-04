@extends('customer.layout.layout')

@section('title', 'Additional Service Invoice | Yuktione')

@section('styles')
<style>
.yk-invoice-sheet {
    background: #fff;
    border: 1px solid #E2E9EE;
    border-radius: 1rem;
    overflow: hidden;
}
.yk-invoice-sheet__banner {
    background: #EAF6F2;
    border-bottom: 1px solid #E2E9EE;
    color: #142B4D;
    padding: 1rem 1.5rem;
}
.yk-invoice-table {
    border-collapse: collapse;
    width: 100%;
}
.yk-invoice-table th,
.yk-invoice-table td {
    border-bottom: 1px solid #E2E9EE;
    padding: 0.75rem 0.5rem;
    text-align: left;
    vertical-align: top;
}
.yk-invoice-table th {
    color: #718096;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    width: 34%;
}
.yk-invoice-table td {
    color: #142B4D;
    font-size: 0.9375rem;
    font-weight: 500;
}
.yk-invoice-total td {
    border-bottom: 0;
    color: #142B4D;
    font-weight: 700;
}
.yk-invoice-actions a,
.yk-invoice-actions button {
    align-items: center;
    border-radius: 0.75rem;
    display: inline-flex;
    font-size: 0.875rem;
    font-weight: 600;
    gap: 0.4rem;
    justify-content: center;
    min-height: 2.5rem;
    padding: 0.5rem 0.9rem;
}
@media print {
    .yk-invoice-actions, .yk-panel-chrome-hide, [data-panel-overlay], aside, header.sticky, nav, .whatsapp-float {
        display: none !important;
    }
    .yk-invoice-sheet {
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }
    body { background: #fff !important; }
}
</style>
@endsection

@section('content')
<div class="mx-auto grid max-w-5xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'Additional Services', 'url' => route('user.additional-services.index')],
        ['label' => 'Invoice'],
    ]" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Billing</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">Additional Service Invoice</h1>
        </div>
        <div class="yk-invoice-actions flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="border border-brand-primary bg-brand-primary text-white">
                <i class="fas fa-print" aria-hidden="true"></i> Print
            </button>
            <a href="{{ route('user.additional-services.invoice.pdf', $service->id) }}" class="border border-border bg-surface text-text-primary" target="_blank">
                <i class="fas fa-download" aria-hidden="true"></i> Download PDF
            </a>
            <a href="{{ route('user.additional-services.index') }}" class="border border-border bg-surface text-text-primary">
                Back to services
            </a>
        </div>
    </div>

    <article class="yk-invoice-sheet" id="invoice-content">
        <div class="yk-invoice-sheet__banner flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-primary">Invoice</p>
                <h2 class="font-display text-xl font-semibold text-brand-secondary">Additional Service · {{ config('yuktione.name', 'YuktiOne') }}</h2>
            </div>
            <div class="text-sm text-text-secondary sm:text-right">
                <p><span class="font-semibold text-brand-secondary">Invoice #:</span> {{ $invoice_number }}</p>
                <p><span class="font-semibold text-brand-secondary">Date:</span> {{ $invoice_date }}</p>
                <p><span class="font-semibold text-brand-secondary">Service ID:</span> AS-{{ str_pad($service->id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>

        <div class="grid gap-6 p-5 sm:p-6 md:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-semibold text-brand-secondary">Bill to</h3>
                <div class="rounded-control border border-border bg-surface-muted/30 p-4 text-sm text-text-primary">
                    <p class="font-semibold">{{ $customer->name }}</p>
                    <p>{{ $customer->email }}</p>
                    @if($customer->phone)<p>{{ $customer->phone }}</p>@endif
                    @if($customer->address)<p>{{ $customer->address }}</p>@endif
                </div>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-semibold text-brand-secondary">Service provider</h3>
                <div class="rounded-control border border-border bg-surface-muted/30 p-4 text-sm text-text-primary">
                    <p class="font-semibold">{{ $professional->name }}</p>
                    <p>{{ $professional->email }}</p>
                    <p>Professional ID: {{ $professional->id }}</p>
                </div>
            </div>
        </div>

        <div class="border-t border-border px-5 py-5 sm:px-6">
            <h3 class="mb-3 text-sm font-semibold text-brand-primary">Service details</h3>
            <table class="yk-invoice-table">
                <tbody>
                    <tr><th>Service name</th><td>{{ $service->service_name }}</td></tr>
                    <tr><th>Description</th><td>{{ $service->reason }}</td></tr>
                    <tr><th>Status</th><td><span class="inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-primary">Completed &amp; Paid</span></td></tr>
                    <tr><th>Completion date</th><td>{{ $service->professional_completed_at ? $service->professional_completed_at->format('d M, Y H:i') : '-' }}</td></tr>
                </tbody>
            </table>
        </div>

        @php
            $showPricingJourney = false;
            if (!empty($negotiation_details) && ($negotiation_details['negotiation_status'] ?? 'none') !== 'none') {
                $userNegotiatedDifferent = $negotiation_details['user_negotiated_price'] &&
                    (float) $negotiation_details['user_negotiated_price'] !== (float) $negotiation_details['original_price'];
                $adminFinalDifferent = $negotiation_details['admin_final_price'] &&
                    (float) $negotiation_details['admin_final_price'] !== (float) $negotiation_details['original_price'];
                $showPricingJourney = $userNegotiatedDifferent || $adminFinalDifferent;
            }
        @endphp

        @if($showPricingJourney)
        <div class="border-t border-border px-5 py-5 sm:px-6">
            <h3 class="mb-3 text-sm font-semibold text-brand-primary">Pricing journey</h3>
            <div class="flex flex-wrap items-center gap-3 rounded-control border border-border bg-brand-50/60 p-4">
                <div class="text-center">
                    <p class="text-lg font-bold text-brand-primary">₹{{ number_format($negotiation_details['original_price'], 2) }}</p>
                    <p class="text-xs text-text-muted">Original</p>
                </div>
                @if($negotiation_details['user_negotiated_price'] && $negotiation_details['user_negotiated_price'] != $negotiation_details['original_price'])
                    <span class="text-brand-primary" aria-hidden="true">→</span>
                    <div class="text-center">
                        <p class="text-lg font-bold text-brand-secondary">₹{{ number_format($negotiation_details['user_negotiated_price'], 2) }}</p>
                        <p class="text-xs text-text-muted">You negotiated</p>
                    </div>
                @endif
                @if($negotiation_details['admin_final_price'] && $negotiation_details['admin_final_price'] != $negotiation_details['original_price'])
                    <span class="text-brand-primary" aria-hidden="true">→</span>
                    <div class="text-center">
                        <p class="text-lg font-bold text-brand-secondary">₹{{ number_format($negotiation_details['admin_final_price'], 2) }}</p>
                        <p class="text-xs text-text-muted">Final agreed</p>
                    </div>
                @endif
                <span class="text-brand-primary" aria-hidden="true">→</span>
                <div class="text-center">
                    <p class="text-lg font-bold text-brand-primary">₹{{ number_format($pricing['total_price'], 2) }}</p>
                    <p class="text-xs text-text-muted">Paid with GST</p>
                </div>
            </div>
        </div>
        @endif

        <div class="border-t border-border px-5 py-5 sm:px-6">
            <h3 class="mb-3 text-sm font-semibold text-brand-primary">Payment breakdown</h3>
            <table class="yk-invoice-table">
                <tbody>
                    <tr><th>Service amount</th><td class="text-end">₹{{ number_format($pricing['base_price'], 2) }}</td></tr>
                    <tr><th>CGST (9%)</th><td class="text-end">₹{{ number_format($pricing['cgst'], 2) }}</td></tr>
                    <tr><th>SGST (9%)</th><td class="text-end">₹{{ number_format($pricing['sgst'], 2) }}</td></tr>
                    @if($pricing['igst'] > 0)
                        <tr><th>IGST (18%)</th><td class="text-end">₹{{ number_format($pricing['igst'], 2) }}</td></tr>
                    @endif
                    <tr class="yk-invoice-total"><th>Total amount paid</th><td class="text-end">₹{{ number_format($pricing['total_price'], 2) }}</td></tr>
                </tbody>
            </table>
        </div>

        @php
            $showNegotiation = false;
            if (!empty($negotiation_details) && ($negotiation_details['negotiation_status'] ?? 'none') !== 'none') {
                $userNegotiatedDifferent = $negotiation_details['user_negotiated_price'] &&
                    (float) $negotiation_details['user_negotiated_price'] !== (float) $negotiation_details['original_price'];
                $hasNegotiationComments = $service->user_negotiation_reason || $service->admin_negotiation_response;
                $showNegotiation = $userNegotiatedDifferent || $hasNegotiationComments;
            }
        @endphp

        @if($showNegotiation)
        <div class="border-t border-border px-5 py-5 sm:px-6">
            <h3 class="mb-3 text-sm font-semibold text-brand-primary">Negotiation summary</h3>
            <div class="rounded-control border border-border bg-surface-muted/40 p-4 text-sm text-text-primary">
                <p class="mb-2">
                    <span class="font-semibold">Status:</span>
                    {{ ucfirst(str_replace('_', ' ', $negotiation_details['negotiation_status'])) }}
                </p>
                @if($service->user_negotiation_reason)
                    <p class="mb-2"><span class="font-semibold">Your request:</span> {{ $service->user_negotiation_reason }}</p>
                @endif
                @if($service->admin_negotiation_response)
                    <p><span class="font-semibold">Response:</span> {{ $service->admin_negotiation_response }}</p>
                @endif
            </div>
        </div>
        @endif

        <div class="border-t border-border px-5 py-4 text-center text-xs text-text-muted sm:px-6">
            Thank you for using {{ config('yuktione.name', 'YuktiOne') }}.
            For support contact {{ config('yuktione.email', 'info@yuktione.in') }}.
            This is a computer-generated invoice. Generated on {{ now()->format('d M, Y H:i') }}.
        </div>
    </article>
</div>
@endsection
