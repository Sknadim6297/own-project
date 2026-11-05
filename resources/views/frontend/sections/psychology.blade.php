@extends('layouts.layout')

@section('title', 'Psychology | YuktiOne')
@section('meta_description', 'Small steps towards understanding yourself and feeling better.')
@section('body_class', 'yk-psych-body')

@section('content')
<main class="yk-psych">
    <div class="yk-psych__wrap">
        <nav class="yk-psych__crumb" aria-label="Breadcrumb">
            <a href="{{ route('tools.index') }}">Tools</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Psychology</span>
        </nav>

        <header class="yk-psych__intro">
            <h1>Psychology</h1>
            <p class="yk-psych__subtitle">Take a moment for yourself</p>
            <p class="yk-psych__lead">Small steps towards understanding yourself and feeling better.</p>
        </header>

        <section class="yk-psych__mood" aria-labelledby="mood-heading">
            <div class="yk-psych__mood-copy">
                <h2 id="mood-heading">How are you feeling today?</h2>
                <p>Take a quick moment to check in with yourself.</p>
                @unless ($mood['href'])
                    <p class="yk-psych__state">{{ $mood['action'] }}</p>
                @endunless
            </div>
            <div class="yk-psych__mood-art" aria-hidden="true">
                @include('frontend.sections.partials.psychology-icon', ['icon' => 'mood'])
            </div>
            @if ($mood['href'])
                <a class="yk-psych__button" href="{{ $mood['href'] }}">Start Mood Check-in</a>
            @else
                <button class="yk-psych__button" type="button" disabled>Start Mood Check-in</button>
            @endif
        </section>

        <section class="yk-psych__tools" aria-labelledby="tools-heading">
            <h2 id="tools-heading">Your self-care tools</h2>
            <ul>
                @foreach ($cards as $card)
                    <li class="yk-psych__card is-{{ $card['tone'] }}">
                        <div class="yk-psych__card-art" aria-hidden="true">
                            @include('frontend.sections.partials.psychology-icon', ['icon' => $card['icon']])
                        </div>
                        <h3>{{ $card['name'] }}</h3>
                        <p>{{ $card['description'] }}</p>
                        @if ($card['href'])
                            <a class="yk-psych__card-action" href="{{ $card['href'] }}">{{ $card['action'] }}</a>
                        @else
                            <span class="yk-psych__card-action is-quiet">{{ $card['action'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="yk-psych__private">Your check-ins, journal notes and gratitude entries stay private. They are not shared automatically, and they are not a diagnosis.</p>
        </section>

        @include('frontend.sections.partials.psychology-connect', [
            'heading' => 'Need someone to talk to?',
            'copy' => 'Connect with a psychologist for a supportive conversation.',
            'cta' => 'Explore Psychologists',
            'url' => $psychologistsUrl,
            'headingId' => 'support-heading',
        ])
    </div>
</main>
@endsection
