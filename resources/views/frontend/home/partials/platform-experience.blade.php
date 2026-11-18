@php
    $brand = config('yuktione.name', 'YuktiOne');
    $whyFeatures = [
        [
            'icon' => 'shield',
            'title' => 'Expert-Led Guidance',
            'description' => 'Connect with verified professionals across wellbeing, career, health, and more.',
        ],
        [
            'icon' => 'platform',
            'title' => 'All-in-One Growth Platform',
            'description' => 'Tools, experts, and insights together — so progress feels simple and connected.',
        ],
        [
            'icon' => 'community',
            'title' => 'Supportive Community',
            'description' => 'Learn, share, and grow with people who care about the same goals you do.',
        ],
        [
            'icon' => 'lock',
            'title' => 'Safe & Private',
            'description' => 'Your conversations and data stay protected with secure booking and privacy-first design.',
        ],
    ];
@endphp

<section class="yukti-why-choose" aria-labelledby="yk-home-experience-title">
    <div class="yukti-why-panel" data-home-reveal>
        <div class="yukti-why-lotus-area" aria-hidden="true">
            <img
                class="yukti-why-lotus"
                src="{{ asset('frontend/assets/img/' . rawurlencode('Glossy Pink Lotus Flower.png')) }}"
                alt=""
                width="2172"
                height="724"
                loading="lazy"
                decoding="async"
            >
        </div>

        <header class="yukti-why-heading">
            <p class="yukti-why-eyebrow">Why {{ $brand }}</p>
            <h2 id="yk-home-experience-title">Why Choose <span>{{ $brand }}?</span></h2>
            <p class="yukti-why-summary">
                A trusted marketplace that brings expert guidance, practical tools, and supportive community into one calm experience.
            </p>
        </header>

        <span class="yukti-why-rule" aria-hidden="true"></span>

        <div class="yukti-why-features">
            @foreach ($whyFeatures as $feature)
                <article class="yukti-why-feature">
                    <span class="yukti-why-feature__icon" aria-hidden="true">
                        {{-- Presentation mapping: identifier -> approved icon silhouette --}}
                        <svg viewBox="0 0 48 40" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            @switch($feature['icon'])
                                @case('shield')
                                    {{-- diamond --}}
                                    <path d="M14 4h20l10 11-20 21L4 15Z"/>
                                    <path d="M4 15h40M14 4l5 11 5-11 5 11 5-11M19 15l5 21 5-21"/>
                                    @break
                                @case('platform')
                                    {{-- layered stack --}}
                                    <path d="M24 4 6 12l18 8 18-8Z"/>
                                    <path d="m6 19 18 8 18-8M6 26l18 8 18-8"/>
                                    @break
                                @case('community')
                                    {{-- people --}}
                                    <circle cx="24" cy="12" r="5"/>
                                    <path d="M14 32c.8-6 4.5-9.5 10-9.5S33.200 26 34 32"/>
                                    <circle cx="10.500" cy="16" r="3.600"/>
                                    <path d="M3.500 32c.4-4.400 2.600-7 6.200-7.500"/>
                                    <circle cx="37.500" cy="16" r="3.600"/>
                                    <path d="M44.500 32c-.4-4.400-2.600-7-6.200-7.500"/>
                                    @break
                                @case('lock')
                                    {{-- shield with check --}}
                                    <path d="M24 3 11 8v10c0 8.500 5.400 14.500 13 18 7.600-3.500 13-9.500 13-18V8L24 3Z"/>
                                    <path d="m18.500 19 4.200 4.200 7.300-8"/>
                                    @break
                            @endswitch
                        </svg>
                    </span>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['description'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
