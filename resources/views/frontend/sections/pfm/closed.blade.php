@extends('layouts.layout')

@section('title', ($tool['name'] ?? 'Tool').' | YuktiOne')
@section('body_class', 'yk-pfm-body')

@section('content')
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    @include('frontend.sections.pfm.partials.page-hero', [
        'crumbs' => [['Money & Finance', route('tools.pfm')], [$tool['name'] ?? 'Calculator', null]],
        'eyebrow' => 'Calculator',
        'title' => $tool['name'] ?? 'Calculator',
        'titleId' => 'pfm-closed-heading',
        'lead' => 'This calculator is unavailable right now.',
    ])

    <div class="yk-pfm__wrap yk-pfm__sheet">
        <section class="yk-pfm__card" aria-labelledby="pfm-closed-heading">
            <div class="yk-pfm__empty" role="status">
                <p class="yk-pfm__empty-title">This calculator is unavailable right now.</p>
                <p>Please check back soon, or try another Money &amp; Finance tool.</p>
                <a class="yk-pfm__btn" href="{{ route('tools.pfm') }}">Browse all calculators</a>
            </div>
        </section>
    </div>
</main>
@endsection
