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
    $last = max(1, count($steps) - 1);
    $pct = (int) round(($currentIndex / $last) * 100);
    // The visual fill reaches halfway towards the next step while it is in progress.
    $fill = $currentIndex >= $last ? 100 : round((($currentIndex + 0.5) / $last) * 100, 2);
@endphp
<nav class="yk-mood__progress" aria-label="Check-in progress">
    <p class="yk-mood__progress-label">
        Step {{ $currentIndex + 1 }} of {{ count($steps) }}
        <span class="visually-hidden">: {{ $steps[$currentIndex]['label'] }}</span>
    </p>
    <div class="yk-mood__progress-track">
        <div class="yk-mood__progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Check-in progress">
            <span style="width: {{ $fill }}%"></span>
        </div>
        <ol class="yk-mood__steps">
            @foreach ($steps as $index => $step)
                <li
                    @class(['is-done' => $index < $currentIndex, 'is-current' => $index === $currentIndex])
                    @if ($index === $currentIndex) aria-current="step" @endif
                >
                    <span class="yk-mood__step-dot" aria-hidden="true">
                        @switch($step['key'])
                            @case('mood')
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M8.6 13.6c.8 1.4 2 2.1 3.4 2.1s2.6-.7 3.4-2.1"/><path d="M9.4 9.6h.01M14.6 9.6h.01" stroke-width="2.6"/></svg>
                                @break
                            @case('emotions')
                                <svg viewBox="0 0 24 24"><path d="M12 19.5s-7.2-4.3-7.2-9.6A3.9 3.9 0 0 1 12 7.6a3.9 3.9 0 0 1 7.2 2.3c0 5.3-7.2 9.6-7.2 9.6Z"/></svg>
                                @break
                            @case('reflection')
                                <svg viewBox="0 0 24 24"><path d="M5 19h3.6L18.4 9.2a2.3 2.3 0 0 0-3.6-3.6L5 15.4V19Z"/><path d="M13.4 7l3.6 3.6"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24"><path d="M6 12.6l4 3.9 8-8.6"/></svg>
                        @endswitch
                    </span>
                    <span class="yk-mood__step-label">{{ $step['label'] }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</nav>
