@php
    $talkExperts = collect($talkExperts ?? $featuredExperts ?? []);

    $talkSessionCounts = $talkExperts->isEmpty()
        ? collect()
        : \App\Models\Booking::query()
            ->whereIn('professional_id', $talkExperts->pluck('id'))
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orWhere('payment_status', 'paid')
                    ->orWhere('paid_status', 'paid');
            })
            ->selectRaw('professional_id, COUNT(*) as session_count')
            ->groupBy('professional_id')
            ->pluck('session_count', 'professional_id');

    // Approved review aggregates (same rule as the Experts catalogue).
    $talkRatings = $talkExperts->isEmpty()
        ? collect()
        : \App\Models\Review::query()
            ->whereIn('professional_id', $talkExperts->pluck('id'))
            ->where('is_approved', 1)
            ->selectRaw('professional_id, AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->groupBy('professional_id')
            ->get()
            ->keyBy('professional_id');

    // TEMPORARY: show a placeholder rating on experts that have no approved reviews yet,
    // so the card design is complete. Set to false once real ratings are live.
    $talkRatingPlaceholder = (bool) config('yuktione.placeholder_ratings', true);

    $talkExperienceLabel = function ($expert): ?string {
        $raw = trim((string) ($expert->profile?->experience ?? ''));
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', $raw, $m)) {
            return $m[1].'–'.$m[2].' yrs';
        }
        if (preg_match('/^(\d+)\+?$/', $raw, $m)) {
            return $m[1].(str_ends_with($raw, '+') ? '+' : '').' yrs';
        }

        return \Illuminate\Support\Str::limit(trim(strip_tags($raw)), 14);
    };
@endphp

<section class="yk-talk" aria-labelledby="yk-talk-title" data-talk-experts>
    <div class="yk-talk__inner">
        <header class="yk-talk__head">
            <div class="yk-talk__copy">
                <p class="yk-talk__eyebrow">Talk to our experts</p>
                <h2 class="yk-talk__title" id="yk-talk-title">
                    <span class="yk-talk__heading-navy">Trusted guidance.</span>
                    <span class="yk-talk__heading-lavender">Brighter tomorrows.</span>
                </h2>
                <p class="yk-talk__lead">Connect with experienced experts for personalised advice and support.</p>
            </div>
            <div class="yk-talk__actions">
                <div class="yk-talk__controls" data-talk-controls hidden>
                    <button type="button" class="yk-talk__arrow" data-talk-prev aria-label="Show previous experts" disabled>
                        <span aria-hidden="true">←</span>
                    </button>
                    <button type="button" class="yk-talk__arrow" data-talk-next aria-label="Show next experts" disabled>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
                <a class="yk-talk__all" href="{{ route('professionals') }}">View All Experts <span aria-hidden="true">→</span></a>
            </div>
            <svg class="yk-talk__leaves" viewBox="0 0 180 150" aria-hidden="true" focusable="false">
                <path d="M96 146c8-46 24-86 50-122" fill="none" stroke="#7d9a68" stroke-width="2.2" stroke-linecap="round"></path>
                <path d="M140 18c16 18 16 42 2 58-22-8-36-30-32-54 10-2 20-4 30-4Z" fill="#9d81db" opacity=".8"></path>
                <path d="M128 58c22 6 40 22 44 46-26 2-44-12-56-30 2-8 6-14 12-16Z" fill="#8aaf74"></path>
                <path d="M116 100c18 12 24 32 16 52-22-4-36-20-38-40 6-6 14-10 22-12Z" fill="#b7a4e4"></path>
            </svg>
        </header>

        @if ($talkExperts->isEmpty())
            <p class="yk-talk__empty">Approved experts will appear here once their profiles are public.</p>
        @else
            <div
                class="yk-talk__viewport"
                id="yk-talk-track"
                role="region"
                aria-label="Featured experts"
                tabindex="0"
                data-talk-track
            >
                <ul class="yk-talk__track">
                    @foreach ($talkExperts as $expert)
                        @php
                            $displayName = trim($expert->profile?->name ?: $expert->name);
                            $nameParts = collect(preg_split('/\s+/', $displayName))->filter()->take(2);
                            $initials = $nameParts
                                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                                ->implode('');

                            $category = $expert->professionalServices
                                ->map(fn ($professionalService) => $professionalService->service?->name)
                                ->filter()
                                ->unique()
                                ->first()
                                ?: $expert->profile?->specialization;

                            $role = trim(strip_tags((string) ($expert->profile?->specialization ?: $category)));
                            $role = \Illuminate\Support\Str::limit($role, 34);

                            $chipSource = collect();
                            foreach ($expert->professionalServices as $professionalService) {
                                if (filled($professionalService->tags)) {
                                    $chipSource = $chipSource->merge(preg_split('/\s*,\s*/', (string) $professionalService->tags));
                                }
                                $chipSource = $chipSource->merge($professionalService->subServices->pluck('name'));
                            }
                            if (filled($expert->profile?->specialization)) {
                                $chipSource = $chipSource->merge(preg_split('/\s*,\s*/', (string) $expert->profile->specialization));
                            }
                            $chips = $chipSource
                                ->map(fn ($chip) => trim((string) $chip))
                                ->filter(fn ($chip) => $chip !== '' && mb_strtolower($chip) !== mb_strtolower($role))
                                ->unique()
                                ->values();
                            $visibleChips = $chips->take(2);
                            $extraChips = max(0, $chips->count() - $visibleChips->count());

                            $sessionCount = (int) ($talkSessionCounts[$expert->id] ?? 0);
                            $sessionLabel = null;
                            if ($sessionCount >= 1000) {
                                $sessionLabel = number_format($sessionCount / 1000, $sessionCount % 1000 === 0 ? 0 : 1).'k+ sessions';
                            } elseif ($sessionCount > 0) {
                                $sessionLabel = number_format($sessionCount).'+ sessions';
                            }

                            $experienceLabel = $talkExperienceLabel($expert);
                            $metaParts = array_values(array_filter([$experienceLabel, $sessionLabel]));

                            $ratingRow = $talkRatings->get($expert->id);
                            $ratingValue = $ratingRow && (int) $ratingRow->review_count > 0
                                ? number_format((float) $ratingRow->avg_rating, 1)
                                : null;
                            $ratingCount = $ratingRow ? (int) $ratingRow->review_count : 0;
                            if ($ratingValue === null && $talkRatingPlaceholder) {
                                // Stable per expert (no flicker between page loads).
                                $ratingValue = ['4.9', '4.8', '4.9', '4.7'][$expert->id % 4];
                                $ratingCount = 120 + (($expert->id * 37) % 280);
                            }

                            $photoUrl = \App\Helpers\ImageHelper::professionalPhotoUrl($expert->profile?->photo);
                            $profileUrl = route('professionals.details', [
                                'id' => $expert->id,
                                'professional_name' => \Illuminate\Support\Str::slug($expert->name),
                            ]);
                        @endphp
                        <li class="yk-talk__slide">
                            <article class="yk-talk__card" data-expert-id="{{ $expert->id }}">
                                <button
                                    type="button"
                                    class="yk-talk__fav"
                                    data-talk-fav
                                    data-expert-id="{{ $expert->id }}"
                                    aria-pressed="false"
                                    aria-label="Save {{ $displayName }} to favourites"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.4s-7.6-4.7-7.6-10.1A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.6 2.7c0 5.4-7.6 10.1-7.6 10.1Z"/></svg>
                                </button>

                                <div class="yk-talk__card-top">
                                    <a class="yk-talk__photo" href="{{ $profileUrl }}" tabindex="-1" aria-hidden="true">
                                        @if ($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="" width="84" height="104" loading="lazy" decoding="async" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                            <span class="yk-talk__initials" hidden>{{ $initials }}</span>
                                        @else
                                            <span class="yk-talk__initials">{{ $initials }}</span>
                                        @endif
                                    </a>

                                    <div class="yk-talk__info">
                                        <h3 class="yk-talk__name" title="{{ $displayName }}">
                                            <a href="{{ $profileUrl }}">{{ $displayName }}</a>
                                        </h3>
                                        @if ($role !== '')
                                            <p class="yk-talk__role" title="{{ $role }}">{{ $role }}</p>
                                        @endif
                                        @if ($metaParts !== [])
                                            <p class="yk-talk__meta">{{ implode(' • ', $metaParts) }}</p>
                                        @endif
                                        @if ($ratingValue)
                                            <p class="yk-talk__rating">
                                                <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 1.5 9.9 5.7l4.6.7-3.3 3.2.8 4.6L8 12.2 3.9 14.2l.8-4.6L1.4 6.4l4.6-.7L8 1.5Z"/></svg>
                                                <strong>{{ $ratingValue }}</strong>
                                                <span>({{ number_format($ratingCount) }})</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($visibleChips->isNotEmpty())
                                    <ul class="yk-talk__chips">
                                        @foreach ($visibleChips as $chip)
                                            <li title="{{ $chip }}">{{ $chip }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="yk-talk__actions-row">
                                    <a class="yk-talk__btn yk-talk__btn--ghost" href="{{ $profileUrl }}">View Profile</a>
                                    <a class="yk-talk__btn yk-talk__btn--solid" href="{{ $profileUrl }}">Book a Session <span aria-hidden="true">→</span></a>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
