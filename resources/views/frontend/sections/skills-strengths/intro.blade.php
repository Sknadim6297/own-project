@extends('layouts.layout')

@section('title', 'Eight skill areas | Skills & Strengths | YuktiOne')
@section('meta_description', 'Your answers help create a snapshot of your current skill profile.')
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa yk-tools-ds" data-yk-tools-ds data-yk-ssa>
    <div class="yk-ssa__wrap">
        <section class="yk-ssa__card" aria-labelledby="ssa-intro-heading">
            <p class="yk-ssa__badge">Before you begin</p>
            <h1 id="ssa-intro-heading">Eight areas we’ll explore</h1>
            <p class="yk-ssa__lead">Answer honestly — your ratings create a snapshot of your current skill profile.</p>

            <ul class="yk-ssa__grid8">
                @foreach ($categories as $category)
                    <li>
                        <span>{{ $category['number'] }}</span>
                        {{ $category['label'] }}
                    </li>
                @endforeach
            </ul>

            <div class="yk-ssa__actions">
                <a class="yk-ssa__btn-secondary" href="{{ route('tools.skills-strengths') }}">Back</a>
                <form method="post" action="{{ route('tools.skills-strengths.intro.store') }}">
                    @csrf
                    <button class="yk-ssa__btn" type="submit">Start Assessment</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
