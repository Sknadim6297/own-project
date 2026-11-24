@extends('layouts.layout')

@section('title', $tool['name'].' | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-goal-body')

@push('styles')
    <link href="{{ asset('frontend/assets/css/yuktione-pfm-goal.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-pfm-goal.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="yk-goal yk-goal--compact-header yk-goal--single" data-yk-goal data-goal-year="{{ now()->year }}">
    @include('frontend.sections.pfm.partials.goal-compact-header', [
        'title' => 'Financial Goal Calculator',
        'titleId' => 'goal-heading',
        'lead' => 'Estimate the monthly investment needed to achieve your financial goal.',
        'eyebrow' => 'Goal planner · all inputs on one page',
        'tool' => $tool,
    ])

    <div class="yk-goal__wrap yk-goal__layout">
        <section class="yk-goal__card" aria-labelledby="goal-card-title">
            <h2 class="visually-hidden" id="goal-card-title">Plan your goal</h2>

            @if (! $open)
                <p class="yk-goal__alert" role="alert">This calculator is unavailable right now.</p>
            @else
                @if ($errors->any())
                    <p class="yk-goal__alert" role="alert">{{ $errors->first() }}</p>
                @endif

                @if ($hasResult)
                    <p class="yk-goal__notice">
                        <x-yk-icon name="check" class="yk-icon--sm" />
                        You have a recent result.
                        <a href="{{ route($tool['route'].'.results') }}">View results</a>
                    </p>
                @endif

                <form method="post" action="{{ route($tool['route'].'.calculate') }}" class="yk-goal__form" data-pfm-form data-yk-goal-single="1" novalidate>
                    @csrf

                    @include('frontend.sections.pfm.partials.goal-fields', ['old' => $old, 'goalTypes' => $goalTypes ?? []])

                    <div class="yk-goal__actions">
                        <button type="submit" class="yk-goal__btn yk-goal__btn--gold" data-pfm-submit>
                            <x-yk-icon name="calculator" class="yk-icon--sm" />
                            Calculate
                        </button>
                        @auth('user')
                            <a class="yk-goal__link" href="{{ route($tool['route'].'.history') }}">
                                <x-yk-icon name="history" class="yk-icon--sm" />
                                View history
                            </a>
                        @endauth
                    </div>
                </form>
            @endif
        </section>

        <aside class="yk-goal__aside" aria-label="Goal summary">
            <section class="yk-goal__summary" aria-labelledby="goal-summary-title">
                <div class="yk-goal__summary-head">
                    <span class="yk-goal__summary-icon" data-goal-icon aria-hidden="true">
                        <x-yk-icon name="target" class="yk-icon--md" />
                    </span>
                    <div>
                        <p class="yk-goal__summary-kicker">Your goal</p>
                        <h2 id="goal-summary-title" data-goal-out="name">Custom</h2>
                    </div>
                </div>
                <dl class="yk-goal__rows">
                    <div><dt>Goal type</dt><dd data-goal-out="type">Custom</dd></div>
                    <div><dt>Cost today</dt><dd data-goal-out="cost">—</dd></div>
                    <div><dt>Target year</dt><dd data-goal-out="year">—</dd></div>
                </dl>
                <div class="yk-goal__future">
                    <p>Estimated cost by then</p>
                    <strong data-goal-out="future">—</strong>
                    <span data-goal-out="future-note">Add a cost and timeline to see this.</span>
                </div>
            </section>

            <section class="yk-goal__how">
                <h2>How it works</h2>
                <ol>
                    <li><span aria-hidden="true">1</span>Your goal's cost rises with inflation until the target date.</li>
                    <li><span aria-hidden="true">2</span>Money you've already set aside grows at the return you assume.</li>
                    <li><span aria-hidden="true">3</span>We show the monthly amount needed to cover what's left.</li>
                </ol>
                <p>Educational estimates only—not financial advice.</p>
            </section>
        </aside>
    </div>
</main>
@push('scripts')
<script src="{{ asset('frontend/assets/js/yuktione-pfm-goal.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-pfm-goal.js')) }}" defer></script>
@endpush
@endsection
