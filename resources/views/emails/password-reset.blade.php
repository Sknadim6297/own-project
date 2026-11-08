@extends('emails.layouts.base')

@section('title', ($brand ?? config('yuktione.name', 'YuktiOne')).' Password Reset')
@section('preheader', 'Reset your '.($brand ?? config('yuktione.name', 'YuktiOne')).' password securely.')
@section('heading', 'Password reset')
@section('subtitle', 'A secure link to update your account password')

@section('content')
    <p style="margin:0 0 16px;">{{ $intro }}</p>

    @include('emails.partials.button', [
        'url' => $actionUrl,
        'label' => $actionText,
    ])

    <p style="margin:0 0 12px;">{{ $expireText }}</p>
    <p style="margin:0 0 12px;">{{ $outro }}</p>

    <p style="margin:20px 0 0;font-size:12px;line-height:1.55;color:#64748b;word-break:break-all;">
        If the button does not work, copy and paste this link into your browser:<br>
        <a href="{{ $actionUrl }}" style="color:#316D61;">{{ $actionUrl }}</a>
    </p>
@endsection
