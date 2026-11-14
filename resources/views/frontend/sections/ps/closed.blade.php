@extends('layouts.layout')

@section('title', ($tool['name'] ?? 'Tool').' | YuktiOne')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds">
    <div class="yk-pfm__wrap">
        <section class="yk-pfm__card">
            <h1>{{ $tool['name'] ?? 'Tool' }}</h1>
            <p class="yk-pfm__error" role="alert">This tool is unavailable right now.</p>
            <a class="yk-pfm__btn" href="{{ route('tools.ps') }}">Back to tools</a>
        </section>
    </div>
</main>
@endsection
