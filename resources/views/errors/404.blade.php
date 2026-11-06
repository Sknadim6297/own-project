@extends('layouts.layout')

@section('title', 'Page not found | YuktiOne')
@section('meta_description', 'The page you requested is not available.')

@section('content')
<x-yuktione.page title="Page not found" crumb="404">
    <div class="yk-doc__error">
        <strong>404</strong>
        <h2>That page is not available</h2>
        <p class="yk-doc__lead">The address may have changed, or the page may have been removed.</p>
        <div class="yk-doc__actions">
            <a class="yk-doc__button" href="{{ route('home') }}">Go home</a>
            <a class="yk-doc__button-secondary" href="javascript:history.back()">Go back</a>
            <a class="yk-doc__button-secondary" href="{{ route('gridlisting') }}">Explore</a>
        </div>
    </div>
</x-yuktione.page>
@endsection
