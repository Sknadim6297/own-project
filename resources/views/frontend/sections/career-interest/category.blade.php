@extends('layouts.layout')

@section('title', $category['label'].' | Career Interest Test | YuktiOne')
@section('meta_description', 'Tell us how much you enjoy these '.$category['label'].' activities.')
@section('body_class', 'yk-cit-body')

@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
    $scaleOptions = $scaleOptions ?? collect($scale)->map(fn ($label, $value) => [
        'value' => (int) $value,
        'label' => $label,
        'emoji' => '',
        'icon' => \App\Support\CareerInterest\CareerInterestCatalog::SCALE_ICON[$value] ?? 'meh',
    ])->all();
    $progress = $progress ?? (int) round(($step / max(1, $totalSteps)) * 100);
    $questionCount = $questionCount ?? 18;
    $perPage = max(1, count($questions));
    $qStart = (($step - 1) * $perPage) + 1;
    $qEnd = min($questionCount, $step * $perPage);
    $allCategories = \App\Support\CareerInterest\CareerInterestCatalog::categories();
    $categoryIcon = $category['icon'] ?? ($allCategories[$category['key']]['icon'] ?? 'compass');
@endphp

@section('content')
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit data-cit>
    <div class="yk-cit__wrap">
        <form class="yk-cit__card yk-cit__card--assess" method="post" action="{{ route('tools.career-interest.category.store', ['category' => $category['key']]) }}" data-cit-form novalidate>
            @csrf

            <header class="yk-cit__assess-head">
                <p class="yk-cit__tool">Career Interest Test</p>
                <div class="yk-cit__progress" aria-hidden="true">
                    <span>
                        Questions {{ $qStart }}–{{ $qEnd }} of {{ $questionCount }}
                        · Step {{ $step }} of {{ $totalSteps }}
                    </span>
                    <span>{{ $progress }}%</span>
                </div>
                <div
                    class="yk-cit__bar-track"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ $progress }}"
                    aria-label="Assessment progress: step {{ $step }} of {{ $totalSteps }}, questions {{ $qStart }} to {{ $qEnd }} of {{ $questionCount }}"
                >
                    <span style="width: {{ $progress }}%"></span>
                </div>

                <ol class="yk-cit__steps" aria-hidden="true">
                    @foreach ($allCategories as $key => $meta)
                        <li class="yk-cit__step {{ $meta['step'] < $step ? 'is-done' : '' }} {{ $meta['step'] === $step ? 'is-current' : '' }}">
                            <span class="yk-cit__step-dot">{!! $meta['step'] < $step ? $icon('check', 18) : $icon($meta['icon'], 18) !!}</span>
                            <span class="yk-cit__step-label">{{ $meta['label'] }}</span>
                        </li>
                    @endforeach
                </ol>

                <p class="yk-cit__sr-only" aria-live="polite">
                    Step {{ $step }} of {{ $totalSteps }}. {{ $category['label'] }} interests. Answer how much you enjoy each activity.
                </p>

                <div class="yk-cit__cat-head">
                    <span class="yk-cit__cat-icon">{!! $icon($categoryIcon, 24) !!}</span>
                    <div>
                        <p class="yk-cit__kicker">{{ $category['kicker'] }}</p>
                        <h1>{{ $category['label'] }}</h1>
                        <p class="yk-cit__cat-desc">{{ $category['description'] }}</p>
                    </div>
                </div>
                <p class="yk-cit__prompt">How much do you enjoy these activities?</p>
            </header>

            @if ($errors->any())
                <p class="yk-cit__error" role="alert" tabindex="-1">Please answer every question on this page.</p>
            @endif

            @foreach ($questions as $index => $question)
                @php
                    $globalNumber = (($step - 1) * $perPage) + $index + 1;
                    $legendId = 'cit-q-'.$question['id'];
                @endphp
                <fieldset class="yk-cit__question">
                    <legend class="yk-cit__q-legend" id="{{ $legendId }}">
                        <span class="yk-cit__q-num">Q{{ $globalNumber }}</span>
                        <span class="yk-cit__q-text">{{ $question['text'] }}</span>
                    </legend>
                    <div class="yk-cit__scale" role="radiogroup" aria-labelledby="{{ $legendId }}">
                        @foreach ($scaleOptions as $option)
                            <label class="yk-cit__option">
                                <input
                                    type="radio"
                                    name="answers[{{ $question['id'] }}]"
                                    value="{{ $option['value'] }}"
                                    @checked((string) old('answers.'.$question['id'], $answers[$question['id']] ?? '') === (string) $option['value'])
                                    required
                                >
                                <span class="yk-cit__option-value" aria-hidden="true">{{ $option['value'] }}</span>
                                <span class="yk-cit__emoji" aria-hidden="true"><x-yk-icon :name="$option['icon'] ?? 'meh'" class="yk-icon--md" :decorative="true" /></span>
                                <span class="yk-cit__option-label">{{ $option['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <p class="yk-cit__answered" aria-live="polite"><span data-cit-answered>0</span> of {{ count($questions) }} answered</p>

            <div class="yk-cit__actions yk-cit__actions--between">
                <a class="yk-cit__btn-secondary" href="{{ $previousUrl }}">{!! $icon('arrow-left', 18) !!} Back</a>
                <button class="yk-cit__btn" type="submit" data-cit-continue>{{ $nextLabel }} {!! $icon($step === $totalSteps ? 'check' : 'arrow', 18) !!}</button>
            </div>
        </form>
    </div>
</main>
<script src="{{ asset('frontend/assets/js/yuktione-career-interest.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-career-interest.js')) }}" defer></script>
@endsection
