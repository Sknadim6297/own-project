{{--
    Static "Programmes" showcase band for the home page (presentation only, no database).
    Shown while the live programmes feature is switched off (config yuktione.programmes_enabled).
--}}
@php
    $showcaseProgrammes = [
        [
            'title' => 'Manage Anxiety & Build Calm',
            'lessons' => 4,
            'image' => 'programme-calm.jpg',
            'alt' => 'A person meditating in a calm, sunlit room',
        ],
        [
            'title' => 'Career Clarity & Next Steps',
            'lessons' => 6,
            'image' => 'programme-career.jpg',
            'alt' => 'An open planner and pen ready for career planning',
        ],
        [
            'title' => 'Healthy Habits, Lasting Change',
            'lessons' => 5,
            'image' => 'programme-habits.jpg',
            'alt' => 'A fresh fruit bowl representing healthy habits',
        ],
    ];
    $showcaseUrl = route('programmes.index');
@endphp

<section class="yk-prog-show" aria-labelledby="yk-prog-show-title">
    <div class="yk-prog-show__container">
        <div class="yk-prog-show__panel">
            <span class="yk-prog-show__swoosh" aria-hidden="true"></span>

            <div class="yk-prog-show__intro">
                <span class="yk-prog-show__icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false">
                        <rect x="4" y="6" width="24" height="22" rx="4.5"/>
                        <path d="M10 3.5v5M22 3.5v5M4 13h24"/>
                        <path d="M10.5 18h.01M16 18h.01M21.5 18h.01M10.5 23h.01M16 23h.01M21.5 23h.01" stroke-width="2.4"/>
                    </svg>
                </span>
                <h2 class="yk-prog-show__title" id="yk-prog-show-title">Programmes</h2>
                <p class="yk-prog-show__lead">Structured guidance, practical learning and long-term transformation.</p>
                <a class="yk-prog-show__all" href="{{ $showcaseUrl }}">
                    View All Programmes
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
                </a>
            </div>

            <ul class="yk-prog-show__cards">
                @foreach ($showcaseProgrammes as $programme)
                    <li>
                        <a class="yk-prog-card" href="{{ $showcaseUrl }}">
                            <span class="yk-prog-card__media">
                                <img
                                    src="{{ asset('frontend/assets/img/programmes/'.$programme['image']) }}"
                                    alt="{{ $programme['alt'] }}"
                                    width="720"
                                    height="360"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </span>
                            <h3 class="yk-prog-card__title">{{ $programme['title'] }}</h3>
                            <p class="yk-prog-card__meta">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M6 7.5h12a1.5 1.5 0 0 1 1.5 1.5v9A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V9A1.5 1.5 0 0 1 6 7.5Z"/>
                                    <path d="M9 7.5V6a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 6v1.5"/>
                                </svg>
                                <span>{{ $programme['lessons'] }} Lessons</span>
                                <span class="yk-prog-card__dot" aria-hidden="true">&bull;</span>
                                <span>Self-paced</span>
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="yk-prog-show__dots" aria-hidden="true">
                @foreach ($showcaseProgrammes as $programme)
                    <span></span>
                @endforeach
            </div>
        </div>
    </div>
</section>

@push('scripts')
    <script>
        (function () {
            const root = document.querySelector('.yk-prog-show');
            const track = root && root.querySelector('.yk-prog-show__cards');
            const dots = root ? [...root.querySelectorAll('.yk-prog-show__dots span')] : [];
            if (!track || !dots.length) return;

            const update = () => {
                const cards = [...track.children];
                let active = 0;
                let best = Infinity;
                cards.forEach((card, index) => {
                    const distance = Math.abs(card.offsetLeft - track.offsetLeft - track.clientLeft - parseFloat(getComputedStyle(track).paddingLeft || '0') - track.scrollLeft);
                    if (distance < best) {
                        best = distance;
                        active = index;
                    }
                });
                dots.forEach((dot, index) => dot.classList.toggle('is-active', index === active));
            };

            track.addEventListener('scroll', () => window.requestAnimationFrame(update), { passive: true });
            window.addEventListener('resize', update);
            update();

            // Auto-advance every 3 seconds on phones; loops back to the first card.
            // Pauses while the user touches/hovers or focuses the row, and never runs with reduced motion.
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            const isSwipeRow = () => window.matchMedia('(max-width: 640px)').matches && track.scrollWidth > track.clientWidth + 1;
            let paused = false;
            // Scroll offset that lines a card up with the row's padding edge (matches scroll-padding in CSS).
            const offsetFor = (card) => card.offsetLeft - track.offsetLeft - track.clientLeft - parseFloat(getComputedStyle(track).paddingLeft || '0');
            const advance = () => {
                if (paused || reduceMotion.matches || !isSwipeRow()) return;
                const current = track.scrollLeft;
                const maxScroll = track.scrollWidth - track.clientWidth;
                const next = [...track.children].find((card) => offsetFor(card) - current > 8);
                if (!next || current >= maxScroll - 2) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }
                track.scrollTo({ left: offsetFor(next), behavior: 'smooth' });
            };
            let timer = window.setInterval(advance, 3000);
            const pause = () => { paused = true; };
            const resume = () => { paused = false; };
            track.addEventListener('pointerdown', pause, { passive: true });
            track.addEventListener('pointerup', resume, { passive: true });
            track.addEventListener('pointerleave', resume, { passive: true });
            track.addEventListener('mouseenter', pause);
            track.addEventListener('mouseleave', resume);
            track.addEventListener('focusin', pause);
            track.addEventListener('focusout', resume);
            window.addEventListener('pagehide', () => window.clearInterval(timer));
        })();
    </script>
@endpush
