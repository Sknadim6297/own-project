@extends('layouts.layout')

@section('title', ($title ?? 'Error') . ' | YuktiOne')
@section('meta_description', $message ?? 'An unexpected error occurred.')

@section('content')
@php
    $code = $statusCode ?? 500;
    $heading = match ((int) $code) {
        404 => 'Page not found',
        403 => 'Access forbidden',
        503 => 'Service unavailable',
        default => 'Something went wrong',
    };
    $copy = $message ?? match ((int) $code) {
        404 => 'The address may have changed, or the page may have been removed.',
        403 => 'You do not have permission to view this page.',
        503 => 'The service is temporarily unavailable. Please try again later.',
        default => 'Please try again in a moment. If it continues, contact support.',
    };
@endphp
<x-yuktione.page title="{{ $heading }}" crumb="{{ $code }}">
    <div class="yk-doc__error">
        <strong>{{ $code }}</strong>
        <h2>{{ $heading }}</h2>
        <p class="yk-doc__lead">{{ $copy }}</p>
        <div class="yk-doc__actions" style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
            <a class="yk-doc__button" href="{{ route('home') }}">Go home</a>
            <a class="yk-doc__button-secondary" href="javascript:history.back()">Go back</a>
        </div>
    </div>
</x-yuktione.page>
@endsection
