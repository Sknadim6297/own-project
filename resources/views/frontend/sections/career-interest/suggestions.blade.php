@extends('layouts.layout')

@section('title', 'Career Suggestions | YuktiOne')
@section('meta_description', 'Explore career areas that match your interests.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <section class="yk-cit__card" aria-labelledby="cit-suggest-heading">
            <p class="yk-cit__badge">Suggestions</p>
            <h1 id="cit-suggest-heading">Explore career areas that match your interests.</h1>
            <p class="yk-cit__lead">Based on your interest profile, here are some areas you may enjoy exploring.</p>

            @if ($errors->any())
                <p class="yk-cit__error">{{ $errors->first() }}</p>
            @endif

            @if (($recommendations['items'] ?? []) === [])
                <p class="yk-cit__lead">No strong career matches were found from this profile. You can retake the assessment or review your interest profile.</p>
            @else
                <ul class="yk-cit__suggest">
                    @foreach ($recommendations['items'] as $item)
                        <li>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['summary'] }}</p>
                            <p style="margin-top:0.45rem;">{{ $item['why'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="yk-cit__lead" style="font-size:0.88rem;">{{ $recommendations['disclaimer'] ?? '' }}</p>

            <div class="yk-cit__actions">
                <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest.profile') }}">Back to profile</a>
                <form method="post" action="{{ route('tools.career-interest.save') }}">
                    @csrf
                    <button class="yk-cit__btn" type="submit">
                        {{ $authenticated ? 'Save to profile' : 'Log in to save' }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
