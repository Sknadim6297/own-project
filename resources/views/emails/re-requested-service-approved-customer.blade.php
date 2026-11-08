@extends('emails.layouts.base')

@section('title', 'Additional service approved')
@section('preheader', 'Your additional service request is approved. Payment is ready.')
@section('heading', 'Additional service approved')
@section('subtitle', 'Payment is ready for the approved request')

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $reRequestedService->customer->name }},</p>
    <p style="margin:0 0 14px;">
        Your professional <strong>{{ $reRequestedService->professional->name }}</strong> requested an additional service, and it has been approved.
    </p>

    @include('emails.partials.info-card', [
        'title' => 'Service details',
        'rows' => array_values(array_filter([
            ['label' => 'Service', 'value' => e($reRequestedService->service_name)],
            ['label' => 'Reason', 'value' => e($reRequestedService->reason)],
            $reRequestedService->admin_notes ? ['label' => 'Admin notes', 'value' => e($reRequestedService->admin_notes)] : null,
        ])),
    ])

    @include('emails.partials.info-card', [
        'title' => 'Payment summary',
        'rows' => [
            ['label' => 'Service amount', 'value' => '₹'.number_format($reRequestedService->final_price, 2)],
            ['label' => 'GST (18%)', 'value' => '₹'.number_format($reRequestedService->gst_amount, 2)],
            ['label' => 'Total amount', 'value' => '₹'.number_format($reRequestedService->total_amount, 2)],
        ],
    ])

    <p style="margin:0 0 8px;">You can complete payment now, or return later from your dashboard.</p>

    @include('emails.partials.button', [
        'url' => url('/customer/re-requested-service/'.$reRequestedService->id.'/payment'),
        'label' => 'Pay now · ₹'.number_format($reRequestedService->total_amount, 2),
    ])
@endsection
