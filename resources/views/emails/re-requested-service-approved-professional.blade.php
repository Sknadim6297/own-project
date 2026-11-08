@extends('emails.layouts.base')

@section('title', 'Service request approved')
@section('preheader', 'Your re-requested service was approved by YuktiOne admin.')
@section('heading', 'Service request approved')
@section('subtitle', 'The customer has been notified about payment')

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $reRequestedService->professional->name }},</p>
    <p style="margin:0 0 14px;">Your re-requested service has been approved by the YuktiOne admin team.</p>

    @include('emails.partials.info-card', [
        'title' => 'Request details',
        'rows' => array_values(array_filter([
            ['label' => 'Customer', 'value' => e($reRequestedService->customer->name)],
            ['label' => 'Service', 'value' => e($reRequestedService->service_name)],
            ['label' => 'Requested amount', 'value' => '₹'.number_format($reRequestedService->original_price, 2)],
            $reRequestedService->admin_modified_price ? ['label' => 'Approved amount', 'value' => '₹'.number_format($reRequestedService->final_price, 2)] : null,
            ['label' => 'Total with GST', 'value' => '₹'.number_format($reRequestedService->total_amount, 2)],
            $reRequestedService->admin_notes ? ['label' => 'Admin notes', 'value' => e($reRequestedService->admin_notes)] : null,
        ])),
    ])

    <p style="margin:0 0 8px;">The customer can now complete payment. Once paid, you can begin providing the additional service.</p>

    @include('emails.partials.button', [
        'url' => url('/professional/re-requested-service/'.$reRequestedService->id),
        'label' => 'View request details',
    ])

    <p style="margin:8px 0 6px;font-weight:700;color:#142B4D;">Next steps</p>
    <ul style="margin:0;padding-left:18px;color:#334155;">
        <li style="margin-bottom:6px;">Wait for customer payment confirmation</li>
        <li style="margin-bottom:6px;">Start the service once payment is complete</li>
        <li>Track progress from your dashboard</li>
    </ul>
@endsection
