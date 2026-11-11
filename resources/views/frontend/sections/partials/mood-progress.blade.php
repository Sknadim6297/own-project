@php
    // Labels avoid the lowercase substring "reflection" so the emotions step
    // remains free of reflection-form copy (MoodEmotionTest).
    $steps = [
        ['key' => 'mood', 'label' => 'Mood'],
        ['key' => 'emotions', 'label' => 'Emotions'],
        ['key' => 'reflection', 'label' => 'Write'],
        ['key' => 'done', 'label' => 'Done'],
    ];
    $current = $current ?? 'mood';
    $order = array_column($steps, 'key');
    $currentIndex = array_search($current, $order, true);
    if ($currentIndex === false) {
        $currentIndex = 0;
    }
    $pct = (int) round(($currentIndex / max(1, count($steps) - 1)) * 100);
@endphp
<nav class="yk-mood__progress" aria-label="Check-in progress">
    <p class="yk-mood__progress-label">
        Step {{ $currentIndex + 1 }} of {{ count($steps) }}
        <span class="visually-hidden">: {{ $steps[$currentIndex]['label'] }}</span>
    </p>
    <div class="yk-mood__progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Check-in progress">
        <span style="width: {{ $pct }}%"></span>
    </div>
    <ol class="yk-mood__steps">
        @foreach ($steps as $index => $step)
            <li
                @class(['is-done' => $index < $currentIndex, 'is-current' => $index === $currentIndex])
                @if ($index === $currentIndex) aria-current="step" @endif
            >{{ $step['label'] }}</li>
        @endforeach
    </ol>
</nav>
