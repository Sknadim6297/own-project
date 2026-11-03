<section class="yk-home-experience" aria-labelledby="yk-home-experience-title">
    <div class="yk-home-experience__ornament yk-home-experience__ornament--one" aria-hidden="true"></div>
    <div class="yk-home-experience__ornament yk-home-experience__ornament--two" aria-hidden="true"></div>

    <div class="yk-home-experience__layout yk-home-container">
        <header class="yk-home-experience__intro" data-home-reveal>
            <p class="yk-home-experience__eyebrow">The Yuktione Experience</p>
            <h2 id="yk-home-experience-title">One Platform. Endless Possibilities.</h2>
            <p class="yk-home-experience__summary">
                Explore professional guidance, personalised support and practical resources in one connected experience.
            </p>
            <a class="yk-home-experience__cta" href="{{ route('gridlisting') }}">
                <span>Explore Experts</span>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                </svg>
            </a>
        </header>

        <div class="yk-home-experience__grid">
            <article class="yk-home-experience-card yk-home-experience-card--featured" data-home-reveal>
                <span class="yk-home-experience-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false">
                        <circle cx="14" cy="14" r="8"/>
                        <path d="m20 20 7 7M10 14h8m-4-4v8"/>
                    </svg>
                </span>
                <h3>Explore Experts</h3>
                <p>Discover professionals and explore their services.</p>
                <span class="yk-home-experience-card__index" aria-hidden="true">01</span>
            </article>

            <article class="yk-home-experience-card" data-home-reveal>
                <span class="yk-home-experience-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false">
                        <path d="M5 7.5A3.5 3.5 0 0 1 8.5 4h12A3.5 3.5 0 0 1 24 7.5v8a3.5 3.5 0 0 1-3.5 3.5H14l-5 4v-4.3A3.5 3.5 0 0 1 5 15.5v-8Z"/>
                        <path d="M13 23h7.5A3.5 3.5 0 0 0 24 19.5V19m-10-9h5m-5 4h3"/>
                    </svg>
                </span>
                <h3>Personalised Guidance</h3>
                <p>Find support and consultation options suited to your needs.</p>
                <span class="yk-home-experience-card__index" aria-hidden="true">02</span>
            </article>

            <article class="yk-home-experience-card" data-home-reveal>
                <span class="yk-home-experience-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false">
                        <rect x="4" y="5" width="24" height="21" rx="3"/>
                        <path d="M4 11h24m-17 0v15m-3-19h.01M12 8h.01m4 8h7m-7 4h5"/>
                    </svg>
                </span>
                <h3>Smart Dashboard</h3>
                <p>Keep track of your bookings and appointments in one place.</p>
                <span class="yk-home-experience-card__index" aria-hidden="true">03</span>
            </article>

            <article class="yk-home-experience-card" data-home-reveal>
                <span class="yk-home-experience-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false">
                        <path d="M6 5h14a3 3 0 0 1 3 3v18H9a3 3 0 0 1-3-3V5Z"/>
                        <path d="M23 10h3a2 2 0 0 1 2 2v14H10m1-15h7m-7 4h7"/>
                    </svg>
                </span>
                <h3>{{ config('yuktione.programmes_enabled') ? 'Programmes & Insights' : 'Insights & Guidance' }}</h3>
                <p>{{ config('yuktione.programmes_enabled') ? 'Discover workshops, programmes and helpful articles.' : 'Discover helpful articles and expert guidance.' }}</p>
                <span class="yk-home-experience-card__index" aria-hidden="true">04</span>
            </article>
        </div>
    </div>
</section>
