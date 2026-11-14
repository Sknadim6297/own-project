@extends('layouts.layout')

@section('title', $tool['name'].' | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $partialMap = [
        'monthly-money-planner' => ['partial' => 'money-fields', 'steps' => ['Income', 'Expenses', 'Investments']],
        'net-worth-calculator' => ['partial' => 'net-worth-fields', 'steps' => ['Assets', 'Liabilities']],
        'emergency-fund-calculator' => ['partial' => 'emergency-fields', 'steps' => ['Expenses', 'Savings']],
        'financial-goal-calculator' => ['partial' => 'goal-fields', 'steps' => ['Goal type', 'Cost & timeline', 'Assumptions']],
        'debt-emi-calculator' => ['partial' => 'debt-fields', 'steps' => ['Loan details', 'Extra payment']],
        'retirement-calculator' => ['partial' => 'retirement-fields', 'steps' => ['Profile', 'Assumptions']],
        'inflation-calculator' => ['partial' => 'inflation-fields', 'steps' => []],
        'savings-investment-calculator' => ['partial' => 'savings-fields', 'steps' => []],
    ];
    $config = $partialMap[$toolKey] ?? null;
    $partial = $config['partial'] ?? null;
    $steps = $config['steps'] ?? [];
    $wizard = $steps !== [];
@endphp
<main class="yk-pfm yk-tools-ds" data-yk-tools-ds data-yk-pfm>
    <div class="yk-pfm__wrap yk-pfm__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <p class="yk-pfm__crumb"><a href="{{ route('tools.pfm') }}">Money &amp; Finance</a> / {{ $tool['name'] }}</p>
        <section class="yk-pfm__card" aria-labelledby="pfm-form-heading">
            <p class="yk-pfm__badge">Money &amp; Finance tool</p>
            <h1 id="pfm-form-heading">{{ $tool['name'] }}</h1>
            <p class="yk-pfm__lead">{{ $tool['description'] }} Educational estimates only—not financial advice.</p>

            @if (! $open)
                <p class="yk-pfm__error" role="alert">This calculator is unavailable right now.</p>
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
                        <nav class="yk-pfm__wizard-nav" aria-label="Calculator steps">
                            @foreach ($steps as $index => $label)
                                <span class="yk-pfm__wizard-step {{ $index === 0 ? 'is-active' : '' }}" data-pfm-step-label="{{ $index }}">{{ $index + 1 }}. {{ $label }}</span>
                            @endforeach
                        </nav>
                    @endif

                    @if ($partial)
                        @include('frontend.sections.pfm.partials.'.$partial, ['old' => $old, 'goalTypes' => $goalTypes ?? []])
                    @endif

                    <div class="yk-pfm__actions">
                        @if ($wizard)
                            <button type="button" class="yk-pfm__btn yk-pfm__btn--ghost" data-pfm-prev hidden>Back</button>
                            <button type="button" class="yk-pfm__btn" data-pfm-next>Continue</button>
                        @endif
                        <button type="submit" class="yk-pfm__btn" data-pfm-submit @if ($wizard) hidden @endif>Calculate</button>
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
