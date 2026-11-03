@extends('layouts.layout')

@section('title', 'Your Journey | Weight Journey | YuktiOne')
@section('meta_description', 'Overview of your weight tracking journey.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Your journey</p>
            <h1>Weight overview</h1>
            <p class="yk-wj__lead">Neutral tracking of recorded weights. Changes are not labeled as success or failure.</p>

            @if ($status)
                <p class="yk-wj__ok">{{ $status }}</p>
            @endif

            <div class="yk-wj__stats">
                <div><strong>{{ $summary['starting_label'] ?? '—' }}</strong><span>Starting</span></div>
                <div><strong>{{ $summary['latest_label'] ?? '—' }}</strong><span>Latest</span></div>
                <div><strong>{{ $summary['net_label'] ?? '—' }}</strong><span>Net change</span></div>
            </div>

            @if ($summary['goal'])
                <p class="yk-wj__lead">
                    Active goal: <strong>{{ \App\Support\WeightJourney\WeightJourneyCatalog::DIRECTIONS[$summary['goal']->direction] ?? $summary['goal']->direction }}</strong>
                    @if ($summary['goal']->target_weight_kg)
                        · target {{ app(\App\Services\WeightJourneyService::class)->formatWeight((string) $summary['goal']->target_weight_kg, $summary['unit']) }}
                    @endif
                </p>
                @if (($summary['progress']['meaningful'] ?? false) && isset($summary['progress']['percent']))
                    <p class="yk-wj__note">Progress toward target (directional distance): {{ $summary['progress']['percent'] }}%. This is geometric progress only—not a health judgement.</p>
                @elseif (($summary['progress']['reason'] ?? null) === 'maintain' || ($summary['progress']['reason'] ?? null) === 'track')
                    <p class="yk-wj__note">Maintain / track-only goals do not use a percentage-complete indicator.</p>
                @endif
            @else
                <p class="yk-wj__lead">No active goal. You can track measurements anytime or set an optional goal.</p>
            @endif

            <h2 style="font-size:1.05rem;margin:1rem 0 0.5rem;">Recent measurements</h2>
            @if ($recent->isEmpty())
                <p class="yk-wj__note">No measurements yet.</p>
            @else
                <ul class="yk-wj__list">
                    @foreach ($recent as $row)
                        <li>
                            <span>
                                <strong>{{ app(\App\Services\WeightJourneyService::class)->formatWeight((string) $row->weight_kg, $summary['unit']) }}</strong>
                                <span class="yk-wj__note" style="display:block;margin:0;">{{ $row->recorded_at->timezone($timezone)->format('d M Y, g:i A') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($summary['history']->isNotEmpty())
                <h2 style="font-size:1.05rem;margin:1.2rem 0 0.5rem;">Goal history</h2>
                <ul class="yk-wj__list">
                    @foreach ($summary['history'] as $g)
                        <li>
                            <span>
                                <strong>{{ \App\Support\WeightJourney\WeightJourneyCatalog::DIRECTIONS[$g->direction] ?? $g->direction }}</strong>
                                · {{ $g->status }}
                                <span class="yk-wj__note" style="display:block;margin:0;">
                                    From {{ optional($g->effective_from)->timezone($timezone)?->format('d M Y') }}
                                    @if ($g->effective_to) to {{ $g->effective_to->timezone($timezone)->format('d M Y') }} @endif
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('tools.weight-journey.log') }}">Log weight</a>
                <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey.goal') }}">Edit goal</a>
                <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey.progress') }}">Weight progress</a>
            </div>
        </section>
    </div>
</main>
@endsection
