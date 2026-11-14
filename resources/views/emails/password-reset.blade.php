@extends('emails.layouts.base')

@section('title', ($brand ?? config('yuktione.name', 'YuktiOne')).' Password Reset')
@section('preheader', 'Reset your '.($brand ?? config('yuktione.name', 'YuktiOne')).' password securely.')
@section('heading', 'Password reset')
@section('subtitle', 'A secure link to update your account password')

@section('content')
    @php
        // Avoid clashing with MailMessage::$introLines / $outroLines (often empty arrays).
        $bodyIntroLines = $messageIntroLines ?? (isset($intro) ? [$intro] : []);
        $bodyOutroLines = $messageOutroLines ?? (isset($outro) ? [$outro] : []);
    @endphp

    @foreach($bodyIntroLines as $line)
        <p style="margin:0 0 16px;">{{ $line }}</p>
    @endforeach

    @include('emails.partials.button', [
        'url' => $actionUrl,
        'label' => $actionText,
    ])

    <p style="margin:0 0 12px;">{{ $expireText }}</p>

    @foreach($bodyOutroLines as $line)
        <p style="margin:0 0 12px;">{{ $line }}</p>
    @endforeach

    <p style="margin:20px 0 0;font-size:12px;line-height:1.55;color:#64748b;word-break:break-all;">
        If the button does not work, copy and paste this link into your browser:<br>
        <a href="{{ $actionUrl }}" style="color:#316D61;">{{ $actionUrl }}</a>
    </p>
@endsection
