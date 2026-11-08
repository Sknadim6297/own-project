@extends('emails.layouts.base')

@section('title', 'Additional service not approved')
@section('preheader', 'An update on your additional service request.')
@section('heading', 'Additional service not approved')
@section('subtitle', 'Please review the request status below')

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $reRequestedService->customer->name }},</p>
    <p style="margin:0 0 14px;">
        The additional service requested by <strong>{{ $reRequestedService->professional->name }}</strong> was not approved at this time.
    </p>

    @include('emails.partials.info-card', [
        'title' => 'Request details',
        'rows' => array_values(array_filter([
            ['label' => 'Service', 'value' => e($reRequestedService->service_name)],
            ['label' => 'Reason', 'value' => e($reRequestedService->reason)],
            $reRequestedService->admin_notes ? ['label' => 'Admin notes', 'value' => e($reRequestedService->admin_notes)] : null,
        ])),
    ])

    <p style="margin:0 0 14px;">No payment is required for this request. If you have questions, contact support.</p>

    @include('emails.partials.button', [
        'url' => route('user.dashboard'),
        'label' => 'View Dashboard',
    ])
@endsection
