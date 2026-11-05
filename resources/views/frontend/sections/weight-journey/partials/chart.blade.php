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
@endphp
<div class="yk-wj__chart">
    @if ($points === [])
        <div class="yk-wj__empty" role="status">No measurements in this period yet.</div>
    @else
        <svg viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Weight line chart from {{ $points[0]['date'] }} to {{ $points[count($points)-1]['date'] }}">
            <rect x="0" y="0" width="{{ $w }}" height="{{ $h }}" fill="#fbfcfe"></rect>
            @if ($min !== null)
                <text x="8" y="{{ $pad + 4 }}" class="yk-wj__chart-axis">{{ number_format($max, 1) }}</text>
                <text x="8" y="{{ $h - $pad + 4 }}" class="yk-wj__chart-axis">{{ number_format($min, 1) }}</text>
            @endif
            @if (count($xs) === 1)
                <circle class="yk-wj__chart-dot" cx="{{ $xs[0] }}" cy="{{ $ys[0] }}" r="5"></circle>
            @else
                <polyline class="yk-wj__chart-line" fill="none"
                    points="@foreach($xs as $i){{ $xs[$i] }},{{ $ys[$i] }} @endforeach"></polyline>
                @foreach ($xs as $i)
                    <circle class="yk-wj__chart-dot" cx="{{ $xs[$i] }}" cy="{{ $ys[$i] }}" r="4"></circle>
                @endforeach
            @endif
        </svg>
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
