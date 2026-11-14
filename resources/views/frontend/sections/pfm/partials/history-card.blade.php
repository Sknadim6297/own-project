@php
    $inputs = is_array($item->inputs) ? $item->inputs : [];
    $outputs = is_array($item->outputs) ? $item->outputs : [];
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
    $moneySigned = function ($value) use ($money) {
        $n = (float) $value;
        return ($n < 0 ? '−' : '').$money(abs($n));
    };
    $when = optional($item->updated_at)->timezone($timezone)?->format('d M Y, h:i A');
    $statusLabel = $item->is_draft ? 'Draft' : 'Saved';
    $statusTone = $item->is_draft ? 'warn' : 'good';
    $metrics = [];

    if ($toolKey === 'monthly-money-planner') {
        $metrics = [
            ['label' => 'Income', 'value' => isset($inputs['monthly_income']) ? $money($inputs['monthly_income']) : '—'],
            ['label' => 'Expenses', 'value' => isset($outputs['total_expenses']) ? $money($outputs['total_expenses']) : '—'],
            ['label' => 'Free cash', 'value' => isset($outputs['free_cash']) ? $moneySigned($outputs['free_cash']) : '—', 'tone' => ((float) ($outputs['free_cash'] ?? 0)) < 0 ? 'bad' : null],
            ['label' => 'Savings rate', 'value' => isset($outputs['savings_rate_percent']) ? number_format((float) $outputs['savings_rate_percent'], 2).'%' : '—'],
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
            ['label' => 'Shortfall', 'value' => isset($outputs['shortfall']) ? $money($outputs['shortfall']) : '—'],
            ['label' => 'Coverage', 'value' => isset($outputs['coverage_months_actual']) ? number_format((float) $outputs['coverage_months_actual'], 1).' mo' : '—'],
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
@endphp

<li class="yk-pfm__history-card">
    <div class="yk-pfm__history-top">
        <div>
            <h2 class="yk-pfm__history-title">
                <a href="{{ $viewUrl }}">{{ $item->title ?: ($tool['name'] ?? 'Saved calculation') }}</a>
            </h2>
            <p class="yk-pfm__history-summary">{{ $item->summary }}</p>
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
        <p class="yk-pfm__meta">{{ $when ?: 'Saved recently' }}</p>
        <div class="yk-pfm__history-actions">
            <a class="yk-pfm__chip-btn" href="{{ $viewUrl }}">View results</a>
            <a class="yk-pfm__chip-btn" href="{{ $continueUrl }}">Continue</a>
            <a class="yk-pfm__chip-btn" href="{{ $continueUrl }}">Edit &amp; recalculate</a>
            <form method="post" action="{{ $deleteUrl }}" onsubmit="return confirm('Delete this saved calculation? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="yk-pfm__chip-btn yk-pfm__chip-btn--danger">Delete</button>
            </form>
        </div>
    </div>
</li>
