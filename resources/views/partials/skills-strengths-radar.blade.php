@php
    /** @var array{points: string, labels: array<int, array{x: float, y: float, text: string}>, grid: array<int, string>} $radar */
@endphp
<figure class="yk-ssa__radar" aria-label="Skills radar chart of eight self-rated areas">
    <svg viewBox="0 0 280 280" role="img" aria-labelledby="ssa-radar-title">
        <title id="ssa-radar-title">Self-rated skills across eight areas</title>
        @foreach ($radar['grid'] as $ring)
            <polygon points="{{ $ring }}" fill="none" stroke="#e6ebf2" stroke-width="1"></polygon>
        @endforeach
        <polygon points="{{ $radar['points'] }}" fill="rgba(49, 109, 97, 0.22)" stroke="#316d61" stroke-width="2"></polygon>
        @foreach ($radar['labels'] as $label)
            <text x="{{ $label['x'] }}" y="{{ $label['y'] }}" text-anchor="middle" dominant-baseline="middle">{{ $label['text'] }}</text>
        @endforeach
    </svg>
</figure>
