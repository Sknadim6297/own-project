@extends('emails.layouts.base')

@section('title', 'Payment received')
@section('preheader', 'We have received your payment on YuktiOne.')
@section('heading', 'Payment received')
@section('subtitle', 'Thank you — your payment was successful')

@section('content')
    <p style="margin:0 0 16px;">Hello,</p>
    <p style="margin:0 0 14px;">We have successfully received your payment on YuktiOne. You can review the related booking or account details from your dashboard.</p>

    @include('emails.partials.button', [
        'url' => route('user.dashboard'),
        'label' => 'View Dashboard',
    ])
@endsection
