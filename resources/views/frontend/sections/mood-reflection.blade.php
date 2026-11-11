@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Sometimes writing down what is on your mind can help.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-tools-ds" data-yk-tools-ds data-yk-mood>
    <div class="yk-mood__wrap">
        <a class="yk-mood__back" href="{{ route('tools.psychology.mood.emotions') }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-mood__closed" aria-labelledby="reflection-closed-heading">
                <p class="yk-mood__badge">Free tool</p>
                <h1 id="reflection-closed-heading">Would you like to share more?</h1>
                <p>This check-in is unavailable right now.</p>
            </section>
        @else
            @include('frontend.sections.partials.mood-progress', ['current' => 'reflection'])

            <form class="yk-mood__form yk-mood__panel yk-mood__panel--write" method="post" action="{{ route('tools.psychology.mood.reflection.store') }}" data-reflection-form data-reflection-limit="{{ $limit }}">
                @csrf
                <p class="yk-mood__badge">Free tool</p>
                <h1>Would you like to share more?</h1>
                <p class="yk-mood__lead">Sometimes writing down what's on your mind can help.</p>

                @if ($held)
                    <p class="yk-reflect__progress" role="status">This check-in is still in progress.</p>
                @endif

                @if ($errors->any())
                    <p class="yk-mood__error" role="alert">{{ $errors->first() }}</p>
                @endif

                <label class="yk-reflect__label" for="mood-reflection">What made you feel this way today?</label>
                <textarea id="mood-reflection" class="yk-reflect__input" name="reflection" rows="5" maxlength="{{ $limit }}" placeholder="Write anything you would like to share..." data-reflection-input aria-describedby="reflection-meta reflection-count">{{ $reflection }}</textarea>

                <div class="yk-reflect__meta" id="reflection-meta">
                    <span>Optional · Up to 1,000 characters</span>
                    <span id="reflection-count" data-reflection-count>{{ mb_strlen($reflection) }} / 1,000</span>
                </div>

                <h2 class="yk-reflect__prompts-title">Need a little inspiration?</h2>
                <div class="yk-reflect__prompts">
                    @foreach ($prompts as $prompt)
                        <button class="yk-reflect__prompt" type="button" data-reflection-prompt="{{ $prompt }}">{{ $prompt }}</button>
                    @endforeach
                </div>
                <div class="yk-reflect__confirm" data-prompt-confirm hidden>
                    <p>Add this prompt below what you have written?</p>
                    <div class="yk-reflect__confirm-actions">
                        <button class="yk-reflect__add" type="button" data-prompt-add>Add prompt</button>
                        <button class="yk-reflect__keep" type="button" data-prompt-cancel>Keep my writing</button>
                    </div>
                </div>

                <div class="yk-mood__actions">
                    <button class="yk-mood__continue" type="submit" name="action" value="save">Save Check-in</button>
                    <button class="yk-reflect__skip" type="submit" name="action" value="skip">Skip</button>
                </div>
            </form>
        @endif
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-mood.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-mood.js')) }}" defer></script>
@endif
@endsection
