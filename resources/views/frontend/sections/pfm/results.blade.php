@extends('layouts.layout')

@section('title', ($editing ?? false) ? 'Edit '.$tool['name'].' Results | YuktiOne' : $tool['name'].' Results | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $isEditing = (bool) ($editing ?? false);
    $isEmergency = $toolKey === 'emergency-fund-calculator';
    $isMoneyPlanner = $toolKey === 'monthly-money-planner';
    $isRetirement = $toolKey === 'retirement-calculator';
    $isSavings = $toolKey === 'savings-investment-calculator';
    $recalculateLabel = $isEmergency
        ? 'Recalculate'
        : ($isEditing ? 'Edit inputs' : 'Edit &amp; recalculate');
    $saveLabel = in_array($toolKey, ['monthly-money-planner', 'emergency-fund-calculator'], true)
        ? 'Save Calculation'
        : 'Save result';
    $saveGuestLabel = $saveLabel.' (sign in)';
    $resultHeroIcons = [
        'monthly-money-planner' => 'wallet',
        'net-worth-calculator' => 'chart-pie',
        'emergency-fund-calculator' => 'shield-check',
        'retirement-calculator' => 'wallet',
        'savings-investment-calculator' => 'wallet',
    ];
    $resultHeroIcon = $resultHeroIcons[$toolKey] ?? 'calculator';
@endphp
<main class="yk-pfm yk-tools-ds yk-tool-shell {{ $isEmergency ? 'yk-pfm--ef' : '' }}{{ $isRetirement ? ' yk-pfm--rt' : '' }}{{ $isSavings ? ' yk-pfm--si' : '' }}" data-yk-tools-ds data-yk-pfm data-yk-tool-shell="1" @if ($isEditing) data-yk-pfm-editing="1" @endif @if ($isRetirement) data-yk-rt-results="1" @endif @if ($isSavings) data-yk-si-results="1" @endif>
    @include('frontend.sections.pfm.partials.page-hero', [
        'crumbs' => $isEditing
            ? [['Money & Finance', route('tools.pfm')], [$tool['name'], route($tool['route'])], ['Edit preview', null]]
            : [['Money & Finance', route('tools.pfm')], [$tool['name'], route($tool['route'])], ['Results', null]],
        'eyebrow' => $isEditing
            ? 'Edit preview'.($editingId ? ' · #'.$editingId : '')
            : ($isRetirement ? 'Your retirement plan' : ($isSavings ? 'Your investment plan' : 'Your results')),
        'heroIcon' => $resultHeroIcon,
        'title' => $tool['name'],
        'titleId' => 'pfm-results-heading',
        'lead' => $isEmergency
            ? 'Clear view of your emergency fund target, savings gap, and estimated timeline.'
            : ($isRetirement
                ? 'Understand your estimated corpus, funding gap, and monthly investment under the assumptions you entered.'
                : ($isSavings
                    ? 'Understand your total contributions and estimated investment growth under the assumptions you entered.'
                    : ($result['summary'] ?? ''))),
    ])

    <div class="yk-pfm__wrap yk-pfm__wrap--wide yk-pfm__sheet">
        <section class="yk-pfm__card" aria-labelledby="pfm-results-heading">
            @if ($errors->any())
                <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
            @endif

            @if ($isEditing)
                <p class="yk-pfm__note yk-pfm__show-banner" role="status">
                    Preview only — the saved calculation is unchanged until you Save Changes.
                </p>
            @endif

            @if ($isSavings)
                {{-- Task 51 — consolidated maturity / returns presentation (includes KPI, charts, conclusion). --}}
                @include('frontend.sections.pfm.partials.savings-conclusion', [
                    'result' => $result,
                    'inputs' => $inputs ?? [],
                ])
            @else
                @include('frontend.sections.pfm.partials.result-metrics', ['toolKey' => $toolKey, 'result' => $result, 'inputs' => $inputs ?? []])
                @include('frontend.sections.pfm.partials.result-charts', ['toolKey' => $toolKey, 'result' => $result, 'inputs' => $inputs ?? []])

                @if ($isRetirement)
                    @include('frontend.sections.pfm.partials.retirement-sufficiency', [
                        'result' => $result,
                        'inputs' => $inputs ?? [],
                    ])
                    @include('frontend.sections.pfm.partials.retirement-conclusion', [
                        'result' => $result,
                        'inputs' => $inputs ?? [],
                    ])
                @else
                    <p class="yk-pfm__note yk-pfm__disclaimer">These figures are educational estimates only and are not financial advice.</p>
                @endif
            @endif

            <div class="yk-pfm__actions {{ $isEmergency ? 'yk-ef__actions' : '' }}">
                @if ($isEditing)
                    <form method="post" action="{{ route($tool['route'].'.save') }}">
                        @csrf
                        <button type="submit" class="yk-pfm__btn">
                            @if ($isEmergency)<x-yk-icon name="save" class="yk-icon--sm" />@endif
                            Save Changes
                        </button>
                    </form>
                    <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                        @csrf
                        <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">
                            @if ($isEmergency)<x-yk-icon name="refresh-cw" class="yk-icon--sm" />@endif
                            {!! $recalculateLabel !!}
                        </button>
                    </form>
                    <form method="post" action="{{ route($tool['route'].'.cancel-edit') }}" onsubmit="return confirm('Discard unsaved edits? The saved calculation will not change.');">
                        @csrf
                        <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Cancel Edit</button>
                    </form>
                    @include('frontend.sections.pfm.partials.new-calculation-action', [
                        'tool' => $tool,
                        'toolKey' => $toolKey,
                        'confirm' => true,
                    ])
                @else
                    @if ($authenticated)
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <button type="submit" class="yk-pfm__btn">
                                @if ($isEmergency)<x-yk-icon name="save" class="yk-icon--sm" />@endif
                                {{ $saveLabel }}
                            </button>
                        </form>
                    @else
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <button type="submit" class="yk-pfm__btn">
                                @if ($isEmergency)<x-yk-icon name="save" class="yk-icon--sm" />@endif
                                {{ $saveGuestLabel }}
                            </button>
                        </form>
                    @endif

                    <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                        @csrf
                        <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">
                            @if ($isEmergency)<x-yk-icon name="refresh-cw" class="yk-icon--sm" />@endif
                            {!! $recalculateLabel !!}
                        </button>
                    </form>

                    @include('frontend.sections.pfm.partials.new-calculation-action', [
                        'tool' => $tool,
                        'toolKey' => $toolKey,
                        'confirm' => true,
                    ])

                    @if ($authenticated)
                        <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">
                            @if ($isEmergency)<x-yk-icon name="history" class="yk-icon--sm" />@endif
                            View History
                        </a>
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <input type="hidden" name="as_draft" value="1">
                            <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Save as draft</button>
                        </form>
                    @endif
                @endif
            </div>
        </section>
    </div>
</main>
@endsection
