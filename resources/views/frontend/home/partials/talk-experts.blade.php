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

    // Rotate brand tones by card index so adjacent cards never share a colour.
    $talkTones = ['plum', 'magenta', 'lilac', 'copper', 'charcoal', 'berry'];

    $nextSlotLabel = function ($expert): ?string {
        $dayMap = [
            'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7,
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4,
            'friday' => 5, 'saturday' => 6, 'sunday' => 7,
        ];

        $candidates = [];
        foreach ($expert->availabilities ?? [] as $availability) {
            $slots = collect($availability->slots ?? []);
            if ($slots->isEmpty()) {
                continue;
            }

            $weekdaySlots = [];
            foreach ($slots as $slot) {
                $weekday = \Illuminate\Support\Str::lower((string) ($slot->weekday ?? ''));
                $iso = $dayMap[$weekday] ?? null;
                if (!$iso || !filled($slot->start_time)) {
                    continue;
                }
                $weekdaySlots[$iso][] = (string) $slot->start_time;
            }

            if ($weekdaySlots === []) {
                continue;
            }

            $cursor = \Carbon\Carbon::today();
            for ($i = 0; $i < 45; $i++) {
                $date = $cursor->copy()->addDays($i);
                $iso = $date->dayOfWeekIso;
                if (!isset($weekdaySlots[$iso])) {
                    continue;
                }
                sort($weekdaySlots[$iso]);
                try {
                    $time = \Carbon\Carbon::parse($date->toDateString().' '.$weekdaySlots[$iso][0]);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($time->isPast()) {
                    continue;
                }
                $candidates[] = $time;
                break;
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a <=> $b);

        return $candidates[0]->format('D, j M g:i A');
    };

    $qualificationFor = function ($expert): ?string {
        foreach (['education', 'education2'] as $field) {
            $value = trim(strip_tags((string) ($expert->profile?->{$field} ?? '')));
            if ($value !== '' && !\Illuminate\Support\Str::startsWith($value, ['{', '['])) {
                return \Illuminate\Support\Str::limit($value, 42);
            }
        }

        return null;
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

                            $qualification = $qualificationFor($expert);
                            if (!$qualification && filled($expert->profile?->specialization) && $expert->profile?->specialization !== $category) {
                                $qualification = \Illuminate\Support\Str::limit(trim((string) $expert->profile->specialization), 42);
                            }

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
                            $chips = $chipSource->map(fn ($chip) => trim((string) $chip))->filter()->unique()->values();
                            $visibleChips = $chips->take(3);
                            $extraChips = max(0, $chips->count() - $visibleChips->count());

                            $sessionCount = (int) ($talkSessionCounts[$expert->id] ?? 0);
                            $sessionLabel = null;
                            if ($sessionCount >= 1000) {
                                $sessionLabel = number_format($sessionCount / 1000, $sessionCount % 1000 === 0 ? 0 : 1).'k+';
                            } elseif ($sessionCount > 0) {
                                $sessionLabel = $sessionCount.'+';
                            }

                            $photoUrl = \App\Helpers\ImageHelper::professionalPhotoUrl($expert->profile?->photo);
                            $profileUrl = route('professionals.details', [
                                'id' => $expert->id,
                                'professional_name' => \Illuminate\Support\Str::slug($expert->name),
                            ]);
                            $tone = $talkTones[$loop->index % count($talkTones)];
                            $nextSlot = $nextSlotLabel($expert);
                        @endphp
                        <li class="yk-talk__slide">
                            <article class="yk-talk__card yk-talk__card--{{ $tone }}">
                                <header class="yk-talk__card-head">
                                    @if (filled($category))
                                        <span class="yk-talk__badge">{{ $category }}</span>
                                    @endif
                                    <div class="yk-talk__avatar">
                                        @if ($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="{{ $displayName }}" width="48" height="48" loading="lazy" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                            <span class="yk-talk__initials" hidden aria-hidden="true">{{ $initials }}</span>
                                        @else
                                            <span class="yk-talk__initials" aria-hidden="true">{{ $initials }}</span>
                                        @endif
                                    </div>
                                    <h3 class="yk-talk__name">{{ $displayName }}</h3>
                                    @if (filled($qualification))
                                        <p class="yk-talk__credential">{{ $qualification }}</p>
                                    @endif
                                </header>

                                <div class="yk-talk__card-body">
                                    @if ($visibleChips->isNotEmpty())
                                        <ul class="yk-talk__chips">
                                            @foreach ($visibleChips as $chip)
                                                <li>{{ $chip }}</li>
                                            @endforeach
                                            @if ($extraChips > 0)
                                                <li class="yk-talk__chip-more">+{{ $extraChips }}</li>
                                            @endif
                                        </ul>
                                    @endif

                                    @if ($sessionLabel)
                                        <p class="yk-talk__sessions">Sessions <strong>{{ $sessionLabel }}</strong></p>
                                    @endif

                                    <a class="yk-talk__book" href="{{ $profileUrl }}">View Profile</a>
                                </div>

                                @if ($nextSlot)
                                    <footer class="yk-talk__card-foot" title="Next Available Slot: {{ $nextSlot }}">
                                        {{ $nextSlot }}
                                    </footer>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
