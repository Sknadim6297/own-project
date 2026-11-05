@extends('professional.layout.layout')

@section('title', 'Additional Service Invoice | YuktiOne')

@section('styles')
<style>
    @media print {
        .as-invoice-toolbar,
        .as-invoice-nav,
        aside,
        header.sticky,
        .whatsapp-float,
        [data-whatsapp] {
            display: none !important;
        }
        .as-invoice-sheet {
            border: none !important;
            box-shadow: none !important;
            max-width: none !important;
        }
        body { background: #fff !important; }
    }
</style>
@endsection

@section('content')
@php
    $brandName = config('yuktione.name', 'YuktiOne');
    $supportEmail = config('yuktione.email', 'info@yuktione.in');
    $logoWeb = asset('frontend/assets/Yuktione_logo.png');
    $logoPath = public_path('frontend/assets/Yuktione_logo.png');
    $logoExists = file_exists($logoPath);

    $showPricingJourney = false;
    if (isset($negotiation_details) && $negotiation_details['negotiation_status'] !== 'none') {
        $userNegotiatedDifferent = $negotiation_details['user_negotiated_price'] &&
            (float) $negotiation_details['user_negotiated_price'] !== (float) $negotiation_details['original_price'];
        $adminFinalDifferent = $negotiation_details['admin_final_price'] &&
            (float) $negotiation_details['admin_final_price'] !== (float) $negotiation_details['original_price'];
        $showPricingJourney = $userNegotiatedDifferent || $adminFinalDifferent;
    }

    $showNegotiation = false;
    if (isset($negotiation_details) && $negotiation_details['negotiation_status'] !== 'none') {
        $userNegotiatedDifferent = $negotiation_details['user_negotiated_price'] &&
            (float) $negotiation_details['user_negotiated_price'] !== (float) $negotiation_details['original_price'];
        $hasNegotiationComments = $service->user_negotiation_reason || $service->admin_negotiation_response;
        $showNegotiation = $userNegotiatedDifferent || $hasNegotiationComments;
    }
@endphp

<div class="mx-auto w-full max-w-5xl space-y-5">
    <header class="as-invoice-nav flex flex-col gap-4 rounded-panel border border-border bg-white p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
        <div class="min-w-0">
            <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.additional-services.index') }}">Additional Services</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.additional-services.show', $service->id) }}">Details</a></li>
                    <li aria-hidden="true" class="text-border">/</li>
                    <li aria-current="page" class="text-text-secondary">Invoice</li>
                </ol>
            </nav>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Billing</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Additional Service Invoice</h1>
            <p class="mt-1 text-sm text-text-secondary">{{ $invoice_number }} · {{ $invoice_date }}</p>
        </div>
        <div class="as-invoice-toolbar flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                <i class="fas fa-print text-xs" aria-hidden="true"></i> Print
            </button>
            <a href="{{ route('professional.additional-services.invoice.pdf', $service->id) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                <i class="fas fa-download text-xs" aria-hidden="true"></i> Download PDF
            </a>
            <a href="{{ route('professional.additional-services.show', $service->id) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> Back
            </a>
        </div>
    </header>

    <article class="as-invoice-sheet overflow-hidden rounded-panel border border-border bg-white shadow-card" id="invoice-content">
        {{-- Brand header --}}
        <div class="border-b-2 border-brand-primary px-5 py-5 sm:px-8 sm:py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    @if($logoExists)
                        <img src="{{ $logoWeb }}" alt="{{ $brandName }}" class="h-12 w-auto max-w-[200px] object-contain">
                    @else
                        <span class="inline-flex items-center rounded-control border border-border bg-brand-50 px-3 py-2 text-sm font-bold tracking-wide text-brand-950">{{ strtoupper($brandName) }}</span>
                    @endif
                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $brandName }} · Additional Service</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-lg font-semibold tracking-wide text-brand-950">TAX INVOICE</p>
                    <p class="mt-1 text-xs text-text-muted">Original for recipient</p>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex flex-wrap gap-x-2 sm:justify-end">
                            <dt class="font-medium text-text-muted">Invoice #</dt>
                            <dd class="font-semibold text-text-primary">{{ $invoice_number }}</dd>
                        </div>
                        <div class="flex flex-wrap gap-x-2 sm:justify-end">
                            <dt class="font-medium text-text-muted">Date</dt>
                            <dd class="text-text-primary">{{ $invoice_date }}</dd>
                        </div>
                        <div class="flex flex-wrap gap-x-2 sm:justify-end">
                            <dt class="font-medium text-text-muted">Service ID</dt>
                            <dd class="text-text-primary">AS-{{ str_pad($service->id, 6, '0', STR_PAD_LEFT) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Parties --}}
        <div class="grid gap-4 border-b border-divider p-5 sm:grid-cols-2 sm:p-8">
            <section>
                <h2 class="rounded-control bg-brand-950 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white">Sold by</h2>
                <div class="mt-2 rounded-control border border-border bg-surface-muted p-3 text-sm text-text-secondary">
                    {{-- Merchant / tax identity preserved; not inventable from config --}}
                    <p class="font-semibold text-brand-950">TAZEN TECHNOLOGIES PRIVATE LIMITED</p>
                    <p class="mt-1">110, Acharya Sisir Kumar Ghosh Street</p>
                    <p>Entally, Kolkata - 700014</p>
                    <p>WEST BENGAL, INDIA</p>
                    <p class="mt-2"><span class="font-medium text-text-primary">PAN:</span> AAFCT8462R</p>
                    <p><span class="font-medium text-text-primary">GSTIN:</span> 19AAFCT8462R1Z5</p>
                    <p><span class="font-medium text-text-primary">State/UT Code:</span> 19</p>
                </div>
            </section>
            <section>
                <h2 class="rounded-control bg-brand-950 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white">Bill to</h2>
                <div class="mt-2 rounded-control border border-border bg-surface-muted p-3 text-sm text-text-secondary">
                    <p class="font-semibold text-brand-950">{{ $customer->name }}</p>
                    <p class="mt-1 break-all">{{ $customer->email }}</p>
                    @if($customer->phone)
                        <p>{{ $customer->phone }}</p>
                    @endif
                    @if($customer->address)
                        <p class="mt-1 whitespace-pre-wrap break-words">{{ $customer->address }}</p>
                    @endif
                </div>
            </section>
            <section class="sm:col-span-2">
                <h2 class="rounded-control bg-brand-950 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white">Service provider</h2>
                <div class="mt-2 rounded-control border border-border bg-surface-muted p-3 text-sm text-text-secondary">
                    <p class="font-semibold text-brand-950">{{ $professional->name }}</p>
                    <p class="mt-1 break-all">{{ $professional->email }}</p>
                    <p>Professional ID: {{ $professional->id }}</p>
                </div>
            </section>
        </div>

        {{-- Service details --}}
        <div class="border-b border-divider p-5 sm:p-8">
            <h2 class="text-sm font-semibold text-brand-950">Service details</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[20rem] border-collapse text-sm">
                    <tbody>
                        <tr class="border-b border-divider">
                            <th scope="row" class="w-[34%] bg-surface-muted px-3 py-2.5 text-left font-medium text-text-secondary">Service name</th>
                            <td class="px-3 py-2.5 font-medium text-text-primary">{{ $service->service_name }}</td>
                        </tr>
                        <tr class="border-b border-divider">
                            <th scope="row" class="bg-surface-muted px-3 py-2.5 text-left font-medium text-text-secondary">Description</th>
                            <td class="px-3 py-2.5 whitespace-pre-wrap break-words text-text-secondary">{{ $service->reason }}</td>
                        </tr>
                        <tr class="border-b border-divider">
                            <th scope="row" class="bg-surface-muted px-3 py-2.5 text-left font-medium text-text-secondary">Status</th>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex rounded-full bg-success-surface px-2.5 py-1 text-xs font-semibold text-success">Completed &amp; Paid</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="bg-surface-muted px-3 py-2.5 text-left font-medium text-text-secondary">Completion date</th>
                            <td class="px-3 py-2.5 text-text-primary">{{ $service->professional_completed_at ? $service->professional_completed_at->format('d M, Y H:i') : '-' }}</td>
                        </tr>
                        @if($service->payment_id || $service->payment_transaction_id)
                            <tr class="border-t border-divider">
                                <th scope="row" class="bg-surface-muted px-3 py-2.5 text-left font-medium text-text-secondary">Payment reference</th>
                                <td class="px-3 py-2.5 break-all text-text-primary">{{ $service->payment_transaction_id ?: $service->payment_id }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if($showPricingJourney)
            <div class="border-b border-divider bg-brand-50/40 p-5 sm:p-8">
                <h2 class="text-sm font-semibold text-brand-950">Pricing flow</h2>
                <div class="mt-4 flex flex-wrap items-center gap-3 sm:gap-4">
                    <div class="min-w-[7.5rem] rounded-control border border-border bg-white px-3 py-3 text-center">
                        <p class="text-lg font-semibold text-brand-800">₹{{ number_format($negotiation_details['original_price'], 2) }}</p>
                        <p class="mt-1 text-[11px] text-text-muted">Original price</p>
                    </div>
                    @if($negotiation_details['user_negotiated_price'] && $negotiation_details['user_negotiated_price'] != $negotiation_details['original_price'])
                        <span class="text-brand-primary" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                        <div class="min-w-[7.5rem] rounded-control border border-border bg-white px-3 py-3 text-center">
                            <p class="text-lg font-semibold text-warning">₹{{ number_format($negotiation_details['user_negotiated_price'], 2) }}</p>
                            <p class="mt-1 text-[11px] text-text-muted">Customer negotiated</p>
                        </div>
                    @endif
                    @if($negotiation_details['admin_final_price'] && $negotiation_details['admin_final_price'] != $negotiation_details['original_price'])
                        <span class="text-brand-primary" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                        <div class="min-w-[7.5rem] rounded-control border border-border bg-white px-3 py-3 text-center">
                            <p class="text-lg font-semibold text-info">₹{{ number_format($negotiation_details['admin_final_price'], 2) }}</p>
                            <p class="mt-1 text-[11px] text-text-muted">Admin final price</p>
                        </div>
                    @endif
                    <span class="text-brand-primary" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                    <div class="min-w-[7.5rem] rounded-control border border-brand-100 bg-white px-3 py-3 text-center">
                        <p class="text-lg font-semibold text-success">₹{{ number_format($pricing['total_price'], 2) }}</p>
                        <p class="mt-1 text-[11px] text-text-muted">Final (with GST)</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Price breakdown — values from controller $pricing only --}}
        <div class="border-b border-divider p-5 sm:p-8">
            <h2 class="text-sm font-semibold text-brand-950">Price breakdown</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[18rem] border-collapse text-sm">
                    <tbody>
                        <tr class="border-b border-divider">
                            <td class="px-3 py-2.5 text-text-secondary">Base price</td>
                            <td class="px-3 py-2.5 text-right font-medium text-text-primary">₹{{ number_format($pricing['base_price'], 2) }}</td>
                        </tr>
                        <tr class="border-b border-divider">
                            <td class="px-3 py-2.5 text-text-secondary">CGST (9%)</td>
                            <td class="px-3 py-2.5 text-right font-medium text-text-primary">₹{{ number_format($pricing['cgst'], 2) }}</td>
                        </tr>
                        <tr class="border-b border-divider">
                            <td class="px-3 py-2.5 text-text-secondary">SGST (9%)</td>
                            <td class="px-3 py-2.5 text-right font-medium text-text-primary">₹{{ number_format($pricing['sgst'], 2) }}</td>
                        </tr>
                        @if($pricing['igst'] > 0)
                            <tr class="border-b border-divider">
                                <td class="px-3 py-2.5 text-text-secondary">IGST (18%)</td>
                                <td class="px-3 py-2.5 text-right font-medium text-text-primary">₹{{ number_format($pricing['igst'], 2) }}</td>
                            </tr>
                        @endif
                        <tr class="bg-brand-50">
                            <td class="px-3 py-3 font-semibold text-brand-950">Total amount</td>
                            <td class="px-3 py-3 text-right text-base font-semibold text-brand-800">₹{{ number_format($pricing['total_price'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-text-muted">Whether tax is payable under reverse charge: <strong class="text-text-primary">NO</strong></p>
        </div>

        @if($showNegotiation)
            <div class="border-b border-divider p-5 sm:p-8">
                <h2 class="text-sm font-semibold text-brand-950">Negotiation history</h2>
                <div class="mt-3 rounded-control border border-warning/20 bg-warning-surface p-4 text-sm text-text-secondary">
                    <p>
                        <span class="font-medium text-text-primary">Status:</span>
                        @switch($negotiation_details['negotiation_status'])
                            @case('user_negotiated')
                                <span class="inline-flex rounded-full bg-warning-surface px-2 py-0.5 text-xs font-semibold text-warning">Customer negotiated</span>
                                @break
                            @case('admin_responded')
                                <span class="inline-flex rounded-full bg-success-surface px-2 py-0.5 text-xs font-semibold text-success">Admin responded</span>
                                @break
                            @default
                                <span class="inline-flex rounded-full bg-surface-muted px-2 py-0.5 text-xs font-semibold text-text-secondary">{{ ucfirst(str_replace('_', ' ', $negotiation_details['negotiation_status'])) }}</span>
                        @endswitch
                    </p>
                    @if($service->user_negotiation_reason)
                        <p class="mt-2"><span class="font-medium text-text-primary">Customer request:</span> <span class="whitespace-pre-wrap">{{ $service->user_negotiation_reason }}</span></p>
                    @endif
                    @if($service->admin_negotiation_response)
                        <p class="mt-2"><span class="font-medium text-text-primary">Admin response:</span> <span class="whitespace-pre-wrap">{{ $service->admin_negotiation_response }}</span></p>
                    @endif
                </div>
            </div>
        @endif

        <footer class="bg-surface-muted px-5 py-5 text-center text-xs leading-5 text-text-muted sm:px-8">
            <p>This is a computer-generated invoice. No signature required.</p>
            <p class="mt-1">Generated on {{ now()->format('d M, Y H:i') }}</p>
            <p class="mt-2">For queries, contact <a class="font-semibold text-brand-primary hover:text-brand-800" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
            <p class="mt-1 font-medium text-text-secondary">Thank you for choosing {{ $brandName }}.</p>
            <p class="mt-3 text-[11px] text-text-muted">For TAZEN TECHNOLOGIES PRIVATE LIMITED · Authorized Signatory</p>
        </footer>
    </article>
</div>
@endsection
