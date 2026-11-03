@extends('layouts.layout')

@section('title', 'Water Intake Dashboard | YuktiOne')
@section('meta_description', 'Today’s water intake against your personal goal.')
@section('body_class', 'yk-water-body')

@section('content')
@php
    $pct = min(100, $dash['percent']);
    $radius = 54;
    $circ = 2 * M_PI * $radius;
    $offset = $circ * (1 - ($pct / 100));
@endphp
<main class="yk-water">
    <div class="yk-water__wrap">
        <section class="yk-water__card" aria-labelledby="water-dash-heading">
            <p class="yk-water__badge">Today · Asia/Kolkata</p>
            <h1 id="water-dash-heading">Water intake</h1>
            <p class="yk-water__lead">Personal tracking only. Your goal is a preference, not a prescribed medical target.</p>

            @if ($status)
                <p class="yk-water__ok">{{ $status }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-water__error">{{ $errors->first() }}</p>
            @endif

            <div class="yk-water__progress" role="img" aria-label="{{ $dash['percent'] }} percent of daily goal">
                <svg viewBox="0 0 140 140" aria-hidden="true">
                    <circle cx="70" cy="70" r="{{ $radius }}" fill="none" stroke="#edf1f6" stroke-width="12"></circle>
                    <circle cx="70" cy="70" r="{{ $radius }}" fill="none" stroke="#316d61" stroke-width="12"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $circ }}"
                        stroke-dashoffset="{{ $offset }}"
                        transform="rotate(-90 70 70)"></circle>
                </svg>
                <div class="yk-water__progress-center">
                    <strong>{{ $dash['percent'] }}%</strong>
                    <span>{{ $dash['total_label'] }}</span>
                </div>
            </div>

            <div class="yk-water__stats">
                <div><strong>{{ $dash['total_label'] }}</strong><span>Consumed</span></div>
                <div><strong>{{ $dash['goal_label'] }}</strong><span>Daily goal</span></div>
                <div><strong>{{ $dash['remaining_label'] }}</strong><span>Remaining</span></div>
            </div>

            <p class="yk-water__note">Quick adds are UI presets only—not medical recommendations.</p>
            <div class="yk-water__quick">
                @foreach ($dash['quick_adds'] as $ml)
                    <form method="post" action="{{ route('tools.water-intake.store') }}">
                        @csrf
                        <input type="hidden" name="amount_ml" value="{{ $ml }}">
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <button type="submit">+{{ $ml }} ml</button>
                    </form>
                @endforeach
            </div>

            <form method="post" action="{{ route('tools.water-intake.store') }}" class="yk-water__field">
                @csrf
                <label for="custom-ml">Custom amount (ml)</label>
                <input id="custom-ml" type="number" name="amount_ml" min="1" max="5000" step="1" inputmode="numeric" required>
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <label for="custom-time" style="margin-top:0.55rem;">Optional time (Asia/Kolkata)</label>
                <input id="custom-time" type="datetime-local" name="consumed_at">
                <div class="yk-water__actions">
                    <button class="yk-water__btn" type="submit">Add water</button>
                </div>
            </form>

            <div class="yk-water__actions">
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.today') }}">Today’s intake</a>
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.week') }}">Weekly hydration</a>
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.goal') }}">Edit daily goal</a>
            </div>
        </section>
    </div>
</main>
@endsection
