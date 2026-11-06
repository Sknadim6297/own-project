@extends('layouts.layout')

@section('title', 'Something went wrong | YuktiOne')
@section('meta_description', 'An unexpected error occurred. Please try again later.')

@section('content')
<x-yuktione.page title="Something went wrong" crumb="500">
    <div class="yk-doc__error">
        <strong>500</strong>
        <h2>We hit an unexpected problem</h2>
        <p class="yk-doc__lead">Please try again in a moment. If it continues, contact support and we will help.</p>
        <div class="yk-doc__actions">
            <a class="yk-doc__button" href="{{ route('home') }}">Go home</a>
            <a class="yk-doc__button-secondary" href="javascript:history.back()">Go back</a>
            <a class="yk-doc__button-secondary" href="{{ route('contact') }}">Contact</a>
        </div>
    </div>
</x-yuktione.page>
@endsection
