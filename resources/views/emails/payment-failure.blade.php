@extends('emails.layouts.base')

@section('title', 'Payment failure notification')
@section('preheader', 'A YuktiOne booking payment has failed and needs attention.')
@section('heading', 'Payment failed')
@section('subtitle', 'A booking payment requires follow-up')

@section('content')
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;background-color:#fff5f5;border:1px solid #f1c9c9;border-radius:10px;">
        <tr>
            <td style="padding:14px 16px;font-size:14px;line-height:1.55;color:#8a3a3a;">
                <strong>Payment failed:</strong> A {{ $paymentData['booking_type'] }} booking payment has failed and requires attention.
            </td>
        </tr>
    </table>

    @include('emails.partials.info-card', [
        'title' => 'Customer',
        'rows' => array_values(array_filter([
            ['label' => 'Name', 'value' => e($userDetails['name'])],
            ['label' => 'Email', 'value' => e($userDetails['email'])],
            !empty($paymentData['phone']) ? ['label' => 'Phone', 'value' => e($paymentData['phone'])] : null,
        ])),
    ])

    @include('emails.partials.info-card', [
        'title' => $isAppointment ? 'Appointment details' : 'Event details',
        'rows' => array_values(array_filter($isAppointment ? [
            ['label' => 'Service', 'value' => e($paymentData['service_name'] ?? 'N/A')],
            ['label' => 'Plan type', 'value' => e(ucwords(str_replace('_', ' ', $paymentData['plan_type'] ?? 'N/A')))],
            ['label' => 'Amount', 'value' => '₹'.number_format(($paymentData['amount'] ?? 0) / 100, 2)],
        ] : [
            ['label' => 'Event', 'value' => e($paymentData['event_name'] ?? 'N/A')],
            ['label' => 'Event date', 'value' => e($paymentData['event_date'] ?? 'N/A')],
            !empty($paymentData['location']) ? ['label' => 'Location', 'value' => e($paymentData['location'])] : null,
            ['label' => 'Amount', 'value' => '₹'.number_format(($paymentData['amount'] ?? 0) / 100, 2)],
        ])),
    ])

    @include('emails.partials.info-card', [
        'title' => 'Failure details',
        'rows' => [
            ['label' => 'Reason', 'value' => e($paymentData['error_description'] ?? 'Unknown error')],
            ['label' => 'Error code', 'value' => e($paymentData['error_code'] ?? 'N/A')],
            ['label' => 'Reference ID', 'value' => e($paymentData['reference_id'] ?? 'N/A')],
        ],
    ])

    @if ($recipientType === 'professional')
        <p style="margin:0 0 8px;font-weight:700;color:#142B4D;">Action required</p>
        <p style="margin:0 0 12px;">Your customer's payment has failed. Please reach out to help them complete the booking.</p>
        @if (!empty($paymentData['phone']))
            @include('emails.partials.button', [
                'url' => 'tel:'.$paymentData['phone'],
                'label' => 'Call customer',
            ])
        @endif
        <p style="margin:0;text-align:center;">
            <a href="mailto:{{ $userDetails['email'] }}" style="color:#316D61;text-decoration:none;font-weight:700;">Email customer</a>
        </p>
    @else
        <p style="margin:0 0 8px;font-weight:700;color:#142B4D;">Admin review required</p>
        <p style="margin:0 0 12px;">This payment failure has been logged and may require follow-up with the customer or payment gateway.</p>
        @include('emails.partials.button', [
            'url' => url('/admin/dashboard'),
            'label' => 'Open admin dashboard',
        ])
    @endif

    <p style="margin:22px 0 0;font-size:12px;color:#64748b;">
        Notification sent on {{ now()->format('d M Y, h:i A') }}
    </p>
@endsection
