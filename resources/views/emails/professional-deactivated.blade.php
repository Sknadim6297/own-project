@extends('emails.layouts.base')

@section('title', 'Account deactivation notice')
@section('preheader', 'Important update about your YuktiOne professional account.')
@section('heading', 'Account deactivation notice')
@section('subtitle', 'Your professional account status has changed')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $professional->name }},</p>
    <p style="margin:0 0 14px;">We regret to inform you that your professional account on YuktiOne has been <strong>deactivated</strong>.</p>
    <p style="margin:0 0 10px;">This may be related to one of the following:</p>
    <ul style="margin:0 0 16px;padding-left:18px;color:#334155;">
        <li style="margin-bottom:6px;">A review against our platform terms</li>
        <li style="margin-bottom:6px;">An extended period of inactivity</li>
        <li style="margin-bottom:6px;">An administrative review of your account</li>
        <li>A temporary suspension pending verification</li>
    </ul>
    <p style="margin:0 0 14px;">If you believe this was done in error or would like to request reactivation, please contact our support team.</p>

    @include('emails.partials.button', [
        'url' => 'mailto:'.config('yuktione.email', 'info@yuktione.in'),
        'label' => 'Contact Support',
    ])

    <p style="margin:8px 0 0;">Thank you for your understanding.</p>
@endsection
