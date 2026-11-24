@extends('layouts.layout')

@section('title', $tool['name'].' Saved Result | YuktiOne')
@section('meta_description', 'Private saved '.$tool['name'].' calculation.')
@section('body_class', 'yk-pfm-body')

@section('content')
@php
    $savedAt = optional($calculation->updated_at)->timezone($timezone)?->format('d M Y, h:i A');
    $statusLabel = $calculation->is_draft ? 'Saved draft' : 'Saved calculation';
@endphp
@php $isEmergency = $toolKey === 'emergency-fund-calculator'; @endphp
<main class="yk-pfm yk-tools-ds {{ $isEmergency ? 'yk-pfm--ef' : '' }}" data-yk-tools-ds data-yk-pfm>
    @include('frontend.sections.pfm.partials.page-hero', [
        'crumbs' => [['Money & Finance', route('tools.pfm')], ['History', route($tool['route'].'.history')], ['Show Saved', null]],
        'eyebrow' => $statusLabel,
        'heroIcon' => $isEmergency ? 'shield-check' : null,
        'title' => $tool['name'],
        'titleId' => 'pfm-show-heading',
        'lead' => $calculation->summary ?: ($result['summary'] ?? 'Your saved financial picture.'),
        'meta' => $savedAt ? 'Saved '.$savedAt : null,
    ])

    <div class="yk-pfm__wrap yk-pfm__wrap--wide yk-pfm__sheet">
        <section class="yk-pfm__card" aria-labelledby="pfm-show-heading">
            @if (session('status'))
                <p class="yk-pfm__status" role="status">{{ session('status') }}</p>
            @endif

            <p class="yk-pfm__note yk-pfm__show-banner" role="status">
                Showing saved calculation #{{ $calculation->id }}
                @if ($savedAt)
                    · {{ $savedAt }}
                @endif
                · read-only
            </p>

            @unless ($isEmergency)
                <h2 class="yk-pfm__section-title">Financial summary</h2>
            @endunless
            @include('frontend.sections.pfm.partials.result-metrics', ['toolKey' => $toolKey, 'result' => $result, 'inputs' => $inputs])

            @include('frontend.sections.pfm.partials.saved-money-summary', [
                'toolKey' => $toolKey,
                'inputs' => $inputs,
                'result' => $result,
            ])

            @include('frontend.sections.pfm.partials.result-charts', [
                'toolKey' => $toolKey,
                'result' => $result,
                'inputs' => $inputs,
            ])

            @include('frontend.sections.pfm.partials.saved-inputs', [
                'toolKey' => $toolKey,
                'inputs' => $inputs,
            ])

            @if (! $isEmergency && (filled($calculation->summary) || filled($result['summary'] ?? null) || filled($result['health_label'] ?? null)))
                <section class="yk-pfm__saved-conclusion" aria-labelledby="pfm-saved-conclusion-heading">
                    <h2 id="pfm-saved-conclusion-heading" class="yk-pfm__section-title">Conclusion</h2>
                    @if (filled($result['health_label'] ?? null))
                        <p class="yk-pfm__conclusion-health">
                            Money health:
                            <strong>{{ $result['health_label'] }}</strong>
                        </p>
                    @endif
                    <p class="yk-pfm__lead">{{ $calculation->summary ?: ($result['summary'] ?? '') }}</p>
                </section>
            @endif

            <p class="yk-pfm__note yk-pfm__disclaimer">These figures are educational estimates only and are not financial advice.</p>

            <div class="yk-pfm__actions {{ $isEmergency ? 'yk-ef__actions' : '' }}">
                <a class="yk-pfm__btn" href="{{ route($tool['route'].'.continue', $calculation) }}">
                    @if ($isEmergency)<x-yk-icon name="pencil" class="yk-icon--sm" />@endif
                    {{ in_array($toolKey, ['monthly-money-planner', 'emergency-fund-calculator'], true) ? 'Edit Calculation' : 'Edit & recalculate' }}
                </a>
                @include('frontend.sections.pfm.partials.new-calculation-action', [
                    'tool' => $tool,
                    'toolKey' => $toolKey,
                    'confirm' => (bool) ($unsavedWork ?? false),
                ])
                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route'].'.history') }}">
                    @if ($isEmergency)<x-yk-icon name="history" class="yk-icon--sm" />@endif
                    View History
                </a>
                <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route($tool['route']) }}">Back</a>
                <form method="post" action="{{ route($tool['route'].'.history.destroy', $calculation) }}" onsubmit="return confirm('Delete this saved calculation? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="yk-pfm__btn yk-pfm__btn--ghost">Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
