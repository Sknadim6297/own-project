<section class="yk-featured-experts" aria-labelledby="yk-featured-experts-title" data-featured-experts>
    <div class="yk-featured-experts__inner">
        <div class="yk-featured-experts__topline">
            <header class="yk-featured-experts__heading" data-reveal>
                <p class="yk-featured-experts__eyebrow">Expert guidance, made personal</p>
                <h2 id="yk-featured-experts-title">Meet Our Trusted Experts</h2>
                <p class="yk-featured-experts__description">Discover professionals who can guide you towards your next step.</p>
            </header>

            <div class="yk-featured-experts__heading-actions" data-reveal>
                <a class="yk-featured-experts__all-link" href="{{ route('professionals') }}">Explore all experts <span aria-hidden="true">→</span></a>
                <div class="yk-featured-experts__carousel-controls" data-expert-controls hidden>
                    <button type="button" data-expert-previous aria-label="Show previous experts" aria-controls="yk-featured-experts-track" disabled>
                        <span aria-hidden="true">←</span>
                    </button>
                    <button type="button" data-expert-next aria-label="Show next experts" aria-controls="yk-featured-experts-track" disabled>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </div>
        </div>

        @if($featuredExperts->isNotEmpty())
            <div class="yk-featured-experts__filters" role="group" aria-label="Filter experts by category" data-reveal>
                <button class="yk-featured-experts__filter is-active" type="button" data-expert-filter="all" aria-pressed="true">All Experts</button>
                @foreach($featuredExpertCategories as $category)
                    <button class="yk-featured-experts__filter" type="button" data-expert-filter="{{ \Illuminate\Support\Str::slug($category) }}" aria-pressed="false">{{ $category }}</button>
                @endforeach
            </div>

            <div class="yk-featured-experts__grid" id="yk-featured-experts-track" role="region" aria-label="Featured professionals" tabindex="0" data-expert-track>
                @foreach($featuredExperts as $expert)
                    @php
                        $expertCategories = $expert->professionalServices
                            ->map(fn ($professionalService) => $professionalService->service?->name)
                            ->filter()
                            ->unique()
                            ->values();
                        if ($expertCategories->isEmpty() && filled($expert->profile?->specialization)) {
                            $expertCategories->push($expert->profile->specialization);
                        }

                        $specialization = $expert->profile?->specialization;
                        if (!filled($specialization)) {
                            $specialization = $expertCategories->implode(' · ');
                        }

                        $displayName = trim($expert->profile?->name ?: $expert->name);
                        $nameParts = collect(preg_split('/\s+/', $displayName))->filter()->take(2);
                        $initials = $nameParts
                            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                            ->implode('');

                        $photoUrl = \App\Helpers\ImageHelper::professionalPhotoUrl($expert->profile?->photo);

                        $profileUrl = route('professionals.details', [
                            'id' => $expert->id,
                            'professional_name' => \Illuminate\Support\Str::slug($expert->name),
                        ]);
                    @endphp
                    <article
                        class="yk-expert-card"
                        data-expert-card
                        data-categories="{{ $expertCategories->map(fn ($category) => \Illuminate\Support\Str::slug($category))->join(' ') }}"
                        data-reveal
                        style="--reveal-delay: {{ min($loop->index * 60, 420) }}ms"
                    >
                        <a class="yk-expert-card__image-link" href="{{ $profileUrl }}" aria-label="View {{ $displayName }}'s profile">
                            @if($photoUrl)
                                <img class="yk-expert-card__image" src="{{ $photoUrl }}" alt="{{ $displayName }}" width="640" height="800" loading="lazy" data-expert-photo>
                            @endif
                            <span class="yk-expert-card__initials" @if($photoUrl) hidden @endif aria-hidden="true">{{ $initials }}</span>
                        </a>
                        <div class="yk-expert-card__body">
                            <h3 class="yk-expert-card__name" title="{{ $displayName }}">{{ $displayName }}</h3>
                            @if(filled($specialization))
                                <p class="yk-expert-card__specialization" title="{{ $specialization }}">{{ $specialization }}</p>
                            @endif
                            @if(filled($expert->profile?->experience))
                                <p class="yk-expert-card__experience">{{ $expert->profile->experience }}{{ is_numeric($expert->profile->experience) || !preg_match('/\byears?\b/i', (string) $expert->profile->experience) ? ' years' : '' }} experience</p>
                            @endif
                            <div class="yk-expert-card__actions">
                                <a class="yk-expert-card__profile" href="{{ $profileUrl }}">View profile <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="yk-featured-experts__empty" data-filter-empty hidden>No experts are available in this category yet.</p>
        @else
            <p class="yk-featured-experts__empty">Our professionals are updating their profiles. Please check back soon.</p>
        @endif
    </div>
</section>
