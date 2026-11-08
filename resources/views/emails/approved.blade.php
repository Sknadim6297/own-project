@extends('emails.layouts.base')

@section('title', 'Application approved')
@section('preheader', 'Your professional application has been approved on YuktiOne.')
@section('heading', 'Application approved')
@section('subtitle', 'Welcome to the YuktiOne professional network')

@section('content')
    <p style="margin:0 0 16px;">Hello {{ $name }},</p>
    <p style="margin:0 0 14px;">We are pleased to let you know that your professional application has been approved.</p>
    <p style="margin:0 0 14px;">Thank you for your patience during the review process. You can now continue setting up your profile and services on YuktiOne.</p>
    @if (!empty($approvalMessage))
        <p style="margin:0 0 14px;">{{ $approvalMessage }}</p>
    @endif
    <p style="margin:0 0 14px;">If you need help getting started, reply is not required — contact us at <a href="mailto:{{ config('yuktione.email', 'info@yuktione.in') }}" style="color:#316D61;text-decoration:none;">{{ config('yuktione.email', 'info@yuktione.in') }}</a>.</p>

    @include('emails.partials.button', [
        'url' => route('professional.login'),
        'label' => 'Go to Dashboard',
    ])
@endsection
