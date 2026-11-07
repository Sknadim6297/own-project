@extends('layouts.layout')

@section('title', 'Saved Career Interest result | YuktiOne')
@section('meta_description', 'Your saved Career Interest Test result.')
@section('body_class', 'yk-cit-body')

@section('content')
@php
    $percentages = $attempt->category_percentages ?? [];
    $ranked = collect($categories)->map(function ($meta, $key) use ($percentages) {
        return [
            'key' => $key,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'percent' => (int) ($percentages[$key] ?? 0),
        ];
    })->sortByDesc('percent')->values();
    $recommendations = $attempt->recommendations['items'] ?? [];
    $when = optional($attempt->completed_at)->timezone($timezone)?->format('d M Y, g:i A');
@endphp
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <section class="yk-cit__card" aria-labelledby="cit-result-heading">
            <p class="yk-cit__badge">Saved result</p>
            <h1 id="cit-result-heading">Your Interest Profile</h1>
            <p class="yk-cit__lead">Saved {{ $when }} (Asia/Kolkata).</p>

            <div class="yk-cit__bars">
                @foreach ($ranked as $row)
                    <div class="yk-cit__bar-row">
                        <strong><span>{{ $row['label'] }}</span><span>{{ $row['percent'] }}%</span></strong>
                        <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                        <p class="yk-cit__bar-desc">{{ $row['description'] }}</p>
                    </div>
                @endforeach
            </div>

            @if (filled($attempt->interpretation))
                <p class="yk-cit__lead">{{ $attempt->interpretation }}</p>
            @endif

            <p class="yk-cit__note">Displayed scores use implementation convention {{ $attempt->scoring_version }} (sum ÷ 15 × 100)—not a validated psychometric claim.</p>

            @if ($recommendations !== [])
                <h2 class="yk-cit__section-title">Career suggestions</h2>
                <ul class="yk-cit__suggest">
                    @foreach ($recommendations as $item)
                        <li>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['summary'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="yk-cit__actions" aria-label="Result actions">
                <a class="yk-cit__btn" href="{{ route('tools.career-interest.history') }}">View History</a>
                <form method="post" action="{{ route('tools.career-interest.retake') }}">
                    @csrf
                    <button class="yk-cit__btn-secondary" type="submit">Retake Assessment</button>
                </form>
                <form method="post" action="{{ route('tools.career-interest.destroy', $attempt) }}" onsubmit="return confirm('Delete this saved assessment?');">
                    @csrf
                    @method('DELETE')
                    <button class="yk-cit__btn-secondary" type="submit">Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
