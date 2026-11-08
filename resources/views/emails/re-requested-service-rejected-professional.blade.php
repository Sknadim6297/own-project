@extends('emails.layouts.base')

@section('title', 'Service request not approved')
@section('preheader', 'Your re-requested service was not approved.')
@section('heading', 'Service request not approved')
@section('subtitle', 'Please review the admin decision below')

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $reRequestedService->professional->name }},</p>
    <p style="margin:0 0 14px;">Your re-requested service was not approved by the YuktiOne admin team.</p>

    @include('emails.partials.info-card', [
        'title' => 'Request details',
        'rows' => array_values(array_filter([
            ['label' => 'Customer', 'value' => e($reRequestedService->customer->name)],
            ['label' => 'Service', 'value' => e($reRequestedService->service_name)],
            ['label' => 'Requested amount', 'value' => '₹'.number_format($reRequestedService->original_price, 2)],
            $reRequestedService->admin_notes ? ['label' => 'Admin notes', 'value' => e($reRequestedService->admin_notes)] : null,
        ])),
    ])

    <p style="margin:0 0 14px;">You may review the request and submit an updated request if appropriate.</p>

    @include('emails.partials.button', [
        'url' => url('/professional/re-requested-service/'.$reRequestedService->id),
        'label' => 'View request details',
    ])
@endsection
