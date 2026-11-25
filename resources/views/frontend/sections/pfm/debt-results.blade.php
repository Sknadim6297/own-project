@extends('layouts.layout')

@section('title', ($saved ?? false) ? $tool['name'].' Saved Result | YuktiOne' : $tool['name'].' Results | YuktiOne')
@section('meta_description', $tool['description'] ?? 'Debt & EMI repayment plan')
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $isSaved = (bool) ($saved ?? false);
    $view = $presentation ?? \App\Support\Pfm\DebtEmiPresentation::build(
        is_array($inputs ?? null) ? $inputs : [],
        is_array($result ?? null) ? $result : []
    );
    $factBits = [];
    if ($view['money']['purchase'] !== 'Unavailable') {
        $factBits[] = 'Purchase '.$view['money']['purchase'];
    }
    if ($view['down_payment'] !== null && (float) $view['down_payment'] > 0.005) {
        $factBits[] = 'Down payment '.$view['money']['down_payment'];
    }
    if ($view['tenure_label']) {
        $factBits[] = $view['tenure_label'];
    }
    if ($view['annual_rate_percent'] !== null) {
        $rateTxt = rtrim(rtrim(number_format((float) $view['annual_rate_percent'], 2, '.', ''), '0'), '.');
        $factBits[] = ($rateTxt === '' ? '0' : $rateTxt).'% p.a.';
    }
@endphp
<main class="yk-pfm yk-tools-ds yk-pfm--debt yk-debtr" data-yk-tools-ds data-yk-pfm data-yk-debt-results @if ($isSaved) data-yk-debt-saved="1" @endif>
    <header class="yk-debtr__header">
        <div class="yk-debtr__wrap">
            <nav class="yk-debtr__crumbs" aria-label="Breadcrumb">
                <a href="{{ route('tools.index') }}"><span aria-hidden="true">←</span> Back to Tools</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('tools.pfm') }}">Money &amp; Finance</a>
                <span aria-hidden="true">/</span>
                @if ($isSaved)
                    <a href="{{ route($tool['route'].'.history') }}">History</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Show Saved</span>
                @else
                    <a href="{{ route($tool['route']) }}">{{ $tool['name'] }}</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Results</span>
                @endif
            </nav>

            <p class="yk-debtr__eyebrow">
                <x-yk-icon name="calculator" class="yk-icon--sm" />
                {{ $isSaved ? (($calculation->is_draft ?? false) ? 'Saved draft' : 'Saved calculation') : 'Your repayment plan' }}
            </p>

            <div class="yk-debtr__title-row">
                <span class="yk-debtr__title-icon" aria-hidden="true">
                    <x-yk-icon name="wallet" class="yk-icon--lg" />
                </span>
                <div>
                    <h1 id="pfm-results-heading">Your Loan Repayment Plan</h1>
                    <p class="yk-debtr__lead">Understand your monthly EMI, borrowing costs, and repayment options.</p>
                </div>
            </div>

            @if ($factBits !== [])
                <p class="yk-debtr__facts">
                    @foreach ($factBits as $fact)
                        <span>{{ $fact }}</span>
                    @endforeach
                </p>
            @endif
        </div>
    </header>

    <div class="yk-debtr__wrap yk-debtr__layout">
        <div class="yk-debtr__main">
            @if ($errors->any())
                <p class="yk-pfm__error yk-debtr__alert" role="alert">{{ $errors->first() }}</p>
            @endif

            @if ($isSaved && session('status'))
                <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
            @endif

            @if ($isSaved)
                <p class="yk-debtr__banner" role="status">
                    Showing saved calculation #{{ $calculation->id }}
                    @if (! empty($savedAt))
                        · {{ $savedAt }}
                    @endif
                    · read-only
                </p>
            @endif

            @include('frontend.sections.pfm.partials.debt-result-body', ['view' => $view])
        </div>

        <aside class="yk-debtr__aside" aria-label="Plan actions">
            <section class="yk-debtr__keep" aria-labelledby="debtr-keep">
                <p class="yk-debtr__kicker">Next steps</p>
                <h2 id="debtr-keep">{{ $isSaved ? 'Manage this plan' : 'Keep this plan' }}</h2>
                <p class="yk-debtr__keep-text">
                    @if ($isSaved)
                        Recalculate with these inputs, start a new test, or return to history.
                    @elseif ($authenticated)
                        Save it to your history, or adjust the numbers and see how the plan changes.
                    @else
                        Sign in to save it to your history, or adjust the numbers and see how the plan changes.
                    @endif
                </p>

                <div class="yk-debtr__acts">
                    @if ($isSaved)
                        <a class="yk-pfm__btn" href="{{ route($tool['route'].'.continue', $calculation) }}">
                            <x-yk-icon name="pencil" class="yk-icon--sm" />
                            Edit &amp; recalculate
                        </a>
                        @include('frontend.sections.pfm.partials.new-calculation-action', [
                            'tool' => $tool,
                            'toolKey' => 'debt-emi-calculator',
                            'confirm' => (bool) ($unsavedWork ?? false),
                            'buttonClass' => 'yk-pfm__btn yk-pfm__btn--ghost',
                        ])
                        <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">
                            <x-yk-icon name="history" class="yk-icon--sm" />
                            View History
                        </a>
                        <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route']) }}">
                            <x-yk-icon name="arrow-left" class="yk-icon--sm" />
                            Back
                        </a>
                        <form method="post" action="{{ route($tool['route'].'.history.destroy', $calculation) }}" onsubmit="return confirm('Delete this saved calculation? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Delete</button>
                        </form>
                    @else
                        @if ($authenticated)
                            <form method="post" action="{{ route($tool['route'].'.save') }}">
                                @csrf
                                <button type="submit" class="yk-pfm__btn">
                                    <x-yk-icon name="save" class="yk-icon--sm" />
                                    Save result
                                </button>
                            </form>
                            <form method="post" action="{{ route($tool['route'].'.save') }}">
                                @csrf
                                <input type="hidden" name="as_draft" value="1">
                                <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">
                                    <x-yk-icon name="pencil" class="yk-icon--sm" />
                                    Save as draft
                                </button>
                            </form>
                        @else
                            <form method="post" action="{{ route($tool['route'].'.save') }}">
                                @csrf
                                <button type="submit" class="yk-pfm__btn">
                                    <x-yk-icon name="save" class="yk-icon--sm" />
                                    Save result (sign in)
                                </button>
                            </form>
                        @endif

                        <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                            @csrf
                            <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">
                                <x-yk-icon name="refresh-cw" class="yk-icon--sm" />
                                Edit &amp; recalculate
                            </button>
                        </form>

                        @include('frontend.sections.pfm.partials.new-calculation-action', [
                            'tool' => $tool,
                            'toolKey' => 'debt-emi-calculator',
                            'confirm' => true,
                            'buttonClass' => 'yk-pfm__btn yk-pfm__btn--ghost',
                        ])

                        <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route']) }}">
                            <x-yk-icon name="arrow-left" class="yk-icon--sm" />
                            Back to calculator
                        </a>

                        @if ($authenticated)
                            <a class="yk-debtr__hist" href="{{ route($tool['route'].'.history') }}">
                                <x-yk-icon name="history" class="yk-icon--sm" />
                                View history
                            </a>
                        @endif
                    @endif
                </div>
            </section>

            <section class="yk-debtr__assump" aria-labelledby="debtr-assump">
                <h2 id="debtr-assump">Assumptions you used</h2>
                <dl>
                    <div>
                        <dt>Total purchase amount</dt>
                        <dd>{{ $view['money']['purchase'] }}</dd>
                    </div>
                    <div>
                        <dt>Down payment</dt>
                        <dd>{{ $view['money']['down_payment'] }}</dd>
                    </div>
                    <div>
                        <dt>Financed loan amount</dt>
                        <dd>{{ $view['money']['financed'] }}</dd>
                    </div>
                    <div>
                        <dt>Annual interest rate</dt>
                        <dd>{{ $view['annual_rate_percent'] !== null ? rtrim(rtrim(number_format((float) $view['annual_rate_percent'], 2, '.', ''), '0'), '.').'%' : 'Unavailable' }}</dd>
                    </div>
                    <div>
                        <dt>Loan tenure</dt>
                        <dd>{{ $view['tenure_label'] ?? 'Unavailable' }}</dd>
                    </div>
                    <div>
                        <dt>Extra monthly repayment</dt>
                        <dd>{{ $view['money']['extra'] }}</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</main>
@endsection
