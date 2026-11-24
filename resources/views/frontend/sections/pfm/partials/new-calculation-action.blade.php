@php
    /**
     * Shared fresh-start action (Task 22 backend / Task 25 product label).
     * Money Planner uses "New Calculation"; other PFM tools keep "New Test".
     */
    $isMoneyPlanner = ($toolKey ?? null) === 'monthly-money-planner';
    $isEmergency = ($toolKey ?? null) === 'emergency-fund-calculator';
    $label = $isMoneyPlanner ? 'New Calculation' : 'New Test';
    $confirmUnsaved = (bool) ($confirm ?? false);
    $confirmMessage = 'Start a new calculation? Your unsaved changes will be discarded. Previously saved calculations will not be affected.';
    $buttonClass = $buttonClass ?? 'yk-pfm__btn yk-pfm__btn--ghost';
    $formClass = $formClass ?? null;
@endphp
<form
    method="post"
    action="{{ route($tool['route'].'.new-test') }}"
    @if ($formClass) class="{{ $formClass }}" @endif
    @if ($isMoneyPlanner) data-yk-pfm-fresh-start="1" @endif
    @if ($confirmUnsaved)
        onsubmit="if (!confirm(@json($confirmMessage))) { return false; } if (window.ykPfmClearFreshDirty) { window.ykPfmClearFreshDirty(); } return true;"
    @elseif ($isMoneyPlanner)
        onsubmit="return window.ykPfmConfirmFreshStart ? window.ykPfmConfirmFreshStart(@json($confirmMessage)) : true;"
    @endif
>
    @csrf
    <button type="submit" class="{{ $buttonClass }}">
        @if ($isEmergency)<x-yk-icon name="plus" class="yk-icon--sm" />@endif
        {{ $label }}
    </button>
</form>
