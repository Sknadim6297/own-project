@extends('layouts.layout')

@section('title', 'Career Suggestions | YuktiOne')
@section('meta_description', 'Explore career areas that match your interests.')
@section('body_class', 'yk-cit-body')

@section('content')
@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')

        <section class="yk-cit__card" aria-labelledby="cit-suggest-heading">
            <p class="yk-cit__badge">{!! $icon('briefcase', 14) !!} Suggestions</p>
            <h1 id="cit-suggest-heading">Explore career areas that match your interests</h1>
            <p class="yk-cit__lead">Based on your interest profile, here are some areas you may enjoy exploring.</p>

            @if ($errors->any())
                <p class="yk-cit__error" role="alert">{{ $errors->first() }}</p>
            @endif

            @if (($recommendations['items'] ?? []) === [])
                <div class="yk-cit__empty">
                    <span class="yk-cit__empty-icon">{!! $icon('search', 22) !!}</span>
                    <p class="yk-cit__lead">No strong career matches were found from this profile.</p>
                    <p class="yk-cit__note">You can retake the assessment or review your interest profile.</p>
                </div>
            @else
                <ul class="yk-cit__suggest">
                    @foreach ($recommendations['items'] as $item)
                        <li>
                            <span class="yk-cit__suggest-icon">{!! $icon('briefcase', 20) !!}</span>
                            <div>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['summary'] }}</p>
                                <p class="yk-cit__suggest-why">{{ $item['why'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (filled($recommendations['disclaimer'] ?? ''))
                <p class="yk-cit__note">{!! $icon('info', 16) !!}<span>{{ $recommendations['disclaimer'] }}</span></p>
            @endif

            <div class="yk-cit__actions yk-cit__actions--between">
                <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest.profile') }}">{!! $icon('arrow-left', 18) !!} Back to profile</a>
                <form method="post" action="{{ route('tools.career-interest.save') }}">
                    @csrf
                    <button class="yk-cit__btn" type="submit">
                        {!! $icon($authenticated ? 'bookmark' : 'lock', 18) !!} {{ $authenticated ? 'Save to profile' : 'Log in to save' }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
