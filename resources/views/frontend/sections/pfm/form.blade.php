@extends('layouts.layout')

@section('title', ($editing ?? false) ? 'Edit '.$tool['name'].' | YuktiOne' : $tool['name'].' | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $partialMap = [
        'monthly-money-planner' => ['partial' => 'money-fields', 'steps' => ['Income', 'Expenses', 'Investments']],
        'net-worth-calculator' => ['partial' => 'net-worth-fields', 'steps' => ['Assets', 'Liabilities']],
        'emergency-fund-calculator' => ['partial' => 'emergency-fields', 'steps' => ['Expenses', 'Savings']],
        // FG uses dedicated goal-form (Task 35 single page). Empty steps if this map is ever hit.
        'financial-goal-calculator' => ['partial' => 'goal-fields', 'steps' => []],
        // Task 41 — Debt & EMI: single continuous form (no wizard steps).
        'debt-emi-calculator' => ['partial' => 'debt-fields', 'steps' => []],
        'retirement-calculator' => ['partial' => 'retirement-fields', 'steps' => ['Profile', 'Assumptions']],
        'inflation-calculator' => ['partial' => 'inflation-fields', 'steps' => []],
        'savings-investment-calculator' => ['partial' => 'savings-fields', 'steps' => []],
    ];
    $config = $partialMap[$toolKey] ?? null;
    $partial = $config['partial'] ?? null;
    $steps = $config['steps'] ?? [];
    $wizard = $steps !== [];
    $isEditing = (bool) ($editing ?? false);
    $needsFreshConfirm = (bool) ($unsavedWork ?? false) || $isEditing;
    $isMoneyPlanner = $toolKey === 'monthly-money-planner';
    $isEmergency = $toolKey === 'emergency-fund-calculator';
    $isDebt = $toolKey === 'debt-emi-calculator';
    $isSavings = $toolKey === 'savings-investment-calculator';
    $heroIcons = [
        'monthly-money-planner' => 'wallet',
        'net-worth-calculator' => 'chart-pie',
        'emergency-fund-calculator' => 'shield-check',
        'debt-emi-calculator' => 'calculator',
        'retirement-calculator' => 'wallet',
        'inflation-calculator' => 'calculator',
        'savings-investment-calculator' => 'wallet',
    ];
    $heroIcon = $heroIcons[$toolKey] ?? 'calculator';
    $debtEyebrow = $isEditing
        ? 'Edit Calculation'.($editingId ? ' #'.$editingId : '')
        : 'EMI planner · all inputs on one page';
@endphp
<main class="yk-pfm yk-tools-ds yk-tool-shell {{ $isEmergency ? 'yk-pfm--ef' : '' }}{{ $isDebt ? ' yk-pfm--debt' : '' }}{{ $isSavings ? ' yk-pfm--si' : '' }}" data-yk-tools-ds data-yk-pfm data-yk-tool-shell="1" @if ($isDebt) data-yk-tool-shell-ref="debt-emi" @endif @if ($isDebt) data-yk-debt-structure="1" data-yk-debt-single="1" @endif @if ($isSavings) data-yk-si-form="1" @endif @if ($isEditing) data-yk-pfm-editing="1" @endif>
    @include('frontend.sections.pfm.partials.page-hero', [
        'crumbs' => $isEditing
            ? [['Money & Finance', route('tools.pfm')], [$tool['name'], route($tool['route'])], ['Edit Calculation', null]]
            : [['Money & Finance', route('tools.pfm')], [$tool['name'], null]],
        'eyebrow' => $isEditing
            ? 'Edit Calculation'.($editingId ? ' #'.$editingId : '')
            : ($isDebt
                ? $debtEyebrow
                : ($isEmergency ? 'Emergency Fund Calculator' : ($wizard ? 'Calculator · '.count($steps).' quick steps' : 'Calculator'))),
        'heroIcon' => $heroIcon,
        'title' => ($isEditing && $isEmergency)
            ? 'Edit Emergency Fund Calculation'
            : $tool['name'],
        'titleId' => 'pfm-form-heading',
        'lead' => $isEditing
            ? 'Update your saved financial inputs, recalculate, then Save Changes. '.\App\Support\Tools\ToolPageLeads::PFM_DISCLAIMER
            : \App\Support\Tools\ToolPageLeads::forPfm($toolKey),
    ])

    <div class="yk-pfm__wrap yk-pfm__wrap--wide yk-pfm__sheet">
        <section class="yk-pfm__card" aria-labelledby="pfm-form-heading">
            @if (! $open)
                <p class="yk-pfm__error" role="alert">This calculator is unavailable right now.</p>
            @else
                @if (session('status'))
                    <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
                @endif

                @if ($errors->any())
                    <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
                @endif

                @if ($isEditing)
                    <p class="yk-pfm__note yk-pfm__show-banner" role="status">
                        Editing saved calculation{{ $editingId ? ' #'.$editingId : '' }} · changes are not saved until you Save Changes
                    </p>
                @elseif (! empty($latestSaved))
                    <div class="yk-pfm__status yk-pfm__status--row">
                        <span>You have a saved calculation.</span>
                        <a href="{{ route($tool['route'].'.history.show', $latestSaved) }}">Show Saved</a>
                        <a href="{{ route($tool['route'].'.history') }}">View History</a>
                        @include('frontend.sections.pfm.partials.new-calculation-action', [
                            'tool' => $tool,
                            'toolKey' => $toolKey,
                            'confirm' => $needsFreshConfirm,
                            'buttonClass' => 'yk-pfm__text-btn',
                            'formClass' => 'yk-pfm__inline-form',
                        ])
                    </div>
                @endif

                @if ($hasResult && ! $isEditing)
                    <div class="yk-pfm__status yk-pfm__status--row">
                        <span>You have a recent unsaved result.</span>
                        <a href="{{ route($tool['route'].'.results') }}">View results</a>
                        @include('frontend.sections.pfm.partials.new-calculation-action', [
                            'tool' => $tool,
                            'toolKey' => $toolKey,
                            'confirm' => true,
                            'buttonClass' => 'yk-pfm__text-btn',
                            'formClass' => 'yk-pfm__inline-form',
                        ])
                    </div>
                @endif

                <form
                    method="post"
                    action="{{ route($tool['route'].'.calculate') }}"
                    class="yk-pfm__form"
                    data-pfm-form
                    autocomplete="off"
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

                    <div class="yk-pfm__actions {{ $isDebt ? 'yk-debt__actions' : '' }}">
                        @if ($wizard)
                            <button type="button" class="yk-pfm__btn yk-pfm__btn--ghost" data-pfm-prev hidden>
                                @if ($isDebt)<x-yk-icon name="arrow-left" class="yk-icon--sm" />@endif
                                Back
                            </button>
                            <button type="button" class="yk-pfm__btn" data-pfm-next>
                                Continue
                                @if ($isDebt)<x-yk-icon name="arrow-right" class="yk-icon--sm" />@endif
                            </button>
                        @endif
                        <button type="submit" class="yk-pfm__btn" data-pfm-submit @if ($wizard) hidden @endif>
                            @if ($isEmergency || $isDebt)
                                <x-yk-icon :name="$isEditing ? 'refresh-cw' : 'calculator'" class="yk-icon--sm" />
                            @endif
                            @if ($isEditing)
                                Recalculate
                            @elseif ($isDebt)
                                Calculate EMI
                            @else
                                Calculate
                            @endif
                        </button>
                        @if (! $isEditing)
                            @auth('user')
                                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">
                                    @if ($isEmergency || $isDebt)<x-yk-icon name="history" class="yk-icon--sm" />@endif
                                    View History
                                </a>
                            @endauth
                        @endif
                    </div>
                </form>

                @if ($isEditing)
                    <div class="yk-pfm__actions yk-pfm__actions--secondary">
                        <form method="post" action="{{ route($tool['route'].'.cancel-edit') }}" @if ($needsFreshConfirm) onsubmit="return confirm('Discard unsaved edits? The saved calculation will not change.');" @endif>
                            @csrf
                            <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Cancel Edit</button>
                        </form>
                        @include('frontend.sections.pfm.partials.new-calculation-action', [
                            'tool' => $tool,
                            'toolKey' => $toolKey,
                            'confirm' => true,
                        ])
                    </div>
                @elseif ($isMoneyPlanner)
                    <div class="yk-pfm__actions yk-pfm__actions--secondary">
                        @include('frontend.sections.pfm.partials.new-calculation-action', [
                            'tool' => $tool,
                            'toolKey' => $toolKey,
                            'confirm' => $needsFreshConfirm,
                        ])
                    </div>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
