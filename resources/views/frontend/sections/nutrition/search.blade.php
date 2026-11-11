@extends('layouts.layout')

@section('title', 'Search Food | Calorie & Nutrition Tracker | YuktiOne')
@section('meta_description', 'Search the YuktiOne food catalogue by name, brand, category, or preparation.')
@section('body_class', 'yk-nut-body')

@section('content')
@php
    $catalogueReady = ($catalogueCount ?? 0) > 0;
    $mealLabel = \App\Support\Nutrition\NutritionCatalog::MEAL_TYPES[$meal] ?? ($meal ?: 'Meal');
@endphp
<main class="yk-nut yk-tools-ds" data-yk-tools-ds data-yk-nut>
    <div class="yk-nut__wrap yk-nut__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-nut__card" aria-labelledby="nut-search-heading">
            <p class="yk-nut__badge">Food search · {{ $mealLabel }}</p>
            <h1 id="nut-search-heading">Search food</h1>
            <p class="yk-nut__lead">Published catalogue only. Unverified foods show a clear badge.</p>

            @include('frontend.sections.nutrition.partials.catalogue-gate', [
                'catalogueCount' => $catalogueCount ?? 0,
                'importWorkbooksMissing' => $importWorkbooksMissing ?? false,
            ])

            <form method="get" action="{{ route('tools.nutrition.search') }}" class="yk-nut__search-form" data-nut-search>
                <input type="hidden" name="meal" value="{{ $meal }}">
                <div class="yk-nut__search-grid">
                    <div class="yk-nut__field">
                        <label for="q">Food or brand</label>
                        <input id="q" type="search" name="q" value="{{ $q }}" placeholder="e.g. rice, yogurt" maxlength="120">
                    </div>
                    <div class="yk-nut__field">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((string) $category === (string) $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="yk-nut__field">
                        <label for="state">Preparation</label>
                        <select id="state" name="state">
                            <option value="">Any</option>
                            @foreach ($states as $key => $label)
                                <option value="{{ $key }}" @selected($state === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="yk-nut__actions" style="margin-top:0.25rem;">
                    <button class="yk-nut__btn" type="submit">Search</button>
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition') }}">Dashboard</a>
                </div>
            </form>

            @if ($foods->isEmpty())
                <div class="yk-nut__empty" role="status" style="margin-top:1rem;">
                    @if (! $catalogueReady)
                        No matching foods in the catalogue. The catalogue may still be empty pending import review.
                    @elseif ($q !== '' || $category || $state)
                        No matching foods for these filters.
                    @else
                        No published foods available.
                    @endif
                </div>
            @else
                <ul class="yk-nut__list yk-nut__list--foods" style="margin-top:1rem;">
                    @foreach ($foods as $food)
                        <li>
                            <div>
                                <strong>{{ $food->name }}</strong>
                                @if ($food->brand)
                                    <div class="yk-nut__meta">{{ $food->brand }}@if ($food->variant) · {{ $food->variant }} @endif</div>
                                @endif
                                <div class="yk-nut__meta">
                                    {{ $states[$food->preparation_state] ?? $food->preparation_state }}
                                    @if ($food->category) · {{ $food->category->name }} @endif
                                </div>
                                <span @class(['yk-nut__pill', 'is-verified' => $food->verification_status === 'verified'])>
                                    {{ $food->verification_status === 'verified' ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>
                            <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.portion', ['food' => $food, 'meal' => $meal]) }}">Select</a>
                        </li>
                    @endforeach
                </ul>
                <div class="yk-nut__pages">{{ $foods->links() }}</div>
            @endif

            @unless ($authenticated)
                <p class="yk-nut__note">Browse as a guest. Logging requires sign-in.</p>
            @endunless
        </section>
    </div>
</main>
@endsection
