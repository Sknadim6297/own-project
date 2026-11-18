@php
    $label = (string) ($label ?? $tool['name'] ?? '');
    $url = (string) ($url ?? $tool['url'] ?? '#');
    $icon = (string) ($icon ?? $tool['icon'] ?? 'leaf');
    $slug = (string) ($slug ?? $tool['slug'] ?? '');
    $description = (string) ($description ?? $tool['description'] ?? '');

    // Presentation-only copy for the eight mockup tools (does not change DB records).
    $displayCopy = [
        'daily-mood-check-in' => 'Track your mood, reflect and feel more balanced.',
        'career-interest-test' => 'Discover your strengths and ideal career path.',
        'bmi-bmr-calculator' => 'Check your body metrics and fitness range.',
        'tarot-reflection' => 'Get intuitive insights and guidance for your journey.',
        'monthly-money-planner' => 'Plan, track and manage your monthly finances.',
        'parenting-stress-check' => 'Assess your parenting stress and get helpful insights.',
        'daily-mindfulness-calm' => 'Simple practices to reduce stress and stay centered.',
        'digital-journal' => 'Write, reflect and build a healthier, happier you.',
    ];
    if (isset($displayCopy[$slug])) {
        $description = $displayCopy[$slug];
    } elseif ($description === '') {
        $description = 'Open this tool for practical guidance and a clearer next step.';
    }

    $illustration = \App\Support\Tools\ToolIllustration::for($slug, $icon);
@endphp
<a class="yk-tool-card yk-tool-card--{{ $icon }}{{ $illustration ? '' : ' yk-tool-card--fallback' }}" href="{{ $url }}">
    <div class="yk-tool-card__content">
        <span class="yk-tool-card__icon" aria-hidden="true">
            @include('frontend.sections.partials.tool-icon-svg', ['icon' => $icon])
        </span>
        <h3 class="yk-tool-card__title">{{ $label }}</h3>
        <p class="yk-tool-card__desc">{{ $description }}</p>
        <span class="yk-tool-card__go" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h12M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </div>
    <div class="yk-tool-card__art" aria-hidden="true">
        <span class="yk-tool-card__art-wave"></span>
        @if ($illustration)
            <img
                class="yk-tool-card__illust"
                src="{{ asset(\App\Support\Tools\ToolIllustration::DIRECTORY.$illustration) }}"
                alt=""
                width="180"
                height="140"
                loading="lazy"
                decoding="async"
            >
        @else
            <span class="yk-tool-card__illust-fallback">
                @include('frontend.sections.partials.tool-icon-svg', ['icon' => $icon])
            </span>
        @endif
    </div>
</a>
