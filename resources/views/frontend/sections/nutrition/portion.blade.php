@extends('layouts.layout')

@section('title', 'Set Portion | '.$food->name.' | YuktiOne')
@section('meta_description', 'Choose a quantity and review estimated nutrition for this food.')
@section('body_class', 'yk-nut-body')

@section('content')
@php
    $fmt = fn ($v) => $v === null ? 'Incomplete' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $defaultUnit = $food->reference_basis === 'per_100g' ? 'g' : optional($food->servings->first())->unit;
    $qty = old('quantity', request('quantity', $food->reference_basis === 'per_100g' ? '100' : optional($food->servings->first())->quantity));
    $unit = old('unit', request('unit', $defaultUnit));
@endphp
<main class="yk-nut yk-tools-ds" data-yk-tools-ds data-yk-nut>
    <div class="yk-nut__wrap yk-nut__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-nut__card" aria-labelledby="nut-portion-heading">
            <p class="yk-nut__badge">Set portion</p>
            <h1 id="nut-portion-heading">{{ $food->name }}</h1>
            @if ($food->brand)
                <p class="yk-nut__lead">{{ $food->brand }}@if ($food->variant) · {{ $food->variant }} @endif</p>
            @endif
            <div class="yk-nut__meta-row">
                <span class="yk-nut__meta">
                    {{ \App\Support\Nutrition\NutritionCatalog::PREPARATION_STATES[$food->preparation_state] ?? $food->preparation_state }}
                    · {{ \App\Support\Nutrition\NutritionCatalog::REFERENCE_BASES[$food->reference_basis] ?? $food->reference_basis }}
                </span>
                <span @class(['yk-nut__pill', 'is-verified' => $food->verification_status === 'verified'])>
                    {{ $food->verification_status === 'verified' ? 'Verified' : 'Unverified' }}
                </span>
            </div>
            @if ($food->source_name)
                <p class="yk-nut__note">Source: {{ $food->source_name }}</p>
            @endif

            <form method="get" action="{{ route('tools.nutrition.portion', $food) }}" class="yk-nut__portion-form">
                <input type="hidden" name="meal" value="{{ $meal }}">
                <div class="yk-nut__search-grid">
                    @if ($food->servings->isNotEmpty())
                        <div class="yk-nut__field">
                            <label for="serving_id">Serving definition</label>
                            <select id="serving_id" name="serving_id">
                                @foreach ($food->servings as $serving)
                                    <option value="{{ $serving->id }}" @selected((string) request('serving_id') === (string) $serving->id)>
                                        {{ $serving->label }} ({{ $serving->quantity }} {{ $serving->unit }}@if ($serving->grams_equivalent) ≈ {{ $serving->grams_equivalent }} g @endif)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="yk-nut__field">
                        <label for="quantity">Quantity</label>
                        <input id="quantity" type="number" name="quantity" value="{{ $qty }}" min="0.01" step="0.01" inputmode="decimal" required>
                    </div>
                    <div class="yk-nut__field">
                        <label for="unit">Unit</label>
                        <input id="unit" type="text" name="unit" value="{{ $unit }}" list="nut-units" required maxlength="40">
                        <datalist id="nut-units">
                            <option value="g"></option>
                            @foreach ($food->servings as $serving)
                                <option value="{{ $serving->unit }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>
                <div class="yk-nut__actions">
                    <button class="yk-nut__btn-secondary" type="submit">Preview nutrition</button>
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.search', ['meal' => $meal]) }}">Back to search</a>
                </div>
            </form>

            @if ($error)
                <p class="yk-nut__error" role="alert">{{ $error }}</p>
            @endif

            @if ($preview)
                <div class="yk-nut__stats yk-nut__stats--reveal" style="margin-top:1rem;">
                    <div><strong>{{ $fmt($preview['calories_kcal']) }}</strong><span>Calories</span></div>
                    <div><strong>{{ $fmt($preview['protein_g']) }}</strong><span>Protein</span></div>
                    <div><strong>{{ $fmt($preview['carbs_g']) }}</strong><span>Carbs</span></div>
                    <div><strong>{{ $fmt($preview['fat_g']) }}</strong><span>Fat</span></div>
                </div>
                @if ($preview['incomplete'])
                    <p class="yk-nut__note">Incomplete: missing {{ implode(', ', $preview['missing']) }}.</p>
                @endif
            @endif

            @if ($authenticated)
                <form method="post" action="{{ route('tools.nutrition.entries.store') }}" class="yk-nut__add-form" data-nut-submit-once>
                    @csrf
                    <input type="hidden" name="food_id" value="{{ $food->id }}">
                    <input type="hidden" name="quantity" value="{{ $qty }}">
                    <input type="hidden" name="unit" value="{{ $unit }}">
                    <input type="hidden" name="serving_id" value="{{ request('serving_id') }}">
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <div class="yk-nut__field">
                        <label for="meal_type">Add to meal</label>
                        <select id="meal_type" name="meal_type">
                            @foreach ($meals as $key => $label)
                                <option value="{{ $key }}" @selected($meal === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="yk-nut__actions">
                        <button class="yk-nut__btn" type="submit" @disabled(! $preview)>Add to meal</button>
                    </div>
                    @unless ($preview)
                        <p class="yk-nut__note">Preview nutrition first to confirm the portion estimate.</p>
                    @endunless
                </form>
            @else
                <p class="yk-nut__note">Sign in to add this food to a meal diary.</p>
                <div class="yk-nut__actions">
                    <a class="yk-nut__btn" href="{{ route('login') }}">Sign in to add</a>
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
