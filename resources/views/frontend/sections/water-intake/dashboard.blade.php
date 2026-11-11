@extends('layouts.layout')

@section('title', 'Water Intake Dashboard | YuktiOne')
@section('meta_description', 'Today’s water intake against your personal goal.')
@section('body_class', 'yk-water-body')

@section('content')
@php
    $ringPct = min(100, max(0, (int) $dash['percent']));
    $radius = 54;
    $circ = 2 * M_PI * $radius;
    $offset = $circ * (1 - ($ringPct / 100));
    $overGoal = $dash['percent'] > 100;
    $overMl = max(0, (int) $dash['total_ml'] - (int) $dash['goal_ml']);
@endphp
<main class="yk-water yk-tools-ds" data-yk-tools-ds data-yk-water>
    <div class="yk-water__wrap yk-water__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-water__card" aria-labelledby="water-dash-heading">
            <p class="yk-water__badge">{{ $isToday ? 'Today' : $dash['date'] }} · Asia/Kolkata</p>
            <h1 id="water-dash-heading">Water Intake Tracker</h1>
            <p class="yk-water__lead">Personal daily tracking against your goal—not a medical prescription.</p>

            @if ($status)
                <p class="yk-water__ok" role="status">{{ $status }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-water__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="yk-water__dash-grid">
                <div
                    class="yk-water__progress {{ $overGoal ? 'is-over' : '' }}"
                    role="img"
                    aria-label="{{ $dash['total_label'] }} of {{ $dash['goal_label'] }}, {{ $dash['percent'] }} percent of daily goal{{ $overGoal ? ', over goal' : '' }}"
                >
                    <svg viewBox="0 0 140 140" aria-hidden="true">
                        <circle class="yk-water__ring-track" cx="70" cy="70" r="{{ $radius }}" fill="none" stroke-width="12"></circle>
                        <circle
                            class="yk-water__ring-fill"
                            cx="70" cy="70" r="{{ $radius }}"
                            fill="none"
                            stroke-width="12"
                            stroke-linecap="round"
                            stroke-dasharray="{{ number_format($circ, 4, '.', '') }}"
                            stroke-dashoffset="{{ number_format($offset, 4, '.', '') }}"
                            transform="rotate(-90 70 70)"
                            style="--yk-water-circ: {{ number_format($circ, 4, '.', '') }}; --yk-water-offset: {{ number_format($offset, 4, '.', '') }};"
                        ></circle>
                    </svg>
                    <div class="yk-water__progress-center">
                        <strong>{{ $dash['percent'] }}%</strong>
                        <span>{{ $dash['total_label'] }}</span>
                        <span class="yk-water__progress-goal">of {{ $dash['goal_label'] }}</span>
                    </div>
                </div>

                <div class="yk-water__stats" aria-label="Daily summary">
                    <div>
                        <strong>{{ $dash['total_label'] }}</strong>
                        <span>Consumed</span>
                    </div>
                    <div>
                        <strong>{{ $dash['goal_label'] }}</strong>
                        <span>Daily goal</span>
                    </div>
                    <div>
                        <strong>{{ $overGoal ? '+'.number_format($overMl).' ml' : $dash['remaining_label'] }}</strong>
                        <span>{{ $overGoal ? 'Over goal' : 'Remaining' }}</span>
                    </div>
                </div>
            </div>

            <h2 class="yk-water__section-title">Quick add</h2>
            <div class="yk-water__quick" data-water-quick>
                @foreach ($dash['quick_adds'] as $preset)
                    <form method="post" action="{{ route('tools.water-intake.store') }}" data-water-submit-once>
                        @csrf
                        <input type="hidden" name="amount_ml" value="{{ $preset['ml'] }}">
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <button type="submit" class="yk-water__quick-card">
                            <span class="yk-water__quick-emoji" aria-hidden="true">{{ $preset['emoji'] }}</span>
                            <span class="yk-water__quick-label">{{ $preset['label'] }}</span>
                            <span class="yk-water__quick-amt">+{{ number_format($preset['ml']) }} ml</span>
                        </button>
                    </form>
                @endforeach
            </div>

            <details class="yk-water__manual">
                <summary>Custom amount</summary>
                <form method="post" action="{{ route('tools.water-intake.store') }}" class="yk-water__manual-form" data-water-submit-once>
                    @csrf
                    <div class="yk-water__manual-row">
                        <div class="yk-water__field">
                            <label for="custom-ml">Amount (ml)</label>
                            <input id="custom-ml" type="number" name="amount_ml" min="1" max="5000" step="1" inputmode="numeric" required placeholder="e.g. 200">
                        </div>
                        <div class="yk-water__field">
                            <label for="custom-time">Time (optional)</label>
                            <input id="custom-time" type="datetime-local" name="consumed_at">
                        </div>
                    </div>
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <button class="yk-water__btn" type="submit">Add water</button>
                </form>
            </details>

            <div class="yk-water__logs">
                <div class="yk-water__logs-head">
                    <h2 class="yk-water__section-title">Today’s logs</h2>
                    <a class="yk-water__text-link" href="{{ route('tools.water-intake.today', ['date' => $dash['date']]) }}">Manage</a>
                </div>
                @if ($entries->isEmpty())
                    <div class="yk-water__empty" role="status">No entries yet today. Use quick add or a custom amount.</div>
                @else
                    <ul class="yk-water__list">
                        @foreach ($entries->sortByDesc(fn ($e) => $e->consumed_at)->take(6) as $entry)
                            @php $local = $entry->consumed_at->timezone($timezone); @endphp
                            <li>
                                <div>
                                    <strong>{{ number_format($entry->amount_ml) }} ml</strong>
                                    <div class="yk-water__meta">{{ $local->format('g:i A') }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @if ($entries->count() > 6)
                        <p class="yk-water__note" style="margin-top:0.65rem;">Showing latest 6 of {{ $entries->count() }}. <a href="{{ route('tools.water-intake.today', ['date' => $dash['date']]) }}">View all</a></p>
                    @endif
                @endif
            </div>

            <div class="yk-water__actions">
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.week') }}">Weekly history</a>
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.goal') }}">Edit goal</a>
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.today') }}">Day detail</a>
            </div>
        </section>
    </div>
</main>
@endsection
