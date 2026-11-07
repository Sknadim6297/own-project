@extends('layouts.layout')

@section('title', 'Saved Skills Profile | YuktiOne')
@section('meta_description', 'Your saved Skills & Strengths Assessment result.')
@section('body_class', 'yk-ssa-body')

@section('content')
@php
    $when = optional($attempt->completed_at)->timezone($timezone)?->format('d M Y, g:i A');
    $strengths = $attempt->strengths ?? [];
    $develop = $attempt->develop ?? [];
    $careerExperts = app(\App\Services\ExpertsDiscovery::class)->career();
@endphp
<main class="yk-ssa">
    <div class="yk-ssa__wrap yk-ssa__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-ssa__card" aria-labelledby="ssa-result-heading">
            <p class="yk-ssa__badge">Saved result</p>
            <h1 id="ssa-result-heading">Your Skills Profile</h1>
            <p class="yk-ssa__lead">Saved {{ $when }} (Asia/Kolkata).</p>

            <div class="yk-ssa__profile">
                @include('partials.skills-strengths-radar', ['radar' => $radar])
                <div class="yk-ssa__panel">
                    <h2>Your Summary</h2>
                    <p class="yk-ssa__lead">{{ $attempt->summary }}</p>

                    <h2>Key strengths</h2>
                    <ul class="yk-ssa__list">
                        @foreach ($strengths as $row)
                            <li>
                                <div class="yk-ssa__score-row">
                                    <span>{{ $row['label'] ?? '' }}</span>
                                    <span>{{ $row['percent'] ?? 0 }}%</span>
                                </div>
                                <div class="yk-ssa__score-bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) ($row['percent'] ?? 0))) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>

                    <h2 class="yk-ssa__panel-gap">Areas to build</h2>
                    <ul class="yk-ssa__list is-develop">
                        @foreach ($develop as $row)
                            <li>
                                <div class="yk-ssa__score-row">
                                    <span>{{ $row['label'] ?? '' }}</span>
                                    <span>{{ $row['percent'] ?? 0 }}%</span>
                                </div>
                                <div class="yk-ssa__score-bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) ($row['percent'] ?? 0))) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>

                    <p class="yk-ssa__note">{{ $attempt->meaning }}</p>
                    <p class="yk-ssa__note">Scoring version {{ $attempt->scoring_version }} — self-ratings only.</p>
                </div>
            </div>

            <div class="yk-ssa__actions">
                <a class="yk-ssa__btn-secondary" href="{{ route('tools.skills-strengths.history') }}">Back to history</a>
                <a class="yk-ssa__btn-secondary" href="{{ $careerExperts['url'] }}">{{ $careerExperts['label'] }}</a>
                <form method="post" action="{{ route('tools.skills-strengths.retake') }}">
                    @csrf
                    <button class="yk-ssa__btn" type="submit">Retake</button>
                </form>
                <form method="post" action="{{ route('tools.skills-strengths.destroy', $attempt) }}" onsubmit="return confirm('Delete this saved assessment?');">
                    @csrf
                    @method('DELETE')
                    <button class="yk-ssa__btn-secondary" type="submit">Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
