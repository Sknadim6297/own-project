@extends('layouts.layout')

@section('title', 'Tools | YuktiOne')
@section('meta_description', 'Practical tools and resources to support your mental, emotional and personal growth.')
@section('body_class', 'yk-tools-page')



@section('content')
<main class="yk-tools" id="tools" data-tools>
    <div class="yk-tools__wrap">
        <header class="yk-tools__hero">
            <div class="yk-tools__hero-copy">
                <p class="yk-tools__eyebrow"><span class="yk-tools__eyebrow-mark" aria-hidden="true"></span>YUKTI TOOLS</p>
                <h1 id="tools-heading">Try Our <span>Tools</span></h1>
                <p class="yk-tools__lead">Practical tools for mental, emotional and personal growth — open what you need, when you need it.</p>
            </div>
            <div class="yk-tools__hero-visual">
                <img
                    class="yk-tools__hero-img"
                    src="{{ asset('frontend/assets/img/yuktione-tools-hero.png') }}"
                    alt="Mindful wellness — calm focus with growth, health and clarity"
                    width="900"
                    height="900"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        </header>

        @include('frontend.sections.partials.tools-catalog', [
            'tItems' => $items,
            'tFilters' => $filters,
            'tCategories' => $categories,
            'tCounts' => $counts ?? [],
            'tRoute' => 'tools.index',
            'tExtra' => [],
        ])
    </div>
</main>
@endsection
