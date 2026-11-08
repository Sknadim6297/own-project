@extends('emails.layouts.base')

@section('title', 'Event booking confirmed')
@section('preheader', 'Your YuktiOne event registration is confirmed.')
@section('heading', 'Event booking confirmed')
@section('subtitle', 'Your registration details are below')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $bookingDetails['user_name'] }},</p>
    <p style="margin:0 0 14px;">Thank you for booking with YuktiOne. Your event registration is confirmed.</p>

    @if (isset($event->image) && $event->image)
        <img src="{{ asset('storage/'.$event->image) }}" alt="{{ $event->heading }}" width="536" style="display:block;width:100%;max-width:536px;height:auto;border-radius:10px;margin:0 0 18px;">
    @endif

    @include('emails.partials.info-card', [
        'title' => 'Event',
        'rows' => array_values(array_filter([
            ['label' => 'Event', 'value' => e($event->heading)],
            ['label' => 'Date', 'value' => isset($event->date) ? e(\Carbon\Carbon::parse($event->date)->format('l, d M Y')) : 'Date TBA'],
            ['label' => 'Type', 'value' => e(ucfirst($event->type ?? 'Offline'))],
            !empty($event->city) ? ['label' => 'Location', 'value' => e($event->city)] : null,
            ['label' => 'Persons', 'value' => e($bookingDetails['persons'].' '.($bookingDetails['persons'] > 1 ? 'persons' : 'person'))],
        ])),
    ])

    @include('emails.partials.info-card', [
        'title' => 'Payment',
        'rows' => [
            ['label' => 'Amount paid', 'value' => '₹'.number_format($bookingDetails['total_price'], 2)],
            ['label' => 'Transaction ID', 'value' => e($bookingDetails['payment_id'])],
            ['label' => 'Payment date', 'value' => e(\Carbon\Carbon::parse($bookingDetails['created_at'])->format('d M, Y H:i'))],
        ],
    ])

    @include('emails.partials.note', [
        'text' => '<strong>Important:</strong> Please save this confirmation. For offline events, bring a valid ID. For online events, joining details are typically shared before the event.',
    ])

    @include('emails.partials.button', [
        'url' => route('user.dashboard'),
        'label' => 'View Dashboard',
    ])
@endsection
