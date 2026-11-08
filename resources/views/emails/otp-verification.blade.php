@extends('emails.layouts.base')

@section('title', 'Verify your email')
@section('preheader', 'Your YuktiOne verification code is ready.')
@section('heading', 'Verify your email address')
@section('subtitle', 'Use this one-time code to complete registration')

@section('content')
    <p style="margin:0 0 16px;">
        Hello{{ !empty($customerName) ? ' '.$customerName : '' }},
    </p>
    <p style="margin:0 0 18px;">
        Thank you for registering with {{ config('yuktione.name', 'YuktiOne') }}. Enter the verification code below on the registration page to continue.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 18px;background-color:#EAF6F2;border:1px solid #d5e8e1;border-radius:10px;">
        <tr>
            <td align="center" style="padding:20px 16px;font-size:32px;letter-spacing:8px;font-weight:700;color:#142B4D;">
                {{ $otp }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px;">This code will expire in 10 minutes.</p>

    @include('emails.partials.note', [
        'text' => '<strong>Security note:</strong> If you did not request this verification code, you can safely ignore this email.',
    ])
@endsection
