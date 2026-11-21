@php
    $hasUpcomingProgrammes = $upcomingProgrammeEvents->isNotEmpty();
    $activeProgrammeTab = 'upcoming';
@endphp

<section class="yk-home-events" aria-labelledby="yk-home-events-title" data-programmes>
    <div class="yk-home-container">
        <header class="yk-home-events__heading" data-programme-reveal>
            <div class="yk-home-events__heading-copy">
                <p class="yk-home-events__eyebrow">Learn, Grow &amp; Connect</p>
                <h2 id="yk-home-events-title"><x-yuktione.two-tone text="Discover Programmes Designed for You" /></h2>
                <p>Explore expert-led sessions, workshops and learning experiences designed to support your personal and professional growth.</p>
            </div>
            <a class="yk-home-events__all-link" href="{{ route('programmes.index') }}">
                View All Programmes
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M4 12h15M13 6l6 6-6 6"/>
                </svg>
            </a>
        </header>

        <div class="yk-home-programmes__toolbar" data-programme-reveal>
            <div class="yk-home-programmes__tabs" role="tablist" aria-label="Programme dates">
                <button
                    class="yk-home-programmes__tab {{ $activeProgrammeTab === 'upcoming' ? 'is-active' : '' }}"
                    id="yk-programmes-upcoming-tab"
                    type="button"
                    role="tab"
                    aria-controls="yk-programmes-upcoming-panel"
                    aria-selected="{{ $activeProgrammeTab === 'upcoming' ? 'true' : 'false' }}"
                    tabindex="{{ $activeProgrammeTab === 'upcoming' ? '0' : '-1' }}"
                    data-programme-tab="upcoming"
                >Upcoming Programmes <span>{{ $upcomingProgrammeEvents->count() }}</span></button>
                <button
                    class="yk-home-programmes__tab {{ $activeProgrammeTab === 'past' ? 'is-active' : '' }}"
                    id="yk-programmes-past-tab"
                    type="button"
                    role="tab"
                    aria-controls="yk-programmes-past-panel"
                    aria-selected="{{ $activeProgrammeTab === 'past' ? 'true' : 'false' }}"
                    tabindex="{{ $activeProgrammeTab === 'past' ? '0' : '-1' }}"
                    data-programme-tab="past"
                >Past Programmes <span>{{ $pastProgrammeEvents->count() }}</span></button>
            </div>
            <div class="yk-home-programmes__carousel-controls" data-programme-controls>
                <button type="button" data-programme-previous aria-label="Show previous programmes" disabled>
                    <span aria-hidden="true">←</span>
                </button>
                <button type="button" data-programme-next aria-label="Show next programmes" disabled>
                    <span aria-hidden="true">→</span>
                </button>
            </div>
        </div>

        <div
            class="yk-home-programmes__panel"
            id="yk-programmes-upcoming-panel"
            role="tabpanel"
            aria-labelledby="yk-programmes-upcoming-tab"
            data-programme-panel="upcoming"
            data-programme-reveal
        >
            @if ($upcomingProgrammeEvents->isNotEmpty())
                <div class="yk-home-events__grid" data-programme-track tabindex="0" aria-label="Upcoming programmes; use arrow keys to scroll">
                    @foreach ($upcomingProgrammeEvents as $programme)
                        @include('frontend.home.partials.programme-card', ['programme' => $programme])
                    @endforeach
                </div>
            @else
                <p class="yk-home-events__empty">
                    No upcoming programmes are scheduled right now.
                    @if ($pastProgrammeEvents->isNotEmpty())
                        <button type="button" data-programme-show-past>Browse past programmes</button> to explore previous sessions.
                    @else
                        Please check back soon.
                    @endif
                </p>
            @endif
        </div>

        <div
            class="yk-home-programmes__panel"
            id="yk-programmes-past-panel"
            role="tabpanel"
            aria-labelledby="yk-programmes-past-tab"
            data-programme-panel="past"
            data-programme-reveal
        >
            @if ($pastProgrammeEvents->isNotEmpty())
                <div class="yk-home-events__grid" data-programme-track tabindex="0" aria-label="Past programmes; use arrow keys to scroll">
                    @foreach ($pastProgrammeEvents as $programme)
                        @include('frontend.home.partials.programme-card', ['programme' => $programme])
                    @endforeach
                </div>
            @else
                <p class="yk-home-events__empty">There are no past programmes to show yet.</p>
            @endif
        </div>
    </div>
</section>
