@extends('layouts.layout')

@section('title', $tool['name'].' | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $partialMap = [
        'parenting-stress-check' => ['partial' => 'stress-fields', 'steps' => ['Intro', 'Questions 1–4', 'Questions 5–8']],
        'child-routine-planner' => ['partial' => 'routine-fields', 'steps' => ['Profile', 'Fixed schedule', 'Preferences']],
        'parent-child-conversation-guide' => ['partial' => 'conversation-fields', 'steps' => ['Situation', 'Age & goal', 'Context']],
        'daily-mindfulness-calm' => ['partial' => 'mindfulness-fields', 'steps' => ['State', 'Duration', 'Practice']],
        'purpose-values-finder' => ['partial' => 'values-fields', 'steps' => ['Choices 1–8', 'Choices 9–16', 'Ranking']],
    ];
    $config = $partialMap[$toolKey] ?? null;
    $partial = $config['partial'] ?? null;
    $steps = $config['steps'] ?? [];
    $wizard = $steps !== [];
@endphp
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-ps data-yk-pfm>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb"><a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> / {{ $tool['name'] }}</p>
        <section class="yk-pfm__card" aria-labelledby="ps-form-heading">
            <p class="yk-pfm__badge">{{ $tool['category_label'] }} tool</p>
            <h1 id="ps-form-heading">{{ $tool['name'] }}</h1>
            <p class="yk-pfm__lead">{{ $tool['description'] }}</p>
            <p class="yk-pfm__note">{{ $disclaimer }}</p>

            @if (! $open)
                <p class="yk-pfm__error" role="alert">This tool is unavailable right now.</p>
            @else
                @if ($errors->any())
                    <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
                @endif

                @if ($hasResult)
                    <p class="yk-pfm__status">
                        You have a recent result.
                        <a href="{{ route($tool['route'].'.results') }}">View results</a>
                    </p>
                @endif

                <form
                    method="post"
                    action="{{ route($tool['route'].'.calculate') }}"
                    class="yk-pfm__form"
                    data-pfm-form
                    @if ($wizard) data-pfm-wizard="1" @endif
                    novalidate
                >
                    @csrf

                    @if ($wizard)
                        <nav class="yk-pfm__wizard-nav" aria-label="Tool steps">
                            @foreach ($steps as $index => $label)
                                <span class="yk-pfm__wizard-step {{ $index === 0 ? 'is-active' : '' }}" data-pfm-step-label="{{ $index }}">{{ $index + 1 }}. {{ $label }}</span>
                            @endforeach
                        </nav>
                    @endif

                    @if ($partial)
                        @include('frontend.sections.ps.partials.'.$partial)
                    @endif

                    <div class="yk-pfm__actions">
                        @if ($wizard)
                            <button type="button" class="yk-pfm__btn yk-pfm__btn--ghost" data-pfm-prev hidden>Back</button>
                            <button type="button" class="yk-pfm__btn" data-pfm-next>Continue</button>
                        @endif
                        <button type="submit" class="yk-pfm__btn" data-pfm-submit @if ($wizard) hidden @endif>Generate result</button>
                        @auth('user')
                            <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">View history</a>
                        @endauth
                    </div>
                </form>
            @endif
        </section>
    </div>
</main>
@endsection

@if ($toolKey === 'daily-mindfulness-calm')
@push('scripts')
<script src="{{ asset('frontend/assets/js/yuktione-ps.js') }}?v={{ @filemtime(public_path('frontend/assets/js/yuktione-ps.js')) ?: time() }}" defer></script>
@endpush
@endif
