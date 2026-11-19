@extends('layouts.layout')

@section('title', 'Career Interest Test unavailable | YuktiOne')
@section('meta_description', 'This assessment is unavailable right now.')
@section('body_class', 'yk-cit-body')

@section('content')
@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        <section class="yk-cit__card" aria-labelledby="cit-closed-heading">
            <div class="yk-cit__empty" style="margin-top:0">
                <span class="yk-cit__empty-icon">{!! $icon('compass', 22) !!}</span>
                <p class="yk-cit__badge">Free tool</p>
                <h1 id="cit-closed-heading">Career Interest Test</h1>
                <p class="yk-cit__lead" style="font-weight:600">This assessment is unavailable right now.</p>
                <div class="yk-cit__actions" style="justify-content:center">
                    <a class="yk-cit__btn" href="{{ route('tools.index') }}">{!! $icon('arrow-left', 18) !!} Back to Tools</a>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
