@extends('layouts.layout')

@section('title', ($tool['name'] ?? 'Tool').' | YuktiOne')
@section('body_class', 'yk-ps-body')

@section('content')
<main class="yk-ps yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-ps__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-ps__panel yk-ps__panel--closed">
            <span class="yk-ps__empty-icon">@include('frontend.sections.ps.partials.icon', ['name' => 'lock', 'size' => 30])</span>
            <h1>{{ $tool['name'] ?? 'Tool' }}</h1>
            <p class="yk-ps__error" role="alert">@include('frontend.sections.ps.partials.icon', ['name' => 'alert', 'size' => 18]) <span>This tool is unavailable right now.</span></p>
            <a class="yk-ps__btn" href="{{ route('tools.ps') }}">Back to Parenting &amp; Spirituality tools</a>
        </section>
    </div>
</main>
@endsection
