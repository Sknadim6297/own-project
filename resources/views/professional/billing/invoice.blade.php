<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice #{{ $invoice_no }}</title>
    <style>
        /* DomPDF-safe layout: tables only, embedded fonts, no flex/grid */
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

        .logo-cell {
            width: 48%;
            text-align: left;
        }

        .title-cell {
            width: 52%;
            text-align: right;
        }

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

        .company-section,
        .order-section,
        .items-section,
        .words-section,
        .tax-info,
        .payment-section,
        .footer-section,
        .footer-note {
            padding: 12px 16px;
        }

        .company-section,
        .order-section,
        .items-section,
        .words-section,
        .tax-info,
        .payment-section,
        .footer-section {
            border-bottom: 1px solid #E2E9EE;
        }

        .company-table,
        .order-table,
        .payment-table,
        .summary-table,
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .company-table td,
        .order-table td,
        .payment-table td {
            width: 50%;
            vertical-align: top;
            border: none;
            padding: 0 8px 0 0;
        }

        .company-table td:last-child,
        .order-table td:last-child,
        .payment-table td:last-child {
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

        .order-label,
        .payment-label {
            font-weight: bold;
            color: #142B4D;
            display: inline-block;
            width: 100px;
        }

        .order-value,
        .payment-value {
            color: #475569;
        }

        .summary-wrap {
            margin: 0 0 12px 0;
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

        .items-table {
            border: 1px solid #E2E9EE;
            margin: 0;
        }

        .items-table th {
            background: #142B4D;
            color: #ffffff;
            padding: 7px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #142B4D;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .items-table td {
            padding: 7px 6px;
            border: 1px solid #E2E9EE;
            text-align: center;
            vertical-align: top;
            color: #142B4D;
        }

        .items-table td:nth-child(2) {
            text-align: left;
        }

        .service-name {
            font-weight: bold;
            color: #142B4D;
            margin-bottom: 3px;
        }

        .service-details {
            font-size: 8px;
            color: #475569;
            line-height: 1.4;
        }

        .total-section {
            background: #36877F;
            color: #ffffff;
            padding: 10px 16px;
            text-align: right;
            border-bottom: 1px solid #36877F;
        }

        .total-amount {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.02em;
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

        .tax-info {
            text-align: center;
            background: #F7FAFB;
            color: #142B4D;
        }

        .payment-item {
            margin: 4px 0;
        }

        .footer-section {
            text-align: right;
            background: #ffffff;
        }

        .signature-area {
            margin: 4px 0;
            color: #142B4D;
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

        .footer-note a,
        .footer-note .support-email {
            color: #36877F;
            font-weight: bold;
            text-decoration: none;
        }

        .font-bold { font-weight: bold; }
        .mt-8 { margin-top: 8px; }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .invoice-container {
                border: 1px solid #E2E9EE;
                max-width: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>
@php
    $brandName = config('yuktione.name', 'YuktiOne');
    $supportEmail = config('yuktione.email', 'info@yuktione.in');
    $logoPath = public_path('frontend/assets/Yuktione_logo.png');
    $logoExists = file_exists($logoPath);
    $profile = $professional->profile ?? null;
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
                        <div class="invoice-subtitle">Bill of Supply / Cash Memo (Original for Recipient)</div>
                        <div class="brand-chip">{{ $brandName }} Professional Billing</div>
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
                            {{-- Merchant / tax identity preserved from existing invoice; not inventable from config --}}
                            <div class="company-name">TAZEN TECHNOLOGIES PRIVATE LIMITED</div>
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
                        <div class="section-header">Billing Address</div>
                        <div class="company-details">
                            <div class="company-name">{{ strtoupper($professional->name) }}</div>
                            @if($profile)
                                @if($profile->gst_address)
                                    <div class="detail-line">{{ $profile->gst_address }}</div>
                                @elseif($profile->address)
                                    <div class="detail-line">{{ $profile->address }}</div>
                                @elseif($profile->full_address)
                                    <div class="detail-line">{{ $profile->full_address }}</div>
                                @else
                                    <div class="detail-line">{{ $professional->email }}</div>
                                @endif
                                @if($profile->state_name)
                                    <div class="detail-line">{{ strtoupper($profile->state_name) }}, INDIA</div>
                                @else
                                    <div class="detail-line">INDIA</div>
                                @endif
                            @else
                                <div class="detail-line">{{ $professional->email }}</div>
                                <div class="detail-line">INDIA</div>
                            @endif
                            <div class="detail-line mt-8">
                                @if($profile && $profile->state_code)
                                    <strong>State/UT Code:</strong> {{ $profile->state_code }}
                                @else
                                    <strong>State/UT Code:</strong> 19
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="order-section">
            <table class="order-table">
                <tr>
                    <td>
                        <div class="detail-line"><span class="order-label">Order Number:</span> <span class="order-value">TZ-{{ str_pad($booking->id, 8, '0', STR_PAD_LEFT) }}</span></div>
                        <div class="detail-line"><span class="order-label">Order Date:</span> <span class="order-value">{{ $booking->created_at->format('d.m.Y') }}</span></div>
                        <div class="detail-line"><span class="order-label">Service Date:</span> <span class="order-value">{{ \Carbon\Carbon::parse($booking->service_datetime)->format('d/m/Y H:i') }}</span></div>
                    </td>
                    <td>
                        <div class="detail-line"><span class="order-label">Invoice Number:</span> <span class="order-value">{{ $invoice_no }}</span></div>
                        <div class="detail-line"><span class="order-label">Invoice Date:</span> <span class="order-value">{{ $invoice_date }}</span></div>
                        <div class="detail-line"><span class="order-label">Place of Supply:</span> <span class="order-value">{{ $profile->state_name ?? 'West Bengal' }}</span></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="items-section">
            <div class="summary-wrap">
                <div class="summary-title">Customer Payment Breakdown</div>
                <table class="summary-table">
                    <tr>
                        <td>Service: {{ $booking->service->name ?? 'Professional Service' }}</td>
                        <td class="amount">₹{{ number_format($baseAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>GST @ 18% on Service:</td>
                        <td class="amount">₹{{ number_format($customerAmount - $baseAmount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Paid by Customer:</td>
                        <td class="amount">₹{{ number_format($customerAmount, 2) }}</td>
                    </tr>
                </table>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Sl. No</th>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;">Unit Price</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 19%;">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>
                            <div class="service-name">Platform Service Fee</div>
                            <div class="service-details">
                                Commission Rate: {{ $marginPercentage }}% on base service amount<br>
                                Service Date: {{ $booking->service_datetime ? \Carbon\Carbon::parse($booking->service_datetime)->format('d/m/Y H:i') : $booking->created_at->format('d/m/Y H:i') }}<br>
                                Base Amount: ₹{{ number_format($baseAmount, 2) }}
                            </div>
                        </td>
                        <td>₹{{ number_format($platformFee, 2) }}</td>
                        <td>1</td>
                        <td>₹{{ number_format($platformFee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>
                            <div class="service-name">GST on Platform Fee</div>
                            <div class="service-details">
                                GST @ 18% on Platform Service Fee<br>
                                (CGST 9% + SGST 9%)
                            </div>
                        </td>
                        <td>₹{{ number_format($platformFeeCGST + $platformFeeSGST, 2) }}</td>
                        <td>1</td>
                        <td>₹{{ number_format($platformFeeCGST + $platformFeeSGST, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-amount">TOTAL AMOUNT PAYABLE: ₹{{ number_format($totalPlatformCut, 2) }}</div>
        </div>

        <div class="words-section">
            <div class="words-label">Amount in Words:</div>
            <div class="words-text">{{ ucwords(\App\Helpers\NumberToWords::convert($totalPlatformCut)) }} Only</div>
        </div>

        <div class="tax-info">
            <strong>Whether tax is payable under reverse charge: NO</strong>
        </div>

        <div class="payment-section">
            <table class="payment-table">
                <tr>
                    <td>
                        <div class="payment-item">
                            <span class="payment-label">Transaction ID:</span>
                            <span class="payment-value">{{ $booking->payment_id ?? 'CASH_PAYMENT_' . $booking->id }}</span>
                        </div>
                        <div class="payment-item">
                            <span class="payment-label">Service Date:</span>
                            <span class="payment-value">{{ $booking->service_datetime ? \Carbon\Carbon::parse($booking->service_datetime)->format('d/m/Y H:i') : $booking->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="payment-item">
                            <span class="payment-label">Invoice Date:</span>
                            <span class="payment-value">{{ $invoice_date }}</span>
                        </div>
                        <div class="payment-item">
                            <span class="payment-label">Amount Due:</span>
                            <span class="payment-value font-bold">₹{{ number_format($totalPlatformCut, 2) }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-section">
            <div class="signature-area">
                <div class="font-bold">For TAZEN TECHNOLOGIES PRIVATE LIMITED:</div>
                <div class="signature-line"></div>
                <div>Authorized Signatory</div>
            </div>
        </div>

        <div class="footer-note">
            <p>This is a computer generated invoice and does not require physical signature.</p>
            <p>For any queries regarding this invoice, please contact us at <span class="support-email">{{ $supportEmail }}</span>.</p>
            <p><strong>Thank you for choosing {{ $brandName }}.</strong></p>
        </div>
    </div>
</body>
</html>
