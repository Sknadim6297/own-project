@extends('emails.layouts.base')

@section('title', 'Booking confirmed')
@section('preheader', 'Your YuktiOne appointment is confirmed.')
@section('heading', 'Booking confirmed')
@section('subtitle', 'Your appointment has been successfully scheduled')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $booking->customer_name }},</p>
    <p style="margin:0 0 14px;">Thank you for choosing YuktiOne. Your appointment is confirmed. Here are the details:</p>

    @include('emails.partials.info-card', [
        'title' => 'Appointment',
        'rows' => [
            ['label' => 'Professional', 'value' => e($professional->name)],
            ['label' => 'Service', 'value' => e($booking->service_name)],
            ['label' => 'Plan type', 'value' => e(ucwords(str_replace('_', ' ', $booking->plan_type)))],
        ],
    ])

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;background-color:#f8fbfa;border:1px solid #e2e8ee;border-radius:10px;">
        <tr>
            <td style="padding:16px 18px;">
                <p style="margin:0 0 12px;font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#316D61;">Scheduled sessions</p>
                @foreach ($bookingTimeDates as $timeDate)
                    <p style="margin:0 0 {{ $loop->last ? '0' : '8px' }};font-size:14px;color:#142B4D;">
                        <strong>{{ \Carbon\Carbon::parse($timeDate->date)->format('d M, Y') }}</strong>
                        &nbsp;·&nbsp;{{ $timeDate->time_slot }}
                    </p>
                @endforeach
            </td>
        </tr>
    </table>

    @include('emails.partials.info-card', [
        'title' => 'Payment',
        'rows' => [
            ['label' => 'Amount paid', 'value' => '₹'.number_format($booking->amount, 2)],
            ['label' => 'Payment status', 'value' => e(ucfirst($booking->payment_status))],
            ['label' => 'Transaction ID', 'value' => e($booking->payment_id)],
            ['label' => 'Payment date', 'value' => e($booking->created_at->format('d M, Y H:i'))],
        ],
    ])

    @include('emails.partials.note', [
        'text' => '<strong>Important:</strong> Please be available at the scheduled time. If you need to reschedule, contact us at least 24 hours in advance.',
    ])

    @include('emails.partials.button', [
        'url' => route('user.dashboard'),
        'label' => 'View Dashboard',
    ])
@endsection
