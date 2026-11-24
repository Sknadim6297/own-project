@extends('layouts.layout')

@section('title', $tool['name'].' | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-ps-body')

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
    $psHeaderIcons = [
        'parenting-stress-check' => 'users',
        'child-routine-planner' => 'users',
        'parent-child-conversation-guide' => 'users',
        'daily-mindfulness-calm' => 'leaf',
        'purpose-values-finder' => 'sparkles',
    ];
    $psHeaderIcon = $psHeaderIcons[$toolKey] ?? 'sparkles';
@endphp
<main class="yk-ps yk-tools-ds yk-tool-shell yk-ps--compact-header" data-yk-tools-ds data-yk-ps data-yk-tool-shell="1">
    <div class="yk-ps__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <nav class="yk-ps__crumb" aria-label="Breadcrumb"><a href="{{ route('tools.ps') }}">{{ $tool['category_label'] }}</a> <span aria-hidden="true">/</span> <span aria-current="page">{{ $tool['name'] }}</span></nav>

        <header class="yk-ps__head yk-ps__head--compact" aria-labelledby="ps-form-heading" data-yk-tool-compact-header>
            <span class="yk-ps__tool-icon yk-ps__tool-icon--{{ $tool['category'] }} yk-ps__tool-icon--lg" aria-hidden="true">
                <x-yk-icon :name="$psHeaderIcon" class="yk-icon--lg" />
            </span>
            <div class="yk-ps__head-copy">
                <p class="yk-ps__badge">{{ $tool['category_label'] }} tool</p>
                <h1 id="ps-form-heading">{{ $tool['name'] }}</h1>
                <p class="yk-ps__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for($tool['slug'] ?? $toolKey) ?? $tool['description'] }}</p>
            </div>
        </header>

        <section class="yk-ps__panel" aria-label="{{ $tool['name'] }} form">
            @if (! $open)
                <p class="yk-ps__error" role="alert">This tool is unavailable right now.</p>
            @else
                @if ($errors->any())
                    <p class="yk-ps__error" role="alert">@include('frontend.sections.ps.partials.icon', ['name' => 'alert', 'size' => 18]) <span>{{ $errors->first() }}</span></p>
                @endif

                @if ($hasResult)
                    <p class="yk-ps__status">
                        @include('frontend.sections.ps.partials.icon', ['name' => 'sparkle', 'size' => 18])
                        <span>You have a recent result. <a href="{{ route($tool['route'].'.results') }}">View results</a></span>
                    </p>
                @endif

                <form
                    method="post"
                    action="{{ route($tool['route'].'.calculate') }}"
                    class="yk-ps__form"
                    data-ps-form
                    @if ($wizard) data-ps-wizard="1" @endif
                    novalidate
                >
                    @csrf

                    @if ($wizard)
                        <div class="yk-ps__progress" aria-hidden="true"><span data-ps-progress style="width: {{ round(100 / count($steps)) }}%"></span></div>
                        <ol class="yk-ps__stepper" aria-label="Progress">
                            @foreach ($steps as $index => $label)
                                <li class="yk-ps__stepper-item {{ $index === 0 ? 'is-active' : '' }}" data-ps-step-label="{{ $index }}">
                                    <span class="yk-ps__stepper-dot"><b>{{ $index + 1 }}</b>@include('frontend.sections.ps.partials.icon', ['name' => 'check', 'size' => 13])</span>
                                    <span class="yk-ps__stepper-text">{{ $label }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($partial)
                        @include('frontend.sections.ps.partials.'.$partial)
                    @endif

                    <div class="yk-ps__actions yk-ps__actions--form">
                        @if ($wizard)
                            <button type="button" class="yk-ps__btn yk-ps__btn--ghost" data-ps-prev hidden>@include('frontend.sections.ps.partials.icon', ['name' => 'arrow-left', 'size' => 16]) Back</button>
                            <button type="button" class="yk-ps__btn" data-ps-next hidden>Continue @include('frontend.sections.ps.partials.icon', ['name' => 'arrow', 'size' => 16])</button>
                        @endif
                        <button type="submit" class="yk-ps__btn" data-ps-submit>Generate result @include('frontend.sections.ps.partials.icon', ['name' => 'sparkle', 'size' => 16])</button>
                        @auth('user')
                            <a class="yk-ps__btn yk-ps__btn--ghost" href="{{ route($tool['route'].'.history') }}">@include('frontend.sections.ps.partials.icon', ['name' => 'history', 'size' => 16]) View history</a>
                        @endauth
                    </div>
                </form>
            @endif
        </section>

        <p class="yk-ps__note">@include('frontend.sections.ps.partials.icon', ['name' => 'info', 'size' => 16]) <span>{{ $disclaimer }}</span></p>
    </div>
</main>
@endsection
