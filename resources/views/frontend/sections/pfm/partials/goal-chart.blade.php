@php
    /** @var array{months:int, points:list<array{month:int,savings:float,cost:float}>} $projection */
    $pts = $projection['points'];
    $months = max(1, (int) $projection['months']);
    $compact = $compact ?? false;
    $w = $compact ? 340 : 640;
    $h = $compact ? 250 : 280;
    $padL = $compact ? 50 : 62;
    $padR = 16;
    $padT = 14;
    $padB = 32;
    $fs = $compact ? 11.5 : 12.5;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $uid = 'gc'.($compact ? 'm' : 'd');

    $yMax = max(1, max(array_column($pts, 'savings')), max(array_column($pts, 'cost')));
    $raw = $yMax / 4;
    $pow = 10 ** floor(log10($raw));
    $n = $raw / $pow;
    $mult = $n <= 1 ? 1 : ($n <= 2 ? 2 : ($n <= 2.5 ? 2.5 : ($n <= 5 ? 5 : 10)));
    $step = $mult * $pow;
    $top = $step * max(1, ceil($yMax / $step));

    $X = fn ($month) => round($padL + ($month / $months) * $plotW, 2);
    $Y = fn ($value) => round($padT + $plotH - (min($value, $top) / $top) * $plotH, 2);

    $line = function (string $key) use ($pts, $X, $Y) {
        $d = '';
        foreach ($pts as $i => $p) {
            $d .= ($i === 0 ? 'M' : 'L').$X($p['month']).' '.$Y($p[$key]).' ';
        }

        return trim($d);
    };
    $savingsLine = $line('savings');
    $costLine = $line('cost');
    $baseY = $padT + $plotH;
    $lastPoint = end($pts);
    $areaPath = $savingsLine.' L'.$X($lastPoint['month']).' '.$baseY.' L'.$X(0).' '.$baseY.' Z';

    $short = function (float $v): string {
        $trim = fn (float $x, int $d) => rtrim(rtrim(number_format($x, $d, '.', ''), '0'), '.');
        if ($v >= 1e7) {
            return '₹'.$trim($v / 1e7, 2).' Cr';
        }
        if ($v >= 1e5) {
            return '₹'.$trim($v / 1e5, 2).' L';
        }
        if ($v >= 1e3) {
            return '₹'.$trim($v / 1e3, 1).'K';
        }

        return '₹'.number_format($v);
    };

    $yearsTotal = $months / 12;
    $target = $compact ? 4 : 6;
    $stepYears = 1;
    foreach ([1, 2, 5, 10, 20, 25, 50] as $candidate) {
        $stepYears = $candidate;
        if ($yearsTotal / $candidate <= $target) {
            break;
        }
    }
    $xTicks = [];
    for ($t = $stepYears; $t * 12 <= $months; $t += $stepYears) {
        $xTicks[] = $t;
    }
    $ariaLabel = sprintf(
        'Line chart over %s. Projected savings reach %s while the goal cost rises to %s.',
        $yearsTotal == floor($yearsTotal) ? (int) $yearsTotal.' years' : round($yearsTotal, 1).' years',
        $short((float) $lastPoint['savings']),
        $short((float) $lastPoint['cost'])
    );
@endphp
<svg class="yk-goalr__svg {{ $compact ? 'yk-goalr__svg--compact' : 'yk-goalr__svg--wide' }}" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="{{ $ariaLabel }}" preserveAspectRatio="xMidYMid meet" focusable="false">
    <defs>
        <linearGradient id="{{ $uid }}-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#14705f" stop-opacity=".32"/>
            <stop offset="1" stop-color="#14705f" stop-opacity="0"/>
        </linearGradient>
    </defs>

    @for ($tick = 0; $tick * $step <= $top + 0.0001; $tick++)
        @php $value = $tick * $step; @endphp
        <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $Y($value) }}" y2="{{ $Y($value) }}" stroke="{{ $tick === 0 ? '#c9c2ae' : '#ebe6d9' }}" stroke-width="1"/>
        <text x="{{ $padL - 8 }}" y="{{ $Y($value) + 4 }}" text-anchor="end" font-size="{{ $fs }}" fill="#5d6a65">{{ $short((float) $value) }}</text>
    @endfor

    <text x="{{ $X(0) }}" y="{{ $h - 9 }}" text-anchor="start" font-size="{{ $fs }}" fill="#5d6a65">Today</text>
    @foreach ($xTicks as $t)
        <text x="{{ $X($t * 12) }}" y="{{ $h - 9 }}" text-anchor="{{ $t * 12 === $months ? 'end' : 'middle' }}" font-size="{{ $fs }}" fill="#5d6a65">{{ $t }}y</text>
    @endforeach

    <path d="{{ $areaPath }}" fill="url(#{{ $uid }}-fill)"/>
    <path d="{{ $costLine }}" fill="none" stroke="#d69a2d" stroke-width="2.6" stroke-dasharray="7 6" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="{{ $savingsLine }}" fill="none" stroke="#14705f" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>

    <circle cx="{{ $X($lastPoint['month']) }}" cy="{{ $Y($lastPoint['cost']) }}" r="5" fill="#fff" stroke="#d69a2d" stroke-width="3"/>
    <circle cx="{{ $X($lastPoint['month']) }}" cy="{{ $Y($lastPoint['savings']) }}" r="4" fill="#6e0b3c"/>
</svg>
