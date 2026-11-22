@extends('layouts.layout')

@section('title', 'Tools | YuktiOne')
@section('meta_description', 'Practical tools and resources to support your mental, emotional and personal growth.')
@section('body_class', 'yk-category-page')

@section('content')
<main class="yk-cat-detail" id="tools" data-tools>
    @include('frontend.sections.partials.catalog-page-hero', [
        'title' => 'Tools',
        'lead' => 'Practical tools for mental, emotional and personal growth — open what you need, when you need it.',
        'backHref' => route('categories.index'),
        'backLabel' => 'All Categories',
    ])

    <section class="yk-cat-detail__tabs-wrap" aria-label="Tools catalogue">
        <div class="yk-cat-detail__wrap">
            @include('frontend.sections.partials.catalog-section-nav', [
                'active' => 'tools',
                'counts' => ['tools' => $items->total()],
            ])

            @include('frontend.sections.partials.tools-catalog', [
                'tItems' => $items,
                'tFilters' => $filters,
                'tCategories' => $categories,
                'tCounts' => $counts ?? [],
                'tRoute' => 'tools.index',
                'tExtra' => [],
            ])
        </div>
    </section>
</main>
@endsection
