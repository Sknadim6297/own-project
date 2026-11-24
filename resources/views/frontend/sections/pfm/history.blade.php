@extends('layouts.layout')

@section('title', $tool['name'].' History | YuktiOne')
@section('meta_description', 'Private history of saved '.$tool['name'].' calculations.')
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $isMoneyPlanner = $toolKey === 'monthly-money-planner';
    $isEmergency = $toolKey === 'emergency-fund-calculator';
@endphp
<main class="yk-pfm yk-tools-ds {{ $isEmergency ? 'yk-pfm--ef' : '' }}" data-yk-tools-ds data-yk-pfm @if ($isMoneyPlanner) data-yk-pfm-history="monthly-money-planner" @endif>
    @include('frontend.sections.pfm.partials.page-hero', [
        'crumbs' => [['Money & Finance', route('tools.pfm')], [$tool['name'], route($tool['route'])], ['History', null]],
        'eyebrow' => $isMoneyPlanner || $isEmergency ? 'Calculation History' : 'Private history',
        'heroIcon' => $isEmergency ? 'history' : null,
        'title' => ($isMoneyPlanner || $isEmergency) ? $tool['name'] : $tool['name'].' history',
        'titleId' => 'pfm-history-heading',
        'lead' => $isMoneyPlanner
            ? 'View and manage your previously saved monthly financial plans.'
            : ($isEmergency
                ? 'View and manage your previously saved emergency fund calculations.'
                : 'Only you can see these saved calculations on your YuktiOne account.'),
    ])

    <div class="yk-pfm__wrap yk-pfm__wrap--wide yk-pfm__sheet">
        <section class="yk-pfm__card" aria-labelledby="pfm-history-heading">
            <div class="yk-pfm__history-header">
                <p class="yk-pfm__history-count">
                    @if ($items->isEmpty())
                        Nothing saved yet
                    @else
                        {{ $items->total() }} saved {{ $items->total() === 1 ? 'calculation' : 'calculations' }}
                    @endif
                </p>
                @include('frontend.sections.pfm.partials.new-calculation-action', [
                    'tool' => $tool,
                    'toolKey' => $toolKey,
                    'confirm' => (bool) ($unsavedWork ?? false),
                    'buttonClass' => 'yk-pfm__btn',
                ])
            </div>

            @if (session('status'))
                <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-pfm__error" role="alert">{{ $errors->first() }}</p>
            @endif

            @if ($items->isEmpty())
                <div class="yk-pfm__empty" role="status">
                    <p class="yk-pfm__empty-title">No saved calculations yet</p>
                    <p>
                        @if ($isMoneyPlanner)
                            Create your first monthly financial plan to start tracking your income, expenses and investments.
                        @else
                            Run {{ $tool['name'] }}, then save a result or draft to see it here.
                        @endif
                    </p>
                    @include('frontend.sections.pfm.partials.new-calculation-action', [
                        'tool' => $tool,
                        'toolKey' => $toolKey,
                        'confirm' => false,
                        'buttonClass' => 'yk-pfm__btn',
                    ])
                </div>
            @else
                <ul class="yk-pfm__history">
                    @foreach ($items as $item)
                        @include('frontend.sections.pfm.partials.history-card', [
                            'item' => $item,
                            'tool' => $tool,
                            'toolKey' => $toolKey,
                            'timezone' => $timezone,
                        ])
                    @endforeach
                </ul>
                @if ($items->hasPages())
                    <div class="yk-pfm__pager">{{ $items->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
