@extends('layouts.layout')

@section('title', 'Saved Career Interest result | YuktiOne')
@section('meta_description', 'Your saved Career Interest Test result.')
@section('body_class', 'yk-cit-body')

@section('content')
@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
    $percentages = $attempt->category_percentages ?? [];
    $ranked = collect($categories)->map(function ($meta, $key) use ($percentages) {
        return [
            'key' => $key,
            'icon' => $meta['icon'] ?? 'compass',
            'label' => $meta['label'],
            'description' => $meta['description'],
            'percent' => (int) ($percentages[$key] ?? 0),
        ];
    })->sortByDesc('percent')->values();
    $topKeys = collect($attempt->top_categories ?? [])->all();
    $primary = $ranked->filter(fn ($row) => in_array($row['key'], $topKeys, true))->values();
    $others = $ranked->reject(fn ($row) => in_array($row['key'], $topKeys, true))->values();
    $recommendations = $attempt->recommendations['items'] ?? [];
    $when = optional($attempt->completed_at)->timezone($timezone)?->format('d M Y, g:i A');
@endphp
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools', ['label' => 'Back to Tools'])

        <section class="yk-cit__card" aria-labelledby="cit-result-heading">
            <p class="yk-cit__badge">{!! $icon('lock', 14) !!} Saved result</p>
            <h1 id="cit-result-heading">Your Interest Profile</h1>
            <p class="yk-cit__lead">Saved {{ $when }} (Asia/Kolkata).</p>

            @if ($primary->isNotEmpty())
                <h2 class="yk-cit__section-title" style="margin-top:0.4rem">{!! $icon('sparkle', 18) !!} Primary interests</h2>
                <ol class="yk-cit__primary">
                    @foreach ($primary as $i => $row)
                        <li class="yk-cit__top yk-cit__top--{{ min($i + 1, 3) }}">
                            <div class="yk-cit__top-head">
                                <div class="yk-cit__top-name">
                                    <span class="yk-cit__top-icon">{!! $icon($row['icon'], 20) !!}</span>
                                    <div>
                                        <span class="yk-cit__top-rank">Top {{ $i + 1 }}</span>
                                        <strong class="yk-cit__top-label">{{ $row['label'] }}</strong>
                                    </div>
                                </div>
                                <span class="yk-cit__top-pct">{{ $row['percent'] }}%</span>
                            </div>
                            <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                            <p class="yk-cit__top-desc">{{ $row['description'] }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif

            <h2 class="yk-cit__section-title">{!! $icon('grid', 18) !!} {{ $primary->isNotEmpty() ? 'Other interests' : 'All interests' }}</h2>
            <div class="yk-cit__bars">
                @foreach (($primary->isNotEmpty() ? $others : $ranked) as $row)
                    <div class="yk-cit__bar-row">
                        <strong><span>{!! $icon($row['icon'], 16) !!} {{ $row['label'] }}</span><span>{{ $row['percent'] }}%</span></strong>
                        <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                        <p class="yk-cit__bar-desc">{{ $row['description'] }}</p>
                    </div>
                @endforeach
            </div>

            @if (filled($attempt->interpretation))
                <p class="yk-cit__lead" style="margin-top:1.2rem">{{ $attempt->interpretation }}</p>
            @endif

            <p class="yk-cit__note">{!! $icon('info', 16) !!}<span>Displayed scores use implementation convention {{ $attempt->scoring_version }} (sum ÷ 15 × 100)—not a validated psychometric claim.</span></p>

            @if ($recommendations !== [])
                <h2 class="yk-cit__section-title">{!! $icon('briefcase', 18) !!} Career suggestions</h2>
                <ul class="yk-cit__suggest">
                    @foreach ($recommendations as $item)
                        <li>
                            <span class="yk-cit__suggest-icon">{!! $icon('briefcase', 20) !!}</span>
                            <div>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['summary'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="yk-cit__actions" aria-label="Result actions">
                <a class="yk-cit__btn" href="{{ route('tools.career-interest.history') }}">{!! $icon('history', 18) !!} View History</a>
                <form method="post" action="{{ route('tools.career-interest.retake') }}">
                    @csrf
                    <button class="yk-cit__btn-secondary" type="submit">{!! $icon('refresh', 18) !!} Retake Assessment</button>
                </form>
                <form method="post" action="{{ route('tools.career-interest.destroy', $attempt) }}" onsubmit="return confirm('Delete this saved assessment?');">
                    @csrf
                    @method('DELETE')
                    <button class="yk-cit__btn-secondary yk-cit__btn-danger" type="submit">{!! $icon('trash', 18) !!} Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
