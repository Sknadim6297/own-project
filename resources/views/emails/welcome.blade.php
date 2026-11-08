@extends('emails.layouts.base')

@section('title', 'Welcome to YuktiOne')
@section('preheader', 'Welcome to YuktiOne — guidance for a brighter you.')
@section('heading', 'Welcome to YuktiOne')
@section('subtitle', 'Guidance for a brighter you')

@section('content')
    <p style="margin:0 0 16px;">Hello{{ !empty($name) ? ' '.$name : '' }},</p>
    <p style="margin:0 0 14px;">Welcome to YuktiOne. Explore trusted professionals, practical tools, and personalised guidance in one place.</p>

    @include('emails.partials.button', [
        'url' => url('/'),
        'label' => 'Explore YuktiOne',
    ])
@endsection
