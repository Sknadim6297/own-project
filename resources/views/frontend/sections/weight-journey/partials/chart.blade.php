@php
    $points = $chart['points'] ?? [];
    $w = 640;
    $h = 220;
    $pad = 28;
    $xs = [];
    $ys = [];
    $min = null;
    $max = null;
    if (count($points) >= 1) {
        $min = min(array_map(fn ($p) => (float) $p['weight_kg'], $points));
        $max = max(array_map(fn ($p) => (float) $p['weight_kg'], $points));
        if ($min === $max) {
            $min -= 1;
            $max += 1;
        }
        foreach ($points as $i => $p) {
            $t = count($points) === 1 ? 0.5 : $i / (count($points) - 1);
            $xs[] = $pad + $t * ($w - 2 * $pad);
            $ys[] = $pad + (1 - (((float) $p['weight_kg'] - $min) / ($max - $min))) * ($h - 2 * $pad);
        }
    }
    $pointCount = count($points);
    $firstDate = $pointCount ? $points[0]['date'] : null;
    $lastDate = $pointCount ? $points[$pointCount - 1]['date'] : null;
    $firstLabel = $pointCount ? $points[0]['label'] : null;
    $lastLabel = $pointCount ? $points[$pointCount - 1]['label'] : null;
@endphp
<div class="yk-wj__chart" data-wj-chart>
    @if ($points === [])
        <div class="yk-wj__empty" role="status">No measurements in this period yet. Missing days stay blank—nothing is invented.</div>
    @else
        <svg
            viewBox="0 0 {{ $w }} {{ $h }}"
            role="img"
            aria-labelledby="wj-chart-title wj-chart-desc"
        >
            <title id="wj-chart-title">Weight trend chart</title>
            <desc id="wj-chart-desc">
                {{ $pointCount }} measurement{{ $pointCount === 1 ? '' : 's' }} from {{ $firstDate }} to {{ $lastDate }}.
                Latest per local day only. Range {{ number_format($min, 1) }} to {{ number_format($max, 1) }} kg.
            </desc>
            <rect x="0" y="0" width="{{ $w }}" height="{{ $h }}" fill="#fbfcfe"></rect>
            @if ($min !== null)
                <line class="yk-wj__chart-grid" x1="{{ $pad }}" y1="{{ $pad }}" x2="{{ $w - $pad }}" y2="{{ $pad }}" aria-hidden="true"></line>
                <line class="yk-wj__chart-grid" x1="{{ $pad }}" y1="{{ $h - $pad }}" x2="{{ $w - $pad }}" y2="{{ $h - $pad }}" aria-hidden="true"></line>
                <text x="8" y="{{ $pad + 4 }}" class="yk-wj__chart-axis">{{ number_format($max, 1) }}</text>
                <text x="8" y="{{ $h - $pad + 4 }}" class="yk-wj__chart-axis">{{ number_format($min, 1) }}</text>
            @endif
            @if (count($xs) === 1)
                <circle class="yk-wj__chart-dot" cx="{{ $xs[0] }}" cy="{{ $ys[0] }}" r="5">
                    <title>{{ $points[0]['date'] }}: {{ $points[0]['label'] }}</title>
                </circle>
            @else
                <polyline class="yk-wj__chart-line" fill="none"
                    points="@foreach($xs as $i => $x){{ $x }},{{ $ys[$i] }} @endforeach"></polyline>
                @foreach ($xs as $i => $x)
                    <circle class="yk-wj__chart-dot" cx="{{ $x }}" cy="{{ $ys[$i] }}" r="4">
                        <title>{{ $points[$i]['date'] }}: {{ $points[$i]['label'] }}</title>
                    </circle>
                @endforeach
            @endif
        </svg>
        <p class="yk-wj__chart-summary" role="status">
            {{ $pointCount }} plotted point{{ $pointCount === 1 ? '' : 's' }}
            ({{ $firstDate }} {{ $firstLabel }} → {{ $lastDate }} {{ $lastLabel }}).
            Same-day readings collapse to the latest local measurement.
        </p>
        <table class="yk-wj__chart-table">
            <caption class="visually-hidden">Weight measurements for the selected period</caption>
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($points as $p)
                    <tr>
                        <td>{{ $p['date'] }}</td>
                        <td>{{ $p['label'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
