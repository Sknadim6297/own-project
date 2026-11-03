@extends('layouts.layout')

@section('title', 'Search Food | Calorie & Nutrition Tracker | YuktiOne')
@section('meta_description', 'Search the YuktiOne food catalogue by name, brand, category, or preparation.')
@section('body_class', 'yk-nut-body')

@section('content')
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">Food search</p>
            <h1>Search food</h1>
            <p class="yk-nut__lead">Results come from the published catalogue only. Unverified foods show a clear badge.</p>

            <form method="get" action="{{ route('tools.nutrition.search') }}">
                <input type="hidden" name="meal" value="{{ $meal }}">
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
                <div class="yk-nut__actions">
                    <button class="yk-nut__btn" type="submit">Search</button>
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition') }}">Dashboard</a>
                </div>
            </form>

            @if ($foods->isEmpty())
                <p class="yk-nut__note" style="margin-top:1rem;">No matching foods in the catalogue. The catalogue may still be empty pending import review.</p>
            @else
                <ul class="yk-nut__list" style="margin-top:1rem;">
                    @foreach ($foods as $food)
                        <li>
                            <span>
                                <strong>{{ $food->name }}</strong>
                                @if ($food->brand)<span class="yk-nut__note" style="display:block;margin:0;">{{ $food->brand }}@if ($food->variant) · {{ $food->variant }} @endif</span>@endif

                                <span class="yk-nut__note" style="display:block;margin:0.15rem 0 0;">
                                    {{ $states[$food->preparation_state] ?? $food->preparation_state }}
                                    @if ($food->category) · {{ $food->category->name }} @endif
                                </span>
                                <span @class(['yk-nut__pill', 'is-verified' => $food->verification_status === 'verified'])>
                                    {{ $food->verification_status === 'verified' ? 'Verified' : 'Unverified' }}
                                </span>
                            </span>
                            <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.portion', ['food' => $food, 'meal' => $meal]) }}">Details</a>
                        </li>
                    @endforeach
                </ul>
                <div style="margin-top:1rem;">{{ $foods->links() }}</div>
            @endif

            @unless ($authenticated)
                <p class="yk-nut__note">You can browse foods as a guest. Logging a meal requires sign-in.</p>
            @endunless
        </section>
    </div>
</main>
@endsection
