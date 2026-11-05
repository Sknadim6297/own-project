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
    <div class="yk-nut__wrap yk-nut__wrap--wide">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">{{ $summary['date'] }} · Build meal</p>
            <h1>{{ $block['label'] }}</h1>
            <p class="yk-nut__lead">Nutrition values are stored as snapshots from the time of logging.</p>

            @if (session('status'))
                <p class="yk-nut__ok" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-nut__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <nav class="yk-nut__tabs" aria-label="Meal type">
                @foreach ($meals as $key => $label)
                    <a href="{{ route('tools.nutrition.meal', ['date' => $summary['date'], 'meal' => $key]) }}"
                       @class(['is-active' => $key === $meal])>{{ $label }}</a>
                @endforeach
            </nav>

            <div class="yk-nut__stats">
                <div><strong>{{ $fmt($block['totals']['calories_kcal']) }}</strong><span>Meal kcal</span></div>
                <div><strong>{{ $fmt($block['totals']['protein_g']) }}</strong><span>Protein</span></div>
                <div><strong>{{ $fmt($block['totals']['carbs_g']) }}</strong><span>Carbs</span></div>
                <div><strong>{{ $fmt($block['totals']['fat_g']) }}</strong><span>Fat</span></div>
            </div>
            <p class="yk-nut__note">Daily total: {{ $fmt($summary['totals']['calories_kcal']) }} kcal</p>

            @if ($block['entries']->isEmpty())
                <div class="yk-nut__empty" role="status">No foods logged for this meal yet.</div>
            @else
                <ul class="yk-nut__list yk-nut__list--manage">
                    @foreach ($block['entries'] as $entry)
                        <li>
                            <div>
                                <strong>{{ $entry->food_name_snapshot }}</strong>
                                @if ($entry->brand_snapshot)
                                    <div class="yk-nut__meta">{{ $entry->brand_snapshot }}</div>
                                @endif
                                <div class="yk-nut__meta">
                                    {{ $entry->quantity }} {{ $entry->unit }} · {{ $fmt($entry->calories_kcal_snapshot) }} kcal
                                    @if ($entry->incomplete_nutrients) · incomplete @endif
                                    · {{ $entry->consumed_at->timezone($timezone)->format('g:i A') }}
                                </div>
                                <form method="post" action="{{ route('tools.nutrition.entries.update', $entry) }}" class="yk-nut__edit-inline" data-nut-submit-once>
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="meal_type" value="{{ $entry->meal_type }}">
                                    <label class="visually-hidden" for="qty-{{ $entry->id }}">Quantity</label>
                                    <input id="qty-{{ $entry->id }}" type="number" name="quantity" value="{{ $entry->quantity }}" min="0.01" step="0.01" required>
                                    <label class="visually-hidden" for="unit-{{ $entry->id }}">Unit</label>
                                    <input id="unit-{{ $entry->id }}" type="text" name="unit" value="{{ $entry->unit }}" required>
                                    <button class="yk-nut__btn-secondary" type="submit">Update</button>
                                </form>
                            </div>
                            <form method="post" action="{{ route('tools.nutrition.entries.destroy', $entry) }}" onsubmit="return confirm('Remove this food entry?');" data-nut-submit-once>
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
<script>
(function () {
    document.querySelectorAll('[data-nut-submit-once]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-submitting') === '1') {
                event.preventDefault();
                return;
            }
            form.setAttribute('data-submitting', '1');
            form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.disabled = true;
            });
        });
    });
})();
</script>
@endsection
