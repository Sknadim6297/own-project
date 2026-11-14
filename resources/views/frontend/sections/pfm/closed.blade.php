@extends('layouts.layout')

@section('title', ($tool['name'] ?? 'Tool').' | YuktiOne')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    <div class="yk-pfm__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-pfm__card">
            <h1>{{ $tool['name'] ?? 'Calculator' }}</h1>
            <p class="yk-pfm__lead">This calculator is unavailable right now.</p>
        </section>
    </div>
</main>
@endsection
