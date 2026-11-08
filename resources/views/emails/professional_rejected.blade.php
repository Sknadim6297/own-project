@extends('emails.layouts.base')

@section('title', 'Application update')
@section('preheader', 'An update on your YuktiOne professional application.')
@section('heading', 'Application not approved')
@section('subtitle', 'Please review the reason below')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $professional->name }},</p>
    <p style="margin:0 0 14px;">Thank you for applying to YuktiOne. After careful review, we are unable to approve your application at this time.</p>

    @include('emails.partials.info-card', [
        'title' => 'Reason',
        'rows' => [
            ['label' => 'Details', 'value' => e($reason)],
        ],
    ])

    <p style="margin:0 0 14px;">If you believe this was a mistake or you would like to update your information, you may log in and resubmit the required details for reconsideration.</p>

    @include('emails.partials.button', [
        'url' => route('professional.login'),
        'label' => 'Sign in to update',
    ])
@endsection
