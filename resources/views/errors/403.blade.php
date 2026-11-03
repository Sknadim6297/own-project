@extends('layouts.layout')

@section('title', 'Access forbidden | YuktiOne')
@section('meta_description', 'You do not have permission to view this page.')

@section('content')
<x-yuktione.page title="Access forbidden" crumb="403">
    <div class="yk-doc__error">
        <strong>403</strong>
        <h2>You do not have access</h2>
        <p class="yk-doc__lead">Sorry, you do not have permission to view this page. If you believe this is a mistake, contact support.</p>
        <div class="yk-doc__actions" style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
            <a class="yk-doc__button" href="{{ route('home') }}">Go home</a>
            <a class="yk-doc__button-secondary" href="javascript:history.back()">Go back</a>
            <a class="yk-doc__button-secondary" href="{{ url('/contact') }}">Contact</a>
        </div>
    </div>
</x-yuktione.page>
@endsection
