@extends('emails.layouts.base')

@section('title', $subject ?? 'YuktiOne notification')
@section('preheader', 'You have a new message from the YuktiOne administration team.')
@section('heading', $subject ?? 'Admin notification')
@section('subtitle', 'Official communication from YuktiOne')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $recipientName }},</p>
    <p style="margin:0 0 14px;">The YuktiOne administration team wants to share the following update:</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 18px;background-color:#f8fbfa;border:1px solid #e2e8ee;border-radius:10px;">
        <tr>
            <td style="padding:18px;font-size:15px;line-height:1.65;color:#142B4D;">
                {!! nl2br(e($messageContent)) !!}
            </td>
        </tr>
    </table>

    <p style="margin:0;color:#5b6b7c;font-size:14px;">
        If you have questions about this message, contact
        <a href="mailto:{{ config('yuktione.email', 'info@yuktione.in') }}" style="color:#316D61;text-decoration:none;">{{ config('yuktione.email', 'info@yuktione.in') }}</a>.
    </p>
@endsection
