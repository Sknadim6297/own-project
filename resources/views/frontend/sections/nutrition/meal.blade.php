@extends('layouts.layout')

@section('title', ($meals[$meal] ?? 'Meal').' | Calorie & Nutrition Tracker | YuktiOne')
@section('meta_description', 'Build and edit a meal from saved food entries.')
@section('body_class', 'yk-nut-body')

@section('content')
@php
    $block = $summary['by_meal'][$meal];
    $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
@endphp
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">{{ $summary['date'] }} · Build meal</p>
            <h1>{{ $block['label'] }}</h1>
            <p class="yk-nut__lead">Multiple foods per meal are allowed. Nutrition values are stored as snapshots.</p>

            @if (session('status'))
                <p class="yk-nut__ok">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-nut__error">{{ $errors->first() }}</p>
            @endif

            <div class="yk-nut__actions" style="margin-bottom:1rem;">
                @foreach ($meals as $key => $label)
                    <a @class(['yk-nut__btn' => $key === $meal, 'yk-nut__btn-secondary' => $key !== $meal])
                       href="{{ route('tools.nutrition.meal', ['date' => $summary['date'], 'meal' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="yk-nut__stats">
                <div><strong>{{ $fmt($block['totals']['calories_kcal']) }}</strong><span>Meal kcal</span></div>
                <div><strong>{{ $fmt($block['totals']['protein_g']) }}</strong><span>Protein</span></div>
                <div><strong>{{ $fmt($block['totals']['carbs_g']) }}</strong><span>Carbs</span></div>
                <div><strong>{{ $fmt($block['totals']['fat_g']) }}</strong><span>Fat</span></div>
            </div>
            <p class="yk-nut__note">Daily total: {{ $fmt($summary['totals']['calories_kcal']) }} kcal</p>

            @if ($block['entries']->isEmpty())
                <p class="yk-nut__note">No foods logged for this meal yet.</p>
            @else
                <ul class="yk-nut__list">
                    @foreach ($block['entries'] as $entry)
                        <li>
                            <span>
                                <strong>{{ $entry->food_name_snapshot }}</strong>
                                @if ($entry->brand_snapshot)<span class="yk-nut__note" style="display:block;margin:0;">{{ $entry->brand_snapshot }}</span>@endif
                                <span class="yk-nut__note" style="display:block;margin:0;">
                                    {{ $entry->quantity }} {{ $entry->unit }} · {{ $fmt($entry->calories_kcal_snapshot) }} kcal
                                    @if ($entry->incomplete_nutrients) · incomplete nutrients @endif
                                </span>
                                <span class="yk-nut__note" style="display:block;margin:0;">
                                    {{ $entry->consumed_at->timezone($timezone)->format('g:i A') }}
                                </span>
                                <form method="post" action="{{ route('tools.nutrition.entries.update', $entry) }}" style="margin-top:0.5rem;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="meal_type" value="{{ $entry->meal_type }}">
                                    <label class="yk-nut__note" for="qty-{{ $entry->id }}">Quantity</label>
                                    <input id="qty-{{ $entry->id }}" type="number" name="quantity" value="{{ $entry->quantity }}" min="0.01" step="0.01" required style="max-width:7rem;display:inline-block;">
                                    <input type="text" name="unit" value="{{ $entry->unit }}" required style="max-width:5rem;display:inline-block;">
                                    <button class="yk-nut__btn-secondary" type="submit">Update</button>
                                </form>
                            </span>
                            <form method="post" action="{{ route('tools.nutrition.entries.destroy', $entry) }}" onsubmit="return confirm('Remove this food entry?');">
                                @csrf
                                @method('DELETE')
                                <button class="yk-nut__btn-secondary" type="submit">Remove</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="yk-nut__actions">
                <a class="yk-nut__btn" href="{{ route('tools.nutrition.search', ['meal' => $meal]) }}">Add food</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition', ['date' => $summary['date']]) }}">Dashboard</a>
            </div>
        </section>
    </div>
</main>
@endsection
