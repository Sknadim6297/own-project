@php
    $inputs = is_array($item->inputs) ? $item->inputs : [];
    $outputs = is_array($item->outputs) ? $item->outputs : [];
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
    $moneySigned = function ($value) use ($money) {
        $n = (float) $value;

        return ($n < 0 ? '−' : '').$money(abs($n));
    };
    $createdAt = optional($item->created_at)->timezone($timezone)?->format('d M Y, h:i A');
    $updatedAt = optional($item->updated_at)->timezone($timezone)?->format('d M Y, h:i A');
    $showUpdated = $createdAt
        && $updatedAt
        && $item->created_at
        && $item->updated_at
        && $item->updated_at->getTimestamp() !== $item->created_at->getTimestamp();
    $when = $updatedAt ?: $createdAt;
    $statusLabel = $item->is_draft ? 'Draft' : 'Saved';
    $statusTone = $item->is_draft ? 'warn' : 'good';
    $metrics = [];
    $isMoneyPlanner = $toolKey === 'monthly-money-planner';
    $isEmergency = $toolKey === 'emergency-fund-calculator';
    $supportsEditInPlace = in_array($toolKey, ['monthly-money-planner', 'emergency-fund-calculator'], true);
    $showDetailedDates = $isMoneyPlanner || $isEmergency;

    if ($isMoneyPlanner) {
        $metrics = [
            ['label' => 'Monthly income', 'value' => isset($inputs['monthly_income']) ? $money($inputs['monthly_income']) : '—'],
            ['label' => 'Expenses & EMI', 'value' => isset($outputs['total_expenses']) ? $money($outputs['total_expenses']) : '—'],
            ['label' => 'Investments', 'value' => isset($inputs['investments']) ? $money($inputs['investments']) : '—'],
            [
                'label' => 'Available cash',
                'value' => isset($outputs['free_cash']) ? $moneySigned($outputs['free_cash']) : '—',
                'tone' => ((float) ($outputs['free_cash'] ?? 0)) < 0 ? 'bad' : null,
            ],
            ['label' => 'Status', 'value' => $outputs['health_label'] ?? '—', 'tone' => $outputs['health_tone'] ?? null],
        ];
    } elseif ($toolKey === 'net-worth-calculator') {
        $metrics = [
            ['label' => 'Assets', 'value' => isset($outputs['total_assets']) ? $money($outputs['total_assets']) : '—'],
            ['label' => 'Liabilities', 'value' => isset($outputs['total_liabilities']) ? $money($outputs['total_liabilities']) : '—'],
            ['label' => 'Net worth', 'value' => isset($outputs['net_worth']) ? $moneySigned($outputs['net_worth']) : '—', 'tone' => ((float) ($outputs['net_worth'] ?? 0)) < 0 ? 'bad' : 'good'],
        ];
    } elseif ($toolKey === 'emergency-fund-calculator') {
        $metrics = [
            ['label' => 'Target', 'value' => isset($outputs['target_amount']) ? $money($outputs['target_amount']) : '—'],
            ['label' => 'Current savings', 'value' => isset($inputs['current_savings']) ? $money($inputs['current_savings']) : '—'],
            ['label' => 'Remaining', 'value' => isset($outputs['shortfall']) ? $money($outputs['shortfall']) : '—'],
            ['label' => 'Coverage', 'value' => isset($outputs['coverage_months_actual']) ? number_format((float) $outputs['coverage_months_actual'], 1).' mo' : '—'],
            [
                'label' => 'Status',
                'value' => ((float) ($outputs['shortfall'] ?? 0)) <= 0 ? 'Target achieved' : 'Building fund',
                'tone' => ((float) ($outputs['shortfall'] ?? 0)) <= 0 ? 'good' : 'warn',
            ],
        ];
    } elseif ($toolKey === 'financial-goal-calculator') {
        $metrics = [
            ['label' => 'Monthly', 'value' => isset($outputs['monthly_investment']) ? $money($outputs['monthly_investment']) : '—'],
            ['label' => 'Corpus', 'value' => isset($outputs['required_corpus']) ? $money($outputs['required_corpus']) : '—'],
            ['label' => 'Gap', 'value' => isset($outputs['funding_gap']) ? $money($outputs['funding_gap']) : '—'],
        ];
    } elseif ($toolKey === 'debt-emi-calculator') {
        $metrics = [
            ['label' => 'EMI', 'value' => isset($outputs['emi']) ? $money($outputs['emi']) : '—'],
            ['label' => 'Interest saved', 'value' => isset($outputs['interest_saved']) ? $money($outputs['interest_saved']) : '—'],
            ['label' => 'Months saved', 'value' => isset($outputs['months_saved']) ? (string) ((int) $outputs['months_saved']) : '—'],
        ];
    } elseif ($toolKey === 'retirement-calculator') {
        $metrics = [
            ['label' => 'Corpus', 'value' => isset($outputs['required_corpus']) ? $money($outputs['required_corpus']) : '—'],
            ['label' => 'Monthly', 'value' => isset($outputs['monthly_investment']) ? $money($outputs['monthly_investment']) : '—'],
            ['label' => 'Readiness', 'value' => isset($outputs['readiness_score']) ? ((int) $outputs['readiness_score']).'%' : '—'],
        ];
    } elseif ($toolKey === 'inflation-calculator') {
        $metrics = [
            ['label' => 'Future cost', 'value' => isset($outputs['future_cost']) ? $money($outputs['future_cost']) : '—'],
            ['label' => 'Increase', 'value' => isset($outputs['increase']) ? $money($outputs['increase']) : '—'],
            ['label' => 'Purchasing power', 'value' => isset($outputs['purchasing_power']) ? $money($outputs['purchasing_power']) : '—'],
        ];
    } elseif ($toolKey === 'savings-investment-calculator') {
        $metrics = [
            ['label' => 'Corpus', 'value' => isset($outputs['corpus']) ? $money($outputs['corpus']) : '—'],
            ['label' => 'Invested', 'value' => isset($outputs['invested']) ? $money($outputs['invested']) : '—'],
            ['label' => 'Gains', 'value' => isset($outputs['gains']) ? $money($outputs['gains']) : '—'],
        ];
    }

    $viewUrl = route($tool['route'].'.history.show', $item);
    $continueUrl = route($tool['route'].'.continue', $item);
    $deleteUrl = route($tool['route'].'.history.destroy', $item);
    $cardTitle = $isMoneyPlanner
        ? 'Monthly Money Plan'
        : ($isEmergency
            ? ($item->title ?: 'Emergency Fund Plan')
            : ($item->title ?: ($tool['name'] ?? 'Saved calculation')));
@endphp

<li class="yk-pfm__history-card {{ $isEmergency ? 'yk-ef__history-card' : '' }}">
    <div class="yk-pfm__history-top">
        <div>
            <h2 class="yk-pfm__history-title">
                <a href="{{ $viewUrl }}">
                    @if ($isEmergency)<x-yk-icon name="shield-check" class="yk-icon--sm" />@endif
                    {{ $cardTitle }}
                </a>
            </h2>
            <p class="yk-pfm__history-summary">{{ $item->summary }}</p>
            @if ($showDetailedDates)
                <p class="yk-pfm__history-dates">
                    @if ($createdAt)
                        <span>Saved {{ $createdAt }}</span>
                    @endif
                    @if ($showUpdated)
                        <span aria-hidden="true">·</span>
                        <span>Updated {{ $updatedAt }}</span>
                    @endif
                </p>
            @endif
        </div>
        <span class="yk-pfm__pill yk-pfm__pill--{{ $statusTone }}">{{ $statusLabel }}</span>
    </div>

    @if ($metrics !== [])
        <dl class="yk-pfm__history-metrics">
            @foreach ($metrics as $metric)
                @php $tone = $metric['tone'] ?? null; @endphp
                <div @class(['yk-pfm__history-metric', 'yk-pfm__history-metric--'.$tone => filled($tone)])>
                    <dt>{{ $metric['label'] }}</dt>
                    <dd>{{ $metric['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    <div class="yk-pfm__history-foot">
        @unless ($showDetailedDates)
            <p class="yk-pfm__meta">{{ $when ?: 'Saved recently' }}</p>
        @endunless
        <div class="yk-pfm__history-actions @if ($supportsEditInPlace) yk-pfm__history-actions--solo @endif">
            <a class="yk-pfm__chip-btn" href="{{ $viewUrl }}">Show Saved</a>
            @if ($supportsEditInPlace)
                <a class="yk-pfm__chip-btn" href="{{ $continueUrl }}">
                    @if ($isEmergency)<x-yk-icon name="pencil" class="yk-icon--sm" />@endif
                    Edit Calculation
                </a>
            @else
                <a class="yk-pfm__chip-btn" href="{{ $continueUrl }}">Continue</a>
                <a class="yk-pfm__chip-btn" href="{{ $continueUrl }}">Edit &amp; recalculate</a>
            @endif
            <form method="post" action="{{ $deleteUrl }}" onsubmit="return confirm('Delete this saved calculation? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="yk-pfm__chip-btn yk-pfm__chip-btn--danger">Delete</button>
            </form>
        </div>
    </div>
</li>
