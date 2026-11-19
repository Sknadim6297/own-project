@php
    /**
     * Display labels match mockup layout; icons keep the previous set the user preferred.
     * Category keys map to existing ExploreCatalog routes (no DB changes).
     */
    $heroRails = [
        ['label' => 'Personal Wellbeing', 'key' => 'mental-health', 'icon' => 'heart', 'hint' => 'Mind & Emotions'],
        ['label' => 'Health & Nutrition', 'key' => 'nutrition-fitness', 'icon' => 'leaf', 'hint' => 'Food • Lifestyle'],
        ['label' => 'Career & Learning', 'key' => 'career-business', 'icon' => 'briefcase', 'hint' => 'Skills • Transitions'],
        ['label' => 'Fitness & Vitality', 'key' => 'health-wellness', 'icon' => 'users', 'hint' => 'Movement • Strength'],
        ['label' => 'Business & Growth', 'key' => 'career-business', 'icon' => 'trend', 'hint' => 'Strategy • Opportunities'],
        ['label' => 'Life Guidance', 'key' => 'astrology-tarot', 'icon' => 'spark', 'hint' => 'Astrology • Clarity'],
    ];
@endphp

@push('scripts')
    <script>
    (function () {
        const rail = document.querySelector('.yk-hero__rail');
        const list = rail && rail.querySelector('.yk-hero__rail-list');
        const bar = rail && rail.querySelector('.yk-hero__rail-progress');
        if (!list || !bar) return;

        const phone = window.matchMedia('(max-width: 959.98px)');

        // Progress pill: thumb width = visible share, thumb position = scroll share.
        const update = () => {
            const max = list.scrollWidth - list.clientWidth;
            const share = list.scrollWidth > 0 ? list.clientWidth / list.scrollWidth : 1;
            bar.style.setProperty('--thumb', (share * 100).toFixed(2) + '%');
            bar.style.setProperty('--p', max > 0 ? (list.scrollLeft / max).toFixed(4) : '0');
            rail.classList.toggle('is-end', max <= 2 || list.scrollLeft >= max - 2);
        };
        list.addEventListener('scroll', () => window.requestAnimationFrame(update), { passive: true });
        window.addEventListener('resize', update);
        update();

        // Mouse drag-to-scroll on phone/tablet widths (touch and trackpad already scroll natively).
        let drag = null;
        let suppressClick = false;
        list.addEventListener('pointerdown', (event) => {
            if (event.pointerType !== 'mouse' || event.button !== 0 || !phone.matches) return;
            drag = { x: event.clientX, left: list.scrollLeft, moved: false };
        });
        window.addEventListener('pointermove', (event) => {
            if (!drag) return;
            const dx = event.clientX - drag.x;
            if (!drag.moved && Math.abs(dx) > 4) {
                drag.moved = true;
                list.classList.add('is-dragging');
            }
            if (drag.moved) list.scrollLeft = drag.left - dx;
        });
        const endDrag = () => {
            if (!drag) return;
            if (drag.moved) suppressClick = true;
            drag = null;
            list.classList.remove('is-dragging');
        };
        window.addEventListener('pointerup', endDrag);
        window.addEventListener('pointercancel', endDrag);
        list.addEventListener('dragstart', (event) => event.preventDefault());
        list.addEventListener('click', (event) => {
            if (!suppressClick) return;
            suppressClick = false;
            event.preventDefault();
            event.stopPropagation();
        }, true);
    })();
    </script>
@endpush

{{-- Poster: Pollianna Bonnett, Pexels License --}}
<section class="yk-hero yk-hero--soft" aria-labelledby="yuktione-home-title">
    <div class="yk-hero__glow yk-hero__glow--a" aria-hidden="true"></div>
    <div class="yk-hero__glow yk-hero__glow--b" aria-hidden="true"></div>

    <div class="yk-hero__grid">
        <div class="yk-hero__copy">
            <p class="yk-hero__eyebrow">Your Everyday Growth Partner</p>
            <h1 id="yuktione-home-title">
                <span class="yk-hero__title-line">Feel Better. Grow Further.</span>
                <span class="yk-hero__title-line">
                    Make <span class="yk-hero__title-accent">Better Choices.</span>
                </span>
            </h1>
            <p class="yk-hero__lead">
                Expert guidance, practical tools and a supportive community for your mind, body, career, business and life.
            </p>
            <div class="yk-hero__actions">
                <a class="yk-hero__btn yk-hero__btn--primary" href="{{ route('professionals') }}">
                    Find an Expert
                    <span class="yk-hero__btn-arrow" aria-hidden="true">→</span>
                </a>
                <a class="yk-hero__btn yk-hero__btn--ghost" href="{{ route('tools.index') }}">Explore Tools</a>
            </div>
        </div>

        <div class="yk-hero__video" data-hero-video>
            <img
                class="yk-hero__poster"
                src="{{ asset('frontend/assets/img/yuktione-hero-poster.jpg') }}"
                alt="A woman with her eyes closed, face lifted toward warm sunlight"
                width="1400"
                height="2100"
                fetchpriority="high"
            >
            <video class="yk-hero__player" preload="none" playsinline>
                <source src="{{ asset('frontend/assets/video/hero-video.mp4') }}" type="video/mp4">
            </video>
            <button class="yk-hero__play" type="button" data-hero-play aria-label="Play the YuktiOne introduction video">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M8.2 5.4v13.2L18.8 12 8.2 5.4Z" fill="currentColor"></path>
                </svg>
            </button>
        </div>

        <aside class="yk-hero__rail" aria-label="Explore by need">
            <p class="yk-hero__rail-label">Explore by need</p>
            <ul class="yk-hero__rail-list">
                @foreach ($heroRails as $item)
                    <li>
                        <a href="{{ route('categories.show', $item['key']) }}">
                            <span class="yk-hero__rail-icon yk-hero__rail-icon--{{ $item['icon'] }}" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    @switch($item['icon'])
                                        @case('heart')
                                            <path d="M12 20s-7-4.4-7-9.4A4 4 0 0 1 12 7a4 4 0 0 1 7 3.6C19 15.6 12 20 12 20Z" fill="currentColor" opacity="0.18"/><path d="M12 20s-7-4.4-7-9.4A4 4 0 0 1 12 7a4 4 0 0 1 7 3.6C19 15.6 12 20 12 20Z" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linejoin="round"/>
                                            @break
                                        @case('leaf')
                                            <path d="M5 19C5 11 11 5 19 5c0 8-6 14-14 14Z" fill="currentColor" opacity="0.16"/><path d="M5 19C5 11 11 5 19 5c0 8-6 14-14 14Z" fill="none" stroke="currentColor" stroke-width="1.65"/><path d="M8 16c2-3 5-5.5 9-7" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round"/>
                                            @break
                                        @case('briefcase')
                                            <rect x="3.5" y="7" width="17" height="12.5" rx="2" fill="currentColor" opacity="0.14"/><rect x="3.5" y="7" width="17" height="12.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.65"/><path d="M9 7V5.8A2.3 2.3 0 0 1 11.3 3.5h1.4A2.3 2.3 0 0 1 15 5.8V7M3.5 12h17" fill="none" stroke="currentColor" stroke-width="1.65"/>
                                            @break
                                        @case('users')
                                            <circle cx="9" cy="8" r="3" fill="currentColor" opacity="0.16"/><circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.65"/><path d="M3.5 19c.6-3 2.8-4.5 5.5-4.5S14 16 14.5 19" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round"/><circle cx="17" cy="9" r="2.4" fill="none" stroke="currentColor" stroke-width="1.65"/><path d="M16 14.5c1.8-.4 3.5.2 4.5 1.8" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round"/>
                                            @break
                                        @case('trend')
                                            <path d="M4 16.5 10 10l3.5 3.5L20 7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 7h5v5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                            @break
                                        @case('spark')
                                            <path d="M12 3.5v4M12 16.5v4M3.5 12h4M16.5 12h4M6.5 6.5l2.8 2.8M14.7 14.7l2.8 2.8M17.5 6.5l-2.8 2.8M9.3 14.7l-2.8 2.8" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round"/>
                                            @break
                                    @endswitch
                                </svg>
                            </span>
                            <span class="yk-hero__rail-text">
                                <span class="yk-hero__rail-name">{{ $item['label'] }}</span>
                                <span class="yk-hero__rail-hint">{{ $item['hint'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="yk-hero__rail-progress" aria-hidden="true"><span></span></div>
        </aside>
    </div>
</section>

{{-- Soft silk band between hero and Explore by Need --}}
<div class="yk-silk-band" aria-hidden="true">
    <img
        src="{{ asset('frontend/assets/img/yuktione-silk-ribbon.jpg') }}"
        alt=""
        loading="lazy"
        decoding="async"
        width="1600"
        height="900"
    >
</div>
