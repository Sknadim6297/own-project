@php
    $expertName = trim($profile->name ?? $profile->professional?->name ?? 'Expert');
    $nameParts = collect(preg_split('/\s+/', $expertName))->filter()->take(2);
    $initials = $nameParts->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('');
    $photoPath = $profile->photo ?? null;
    $blockedPhoto = in_array($photoPath, [
        'professional_documents/McPqTOLmpLHhukE6ka0DJmmNWTU3qG6segI57ccy.jpg',
        'uploads/professionals/photo/media_68b52ad01436a.jpg',
    ], true);
    $photoUrl = null;
    if (filled($photoPath) && !$blockedPhoto) {
        $photoUrl = preg_match('/^https?:\/\//i', $photoPath)
            ? $photoPath
            : asset(str_starts_with($photoPath, 'storage/') ? $photoPath : 'storage/' . ltrim($photoPath, '/'));
    }

    $approvedReviews = collect($profile->professional?->reviews ?? [])->filter(fn ($review) => (int) ($review->is_approved ?? 0) === 1)->values();
    $reviewCount = $approvedReviews->count();
    $averageRating = $reviewCount > 0 ? number_format((float) $approvedReviews->avg('rating'), 1) : null;
    $isVerified = ($profile->professional?->status ?? null) === 'accepted';
    $hasAvailability = collect($enabledDates ?? [])->isNotEmpty();

    $formatMoney = function ($amount) {
        if (!is_numeric($amount)) {
            return null;
        }
        $converted = convertCurrency($amount);
        $decimals = ((float) $converted == floor((float) $converted)) ? 0 : 2;
        return getCurrencySymbol() . number_format((float) $converted, $decimals);
    };

    $lowestRate = $rates->filter(fn ($rate) => is_numeric($rate->rate_per_session))->min('rate_per_session');
    $priceLabel = $formatMoney($lowestRate);
    if (!$priceLabel && filled($profile->starting_price) && is_numeric($profile->starting_price)) {
        $priceLabel = $formatMoney($profile->starting_price);
    }

    $experience = trim((string) ($profile->experience ?? ''));
    if ($experience !== '' && !preg_match('/year/i', $experience)) {
        $experience .= str_contains($experience, '-') ? ' years' : ' years of experience';
    }

    $specialization = trim((string) ($profile->specialization ?? ''));
    $chipSource = collect();
    if ($specialization !== '') {
        $chipSource = $chipSource->merge(preg_split('/\s*,\s*/', $specialization));
    }
    if ($services && filled($services->tags)) {
        $chipSource = $chipSource->merge(preg_split('/\s*,\s*/', (string) $services->tags));
    }
    if ($services) {
        $chipSource = $chipSource->merge($services->subServices->pluck('name'));
    }
    if ($requestedService && is_array($requestedService->specializations)) {
        $chipSource = $chipSource->merge($requestedService->specializations);
    }
    $chips = $chipSource->map(fn ($chip) => trim((string) $chip))->filter()->unique()->values();

    $bio = trim(strip_tags((string) ($profile->bio ?? '')));
    $quote = trim(strip_tags((string) ($profile->comments ?? '')));
    if (strlen($quote) > 180) {
        $quote = '';
    }

    $qualifications = collect();
    $educationPayload = $requestedService?->education;
    if (is_string($educationPayload)) {
        $educationPayload = json_decode($educationPayload, true);
    }
    if (is_array($educationPayload) && isset($educationPayload['college_name']) && is_array($educationPayload['college_name'])) {
        foreach ($educationPayload['college_name'] as $index => $college) {
            $degree = trim((string) ($educationPayload['degree'][$index] ?? ''));
            $college = trim((string) $college);
            if ($degree !== '' || $college !== '') {
                $qualifications->push(['title' => $degree !== '' ? $degree : $college, 'place' => $degree !== '' ? $college : '']);
            }
        }
    }
    foreach (['education', 'education2'] as $educationField) {
        $value = trim(strip_tags((string) ($profile->{$educationField} ?? '')));
        if ($value !== '' && !str_starts_with($value, '{') && !str_starts_with($value, '[')) {
            $qualifications->push(['title' => $value, 'place' => '']);
        }
    }
    $qualifications = $qualifications->unique('title')->values();

    $durations = $availabilities->pluck('session_duration')->filter(fn ($duration) => filled($duration))->unique()->values();
    $address = trim((string) ($profile->full_address ?? $profile->address ?? ''));
@endphp

<div class="yk-profile">
    <div class="yk-profile__wrap">
        <nav class="yk-profile__crumbs" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('professionals') }}">Experts</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Expert Profile</span>
        </nav>

        <div class="yk-profile__layout">
            <div>
                <article class="yk-profile__card yk-profile__hero">
                    <div class="yk-profile__photo-wrap">
                        @if ($photoUrl)
                            <img class="yk-profile__photo" src="{{ $photoUrl }}" alt="{{ $expertName }}" width="640" height="720" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <span class="yk-profile__initials" hidden>{{ $initials }}</span>
                        @else
                            <span class="yk-profile__initials">{{ $initials }}</span>
                        @endif
                        @if ($hasAvailability)
                            <span class="yk-profile__available"><i></i> Available for sessions</span>
                        @endif
                    </div>
                    <div class="yk-profile__identity">
                        <div class="yk-profile__name-row">
                            <h1 id="professional_name">{{ $expertName }}</h1>
                            @if ($isVerified)
                                <span class="yk-profile__verified" title="Approved expert">
                                    <svg viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M6.4 11.2 3.7 8.5l1.1-1.1 1.6 1.6 4.8-4.8 1.1 1.1z"></path></svg>
                                </span>
                            @endif
                        </div>
                        @if ($specialization !== '')
                            <p class="yk-profile__meta">{{ $specialization }}</p>
                        @endif
                        <ul class="yk-profile__facts">
                            @if ($averageRating)
                                <li>
                                    <svg viewBox="0 0 16 16" aria-hidden="true"><path fill="#e0a106" d="M8 1.4 9.8 5.8l4.7.4-3.6 3.1 1.1 4.6L8 11.6 3.9 14l1.1-4.6L1.5 6.2l4.7-.4Z"></path></svg>
                                    {{ $averageRating }} ({{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }})
                                </li>
                            @endif
                            @if ($experience !== '')
                                <li>{{ $experience }}</li>
                            @endif
                        </ul>
                        @if ($priceLabel)
                            <p class="yk-profile__price">{{ $priceLabel }} <span>/ session</span></p>
                        @elseif (filled($profile->starting_price))
                            <p class="yk-profile__price">{{ $profile->starting_price }} <span>starting price</span></p>
                        @endif
                        <div class="yk-profile__actions">
                            <a class="yk-profile__book-jump" href="#yk-book">
                                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm12 8H5v10h14V10z"></path></svg>
                                Book a Session
                            </a>
                        </div>
                        @if ($chips->isNotEmpty())
                            <ul class="yk-profile__chips">
                                @foreach ($chips->take(8) as $chip)
                                    <li>{{ $chip }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($bio !== '')
                            <p class="yk-profile__intro">{{ $bio }}</p>
                        @endif
                        <p id="professional_address" class="yk-profile__sr">{{ $address !== '' ? $address : 'Address not available' }}</p>
                    </div>
                    <div class="yk-profile__decor" aria-hidden="true">
                        @if ($quote !== '')
                            <p>“{{ $quote }}”</p>
                        @endif
                        <svg viewBox="0 0 180 140">
                            <path d="M70 130c10-40 28-74 58-104" fill="none" stroke="#8eae78" stroke-width="2" stroke-linecap="round"></path>
                            <path d="M122 22c18 16 20 40 4 56-24-6-40-28-36-52 10-2 22-4 32-4Z" fill="#b7d39a"></path>
                            <path d="M108 58c20 4 36 20 40 42-24 4-42-10-52-28 2-6 6-12 12-14Z" fill="#9d81db" opacity=".8"></path>
                        </svg>
                    </div>
                </article>

                <div class="yk-profile__trust">
                    @if ($isVerified)
                        <article class="yk-profile__card">
                            <span class="yk-profile__trust-icon yk-profile__trust-icon--mint">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2 4 6v6c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6l-8-4zm-1.2 13.2-3-3 1.4-1.4 1.6 1.6 3.8-3.8 1.4 1.4-5.2 5.2z"></path></svg>
                            </span>
                            <div>
                                <h2>Verified Expert</h2>
                                <p>Profile approved</p>
                            </div>
                        </article>
                    @endif
                    <article class="yk-profile__card">
                        <span class="yk-profile__trust-icon yk-profile__trust-icon--blue">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a5 5 0 0 0-5 5v3H6v12h12V10h-1V7a5 5 0 0 0-5-5zm0 2a3 3 0 0 1 3 3v3H9V7a3 3 0 0 1 3-3z"></path></svg>
                        </span>
                        <div>
                            <h2>Safe &amp; Private</h2>
                            <p>Booked through your YuktiOne account</p>
                        </div>
                    </article>
                    <article class="yk-profile__card">
                        <span class="yk-profile__trust-icon yk-profile__trust-icon--gold">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m12 3 2.6 5.4 6 .9-4.3 4.2 1 5.9L12 16.8 6.7 19.4l1-5.9L3.4 9.3l6-.9L12 3z"></path></svg>
                        </span>
                        <div>
                            <h2>{{ $averageRating ? $averageRating . ' rating' : 'No reviews yet' }}</h2>
                            <p>{{ $reviewCount > 0 ? 'From ' . $reviewCount . ' ' . \Illuminate\Support\Str::plural('approved review', $reviewCount) : 'Approved reviews will appear here' }}</p>
                        </div>
                    </article>
                </div>

                <div class="yk-profile__tabs" role="tablist" aria-label="Profile sections">
                    <button class="yk-profile__tab is-active" type="button" role="tab" id="yk-tab-about" data-yk-tab aria-selected="true" aria-controls="yk-panel-about">About</button>
                    <button class="yk-profile__tab" type="button" role="tab" id="yk-tab-services" data-yk-tab aria-selected="false" aria-controls="yk-panel-services">Services</button>
                    <button class="yk-profile__tab" type="button" role="tab" id="yk-tab-reviews" data-yk-tab aria-selected="false" aria-controls="yk-panel-reviews">Reviews{{ $reviewCount ? ' ('.$reviewCount.')' : '' }}</button>
                    <button class="yk-profile__tab" type="button" role="tab" id="yk-tab-qualifications" data-yk-tab aria-selected="false" aria-controls="yk-panel-qualifications">Qualifications</button>
                </div>

                <div id="yk-panel-about" class="yk-profile__panel" role="tabpanel" aria-labelledby="yk-tab-about" data-yk-panel>
                    <div class="yk-profile__split">
                        <section class="yk-profile__block">
                            <h3>About Me</h3>
                            <p class="yk-profile__bio">{{ $bio !== '' ? $bio : 'This expert has not added a biography yet.' }}</p>
                            @if ($experience !== '' || $specialization !== '')
                                <div class="yk-profile__stats">
                                    @if ($experience !== '')
                                        <div class="yk-profile__stat"><strong>{{ $experience }}</strong><span>Experience</span></div>
                                    @endif
                                    @if ($specialization !== '')
                                        <div class="yk-profile__stat"><strong>{{ $specialization }}</strong><span>Specialisation</span></div>
                                    @endif
                                </div>
                            @endif
                        </section>
                        <section class="yk-profile__block">
                            <h3>Session plans</h3>
                            @if ($rates->isEmpty())
                                <p class="yk-profile__empty">No session plans are published yet.</p>
                            @else
                                <ul class="yk-profile__offers">
                                    @foreach ($rates as $rate)
                                        <li class="yk-profile__offer">
                                            <span class="yk-profile__offer-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm12 8H5v10h14V10z"></path></svg>
                                            </span>
                                            <div>
                                                <h4>{{ ucfirst($rate->session_type) }}</h4>
                                                <p>{{ $rate->num_sessions }} {{ \Illuminate\Support\Str::plural('session', (int) $rate->num_sessions) }}@if($formatMoney($rate->rate_per_session)) · {{ $formatMoney($rate->rate_per_session) }} per session @endif</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    </div>
                    <div class="yk-profile__lower">
                        <section class="yk-profile__block">
                            <h3>Specialisations &amp; areas I support</h3>
                            @if ($chips->isEmpty())
                                <p class="yk-profile__empty">No specialisations have been added.</p>
                            @else
                                <ul class="yk-profile__chips">
                                    @foreach ($chips as $chip)
                                        <li>{{ $chip }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                        <section class="yk-profile__block">
                            <h3>Qualifications &amp; experience</h3>
                            @if ($qualifications->isEmpty())
                                <p class="yk-profile__empty">No qualifications have been added.</p>
                            @else
                                <ul class="yk-profile__quals">
                                    @foreach ($qualifications as $qualification)
                                        <li class="yk-profile__qual">
                                            <span class="yk-profile__qual-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 3 3 8l9 5 9-5-9-5zm-6 8.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"></path></svg>
                                            </span>
                                            <div>
                                                <h4>{{ $qualification['title'] }}</h4>
                                                @if ($qualification['place'] !== '')
                                                    <p>{{ $qualification['place'] }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    </div>
                </div>

                <div id="yk-panel-services" class="yk-profile__panel" role="tabpanel" aria-labelledby="yk-tab-services" data-yk-panel hidden>
                    @if ($services && filled($services->description))
                        <p class="yk-profile__bio">{{ strip_tags($services->description) }}</p>
                    @endif
                    @if ($rates->isEmpty())
                        <p class="yk-profile__empty">No services are published for booking yet.</p>
                    @else
                        <ul class="yk-profile__offers">
                            @foreach ($rates as $rate)
                                <li class="yk-profile__offer">
                                    <span class="yk-profile__offer-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z"></path></svg>
                                    </span>
                                    <div>
                                        <h4>{{ ucfirst($rate->session_type) }}@if($rate->subService) · {{ $rate->subService->name }}@endif</h4>
                                        <p>
                                            {{ $rate->num_sessions }} {{ \Illuminate\Support\Str::plural('session', (int) $rate->num_sessions) }}
                                            @if($formatMoney($rate->final_rate)) · {{ $formatMoney($rate->final_rate) }} total @endif
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div id="yk-panel-reviews" class="yk-profile__panel" role="tabpanel" aria-labelledby="yk-tab-reviews" data-yk-panel hidden>
                    @forelse ($approvedReviews as $review)
                        <article class="yk-profile__review">
                            <strong>{{ number_format((float) $review->rating, 1) }} / 5</strong>
                            @if (filled($review->review_text))
                                <p class="yk-profile__bio">{{ $review->review_text }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="yk-profile__empty">No approved reviews yet.</p>
                    @endforelse
                    <button class="yk-profile__review-btn" type="button" data-bs-toggle="modal" data-bs-target="#reviewModal">Leave a review</button>
                </div>

                <div id="yk-panel-qualifications" class="yk-profile__panel" role="tabpanel" aria-labelledby="yk-tab-qualifications" data-yk-panel hidden>
                    @if ($qualifications->isEmpty())
                        <p class="yk-profile__empty">No qualifications have been added.</p>
                    @else
                        <ul class="yk-profile__quals">
                            @foreach ($qualifications as $qualification)
                                <li class="yk-profile__qual">
                                    <span class="yk-profile__qual-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 3 3 8l9 5 9-5-9-5zm-6 8.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"></path></svg>
                                    </span>
                                    <div>
                                        <h4>{{ $qualification['title'] }}</h4>
                                        @if ($qualification['place'] !== '')
                                            <p>{{ $qualification['place'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($experience !== '')
                        <p class="yk-profile__bio">Experience: {{ $experience }}</p>
                    @endif
                </div>
            </div>

            <aside class="yk-profile__book" id="yk-book" aria-labelledby="yk-book-title">
                <div class="yk-profile__book-head">
                    <span class="yk-profile__book-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm12 8H5v10h14V10z"></path></svg>
                    </span>
                    <div>
                        <h2 id="yk-book-title">Book a Session</h2>
                        <p>Choose a date and time that works for you.</p>
                    </div>
                </div>

                <p class="yk-profile__label">Select session plan</p>
                @if ($rates->isEmpty())
                    <p class="yk-profile__empty">This expert has not published a bookable plan yet.</p>
                @else
                    <div class="yk-profile__types">
                        @foreach ($rates as $rate)
                            @php $safeId = strtolower(str_replace(' ', '_', $rate->session_type)); @endphp
                            <button
                                class="yk-profile__type select-plan"
                                type="button"
                                data-plan="{{ $safeId }}"
                                data-sessions="{{ $rate->num_sessions }}"
                                data-rate="{{ $rate->final_rate }}"
                                data-original-rate="{{ $rate->final_rate }}"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm12 8H5v10h14V10z"></path></svg>
                                <strong>{{ ucfirst($rate->session_type) }}</strong>
                                <small>{{ $formatMoney($rate->final_rate) }}</small>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div id="selected-plan-display" class="yk-profile__plan-note" style="display:none;">
                    <span id="selected-plan-text">None</span>
                    <input type="hidden" id="selected_plan" name="selected_plan" value="">
                </div>

                <p class="yk-profile__label">Session duration</p>
                @if ($durations->isEmpty())
                    <p class="yk-profile__empty">No session duration is published yet.</p>
                @else
                    <div class="yk-profile__durations">
                        @foreach ($durations as $duration)
                            <div class="yk-profile__duration is-current">
                                <strong>{{ $duration }} mins</strong>
                                <small>@if($priceLabel) From {{ $priceLabel }} @else Published duration @endif</small>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p class="yk-profile__label">Pick a date</p>
                <input type="text" id="rangeInput" hidden>
                <div id="calendarDiv"></div>

                <p class="yk-profile__label">Available time slots</p>
                <div class="yk-profile__slots">
                    <div class="radio_select">
                        @foreach($availabilities as $availability)
                            @php
                                $monthFull = $availability->month;
                                $monthMap = [
                                    'January' => 'jan', 'February' => 'feb', 'March' => 'mar',
                                    'April' => 'apr', 'May' => 'may', 'June' => 'jun',
                                    'July' => 'jul', 'August' => 'aug', 'September' => 'sep',
                                    'October' => 'oct', 'November' => 'nov', 'December' => 'dec'
                                ];
                                if (preg_match('/^\d{4}-(\d{2})$/', $monthFull, $matches)) {
                                    $monthNum = (int) $matches[1];
                                    $monthNames = array_keys($monthMap);
                                    $monthAbbr = $monthNum >= 1 && $monthNum <= 12 ? $monthMap[$monthNames[$monthNum - 1]] : 'jan';
                                } else {
                                    $monthAbbr = 'jan';
                                    foreach ($monthMap as $full => $abbr) {
                                        if (stripos($monthFull, $full) !== false) {
                                            $monthAbbr = $abbr;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            @foreach($availability->slots as $slot)
                                @php
                                    $weekday = strtolower($slot->weekday);
                                    $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $slot->start_time)->format('h:i A');
                                    $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $slot->end_time)->format('h:i A');
                                @endphp
                                <div class="slot-box" data-weekday="{{ $weekday }}" data-month="{{ $monthAbbr }}" style="display: none;">
                                    <input type="radio"
                                        id="time_{{ $slot->id }}_{{ $weekday }}_{{ $monthAbbr }}"
                                        name="time"
                                        class="time-slot"
                                        data-id="{{ $slot->id }}"
                                        data-month="{{ $monthAbbr }}"
                                        value="{{ $startTime }} to {{ $endTime }}"
                                        data-start="{{ $startTime }}"
                                        data-end="{{ $endTime }}">
                                    <label for="time_{{ $slot->id }}_{{ $weekday }}_{{ $monthAbbr }}">{{ $startTime }} - {{ $endTime }}</label>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                <p class="yk-profile__empty" id="yk-slots-hint">Choose an available date to see times. A plan with more than one session needs one date per session.</p>
                <ul id="selected-time-list" class="yk-profile__chosen"></ul>

                <a href="javascript:void(0);" class="yk-profile__continue booking" id="bookNowBtn">Continue Booking <span aria-hidden="true">→</span></a>
                <p class="yk-profile__reassure">
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M12 2a5 5 0 0 0-5 5v3H6v12h12V10h-1V7a5 5 0 0 0-5-5zm0 2a3 3 0 0 1 3 3v3H9V7a3 3 0 0 1 3-3z"></path></svg>
                    Booking continues through your YuktiOne account.
                </p>
            </aside>
        </div>
    </div>
</div>

<div class="modal fade yk-profile-modal" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content yk-profile-modal__content">
            <div class="modal-header yk-profile-modal__header">
                <h5 class="modal-title" id="reviewModalLabel">Rate your experience</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body yk-profile-modal__body">
                @if(Auth::guard('user')->check())
                    <form id="reviewForm">
                        @csrf
                        <input type="hidden" name="professional_id" value="{{ $profile->professional_id }}">
                        <div class="star-rating">
                            @foreach ([5, 4, 3, 2, 1] as $star)
                                <input type="radio" id="star{{ $star }}" name="rating" value="{{ $star }}">
                                <label for="star{{ $star }}">{{ $star }} stars</label>
                            @endforeach
                        </div>
                        <label class="yk-profile-modal__label" for="review_text">Your review</label>
                        <textarea class="yk-profile-modal__textarea" name="review_text" id="review_text" rows="4"></textarea>
                    </form>
                @else
                    <p>Please <a href="{{ route('login') }}?redirect={{ url()->current() }}">sign in</a> to leave a review.</p>
                @endif
            </div>
            <div class="modal-footer yk-profile-modal__footer">
                <button type="button" class="yk-profile-modal__cancel" data-bs-dismiss="modal">Cancel</button>
                @if(Auth::guard('user')->check())
                    <button type="button" class="yk-profile-modal__submit" id="submitReview">Submit review</button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-yk-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            var panelId = button.getAttribute('aria-controls');
            document.querySelectorAll('[data-yk-tab]').forEach(function (tab) {
                var active = tab === button;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-yk-panel]').forEach(function (panel) {
                panel.hidden = panel.id !== panelId;
            });
        });
    });
</script>
