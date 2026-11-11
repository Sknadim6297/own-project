<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Additional Service Invoice - {{ $invoice_number }}</title>
    <style>
        /* DomPDF-safe: tables only, embedded fonts, no flex/grid/external fonts */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 12px;
            background: #ffffff;
            color: #142B4D;
            font-size: 11px;
            line-height: 1.35;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #E2E9EE;
        }

        .invoice-header {
            padding: 14px 16px;
            border-bottom: 2px solid #36877F;
            background: #ffffff;
        }

        .header-content {
            width: 100%;
            border-collapse: collapse;
        }

        .header-content td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        .logo-cell { width: 48%; text-align: left; }
        .title-cell { width: 52%; text-align: right; }

        .logo {
            height: 52px;
            max-width: 200px;
        }

        .logo-fallback {
            display: inline-block;
            padding: 10px 14px;
            border: 1px solid #E2E9EE;
            background: #EAF6F2;
            color: #142B4D;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.02em;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            color: #142B4D;
            letter-spacing: 0.04em;
        }

        .invoice-subtitle {
            font-size: 9px;
            margin: 3px 0 0 0;
            color: #475569;
        }

        .brand-chip {
            display: inline-block;
            margin-top: 6px;
            padding: 2px 8px;
            border: 1px solid #D9EDE7;
            background: #EAF6F2;
            color: #245E59;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .meta-line {
            margin: 2px 0;
            font-size: 10px;
            color: #475569;
        }

        .meta-label {
            font-weight: bold;
            color: #142B4D;
        }

        .company-section,
        .service-section,
        .pricing-section,
        .negotiation-section,
        .footer-section,
        .footer-note {
            padding: 12px 16px;
        }

        .company-section,
        .service-section,
        .pricing-section,
        .negotiation-section,
        .footer-section {
            border-bottom: 1px solid #E2E9EE;
        }

        .company-table {
            width: 100%;
            border-collapse: collapse;
        }

        .company-table td {
            width: 50%;
            vertical-align: top;
            border: none;
            padding: 0 8px 0 0;
        }

        .company-table td:last-child {
            padding-right: 0;
            padding-left: 8px;
        }

        .section-header {
            background: #142B4D;
            color: #ffffff;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 9px;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: left;
        }

        .company-details {
            background: #F7FAFB;
            padding: 8px;
            border: 1px solid #E2E9EE;
            min-height: 70px;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #142B4D;
            margin: 0 0 4px 0;
        }

        .detail-line {
            margin: 2px 0;
            color: #475569;
            word-wrap: break-word;
        }

        .provider-block {
            margin-top: 10px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table th,
        .table td {
            padding: 7px 8px;
            border: 1px solid #E2E9EE;
            text-align: left;
            vertical-align: top;
            color: #142B4D;
            font-size: 10px;
        }

        .table th {
            background: #F7FAFB;
            font-weight: bold;
            width: 32%;
            color: #475569;
        }

        .summary-wrap {
            margin: 0;
            border: 1px solid #E2E9EE;
            background: #F7FAFB;
        }

        .summary-title {
            margin: 0;
            padding: 7px 8px;
            background: #EAF6F2;
            border-bottom: 1px solid #E2E9EE;
            color: #245E59;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 1px solid #E2E9EE;
            padding: 6px 8px;
            font-size: 10px;
            color: #142B4D;
            vertical-align: top;
        }

        .summary-table .amount {
            text-align: right;
            white-space: nowrap;
        }

        .summary-table .total-row td {
            background: #EAF6F2;
            font-weight: bold;
            color: #142B4D;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            background: #EAF6F2;
            color: #245E59;
        }

        .badge-warning {
            background: #FFF7E8;
            color: #B7791F;
        }

        .badge-muted {
            background: #F1F5F9;
            color: #475569;
        }

        .total-section {
            background: #36877F;
            color: #ffffff;
            padding: 10px 16px;
            text-align: right;
        }

        .total-amount {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.02em;
        }

        .words-section {
            padding: 10px 16px;
            border-bottom: 1px solid #E2E9EE;
        }

        .words-label {
            font-weight: bold;
            color: #142B4D;
            margin-bottom: 2px;
        }

        .words-text {
            color: #245E59;
            font-weight: bold;
        }

        .tax-note {
            padding: 8px 16px;
            text-align: center;
            background: #F7FAFB;
            border-bottom: 1px solid #E2E9EE;
            color: #142B4D;
            font-size: 10px;
        }

        .footer-section {
            text-align: right;
            background: #ffffff;
        }

        .signature-line {
            border-top: 1px solid #142B4D;
            width: 160px;
            margin: 28px 0 6px auto;
        }

        .footer-note {
            text-align: center;
            background: #F7FAFB;
            font-size: 8px;
            color: #475569;
        }

        .footer-note p {
            margin: 3px 0;
        }

        .support-email {
            color: #36877F;
            font-weight: bold;
        }

        .mt-8 { margin-top: 8px; }
        .font-bold { font-weight: bold; }
        .wrap { word-wrap: break-word; }
    </style>
</head>
<body>
@php
    $brandName = config('yuktione.name', 'YuktiOne');
    $supportEmail = config('yuktione.email', 'info@yuktione.in');
    $logoPath = public_path('frontend/assets/Yuktione_logo.png');
    $logoExists = file_exists($logoPath);

    $showNegotiation = false;
    if (isset($negotiation_details) && $negotiation_details['negotiation_status'] !== 'none') {
        $userNegotiatedDifferent = $negotiation_details['user_negotiated_price'] &&
            (float) $negotiation_details['user_negotiated_price'] !== (float) $negotiation_details['original_price'];
        $hasNegotiationComments = $service->user_negotiation_reason || $service->admin_negotiation_response;
        $showNegotiation = $userNegotiatedDifferent || $hasNegotiationComments;
    }
@endphp
    <div class="invoice-container">
        <div class="invoice-header">
            <table class="header-content">
                <tr>
                    <td class="logo-cell">
                        @if($logoExists)
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="{{ $brandName }}" class="logo">
                        @else
                            <div class="logo-fallback">{{ strtoupper($brandName) }}</div>
                        @endif
                    </td>
                    <td class="title-cell">
                        <div class="invoice-title">TAX INVOICE</div>
                        <div class="invoice-subtitle">Additional Service · Original for Recipient</div>
                        <div class="brand-chip">{{ $brandName }} Professional</div>
                        <div class="meta-line mt-8"><span class="meta-label">Invoice #:</span> {{ $invoice_number }}</div>
                        <div class="meta-line"><span class="meta-label">Date:</span> {{ $invoice_date }}</div>
                        <div class="meta-line"><span class="meta-label">Service ID:</span> AS-{{ str_pad($service->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="company-section">
            <table class="company-table">
                <tr>
                    <td>
                        <div class="section-header">Sold By</div>
                        <div class="company-details">
                            {{-- Merchant / tax identity preserved from existing platform invoices --}}
                            <div class="company-name">YuktiOne</div>
                            <div class="detail-line">110, Acharya Sisir Kumar Ghosh Street</div>
                            <div class="detail-line">Entally, Kolkata - 700014</div>
                            <div class="detail-line">WEST BENGAL, INDIA</div>
                            <div class="detail-line mt-8">
                                <div><strong>PAN No:</strong> AAFCT8462R</div>
                                <div><strong>GST Registration No:</strong> 19AAFCT8462R1Z5</div>
                                <div><strong>State/UT Code:</strong> 19</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="section-header">Bill To</div>
                        <div class="company-details">
                            <div class="company-name wrap">{{ $customer->name }}</div>
                            <div class="detail-line wrap">{{ $customer->email }}</div>
                            @if($customer->phone)
                                <div class="detail-line">{{ $customer->phone }}</div>
                            @endif
                            @if($customer->address)
                                <div class="detail-line wrap">{{ $customer->address }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <div class="provider-block">
                <div class="section-header">Service Provider</div>
                <div class="company-details">
                    <div class="company-name wrap">{{ $professional->name }}</div>
                    <div class="detail-line wrap">{{ $professional->email }}</div>
                    <div class="detail-line">Professional ID: {{ $professional->id }}</div>
                </div>
            </div>
        </div>

        <div class="service-section">
            <div class="section-header">Service Details</div>
            <table class="table">
                <tbody>
                    <tr>
                        <th>Service Name</th>
                        <td class="wrap">{{ $service->service_name }}</td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td class="wrap">{{ $service->reason }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge">Completed &amp; Paid</span></td>
                    </tr>
                    <tr>
                        <th>Completion Date</th>
                        <td>{{ $service->professional_completed_at ? $service->professional_completed_at->format('d M, Y H:i') : '-' }}</td>
                    </tr>
                    @if($service->payment_id || $service->payment_transaction_id)
                        <tr>
                            <th>Payment Reference</th>
                            <td class="wrap">{{ $service->payment_transaction_id ?: $service->payment_id }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="pricing-section">
            <div class="summary-wrap">
                <div class="summary-title">Price Breakdown</div>
                <table class="summary-table">
                    <tr>
                        <td>Base Price</td>
                        <td class="amount">₹{{ number_format($pricing['base_price'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>CGST (9%)</td>
                        <td class="amount">₹{{ number_format($pricing['cgst'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>SGST (9%)</td>
                        <td class="amount">₹{{ number_format($pricing['sgst'], 2) }}</td>
                    </tr>
                    @if($pricing['igst'] > 0)
                        <tr>
                            <td>IGST (18%)</td>
                            <td class="amount">₹{{ number_format($pricing['igst'], 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Amount</td>
                        <td class="amount">₹{{ number_format($pricing['total_price'], 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="total-section">
            <div class="total-amount">TOTAL AMOUNT: ₹{{ number_format($pricing['total_price'], 2) }}</div>
        </div>

        <div class="words-section">
            <div class="words-label">Amount in Words:</div>
            <div class="words-text">{{ ucwords(\App\Helpers\NumberToWords::convert($pricing['total_price'])) }} Only</div>
        </div>

        <div class="tax-note">
            <strong>Whether tax is payable under reverse charge: NO</strong>
        </div>

        @if($showNegotiation)
            <div class="negotiation-section">
                <div class="section-header">Negotiation History</div>
                <div class="detail-line">
                    <strong>Status:</strong>
                    @switch($negotiation_details['negotiation_status'])
                        @case('user_negotiated')
                            <span class="badge badge-warning">Customer Negotiated</span>
                            @break
                        @case('admin_responded')
                            <span class="badge">Admin Responded</span>
                            @break
                        @default
                            <span class="badge badge-muted">{{ ucfirst(str_replace('_', ' ', $negotiation_details['negotiation_status'])) }}</span>
                    @endswitch
                </div>
                @if($service->user_negotiation_reason)
                    <div class="detail-line wrap mt-8"><strong>Customer Request:</strong> {{ $service->user_negotiation_reason }}</div>
                @endif
                @if($service->admin_negotiation_response)
                    <div class="detail-line wrap"><strong>Admin Response:</strong> {{ $service->admin_negotiation_response }}</div>
                @endif
            </div>
        @endif

        <div class="footer-section">
            <div class="font-bold">For YuktiOne:</div>
            <div class="signature-line"></div>
            <div>Authorized Signatory</div>
        </div>

        <div class="footer-note">
            <p>This is a computer generated invoice and does not require physical signature.</p>
            <p>Generated on {{ now()->format('d M, Y H:i') }}</p>
            <p>For any queries regarding this invoice, please contact us at <span class="support-email">{{ $supportEmail }}</span>.</p>
            <p><strong>Thank you for choosing {{ $brandName }}.</strong></p>
        </div>
    </div>
</body>
</html>
