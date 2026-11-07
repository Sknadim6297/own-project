@extends('layouts.layout')

@section('title', 'Your Skills Profile | YuktiOne')
@section('meta_description', 'Your self-rated strengths at a glance.')
@section('body_class', 'yk-ssa-body')

@section('content')
@php $careerExperts = app(\App\Services\ExpertsDiscovery::class)->career(); @endphp
<main class="yk-ssa">
    <div class="yk-ssa__wrap yk-ssa__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-ssa__card" aria-labelledby="ssa-profile-heading">
            <p class="yk-ssa__badge">Results</p>
            <h1 id="ssa-profile-heading">Your Skills Profile</h1>
            <p class="yk-ssa__lead">Your self-rated strengths at a glance</p>

            @if ($errors->any())
                <p class="yk-ssa__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="yk-ssa__profile">
                @include('partials.skills-strengths-radar', ['radar' => $radar])

                <div class="yk-ssa__panel">
                    <h2>Your Summary</h2>
                    <p class="yk-ssa__lead">{{ $scored['summary'] }}</p>

                    <h2>Key strengths</h2>
                    <ul class="yk-ssa__list">
                        @foreach ($scored['strengths'] as $row)
                            <li>
                                <div class="yk-ssa__score-row">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ $row['percent'] }}%</span>
                                </div>
                                <div class="yk-ssa__score-bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>

                    <h2 class="yk-ssa__panel-gap">Areas to build</h2>
                    <ul class="yk-ssa__list is-develop">
                        @foreach ($scored['develop'] as $row)
                            <li>
                                <div class="yk-ssa__score-row">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ $row['percent'] }}%</span>
                                </div>
                                <div class="yk-ssa__score-bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>

                    <p class="yk-ssa__note">{{ $scored['meaning'] }}</p>
                    <p class="yk-ssa__note">Scores use implementation convention {{ $scored['scoring_version'] }}: round(((sum of 3 answers − 3) / 12) × 100). Not a scientifically validated ability score.</p>
                </div>
            </div>

            <div class="yk-ssa__actions">
                <form method="post" action="{{ route('tools.skills-strengths.retake') }}">
                    @csrf
                    <button class="yk-ssa__btn-secondary" type="submit">Retake Assessment</button>
                </form>
                <a class="yk-ssa__btn-secondary" href="{{ $careerExperts['url'] }}">{{ $careerExperts['label'] }}</a>
                <form method="post" action="{{ route('tools.skills-strengths.save') }}">
                    @csrf
                    <button class="yk-ssa__btn" type="submit">
                        {{ $authenticated ? 'Save to profile' : 'Log in to save' }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
