@php
    $brandName = config('yuktione.name', 'YuktiOne');
    $howwork = collect($howworks ?? [])->first();
    $howItWorksSteps = [
        [
            'title' => 'Search for a Professional',
            'description' => 'Explore professionals and find expertise that matches your needs.',
            'icon' => 'search',
            'image' => $howwork->image1 ?? null,
        ],
        [
            'title' => 'View Professional Profile',
            'description' => 'Review professional profiles, services, and available information before choosing.',
            'icon' => 'profile',
            'image' => $howwork->image2 ?? null,
        ],
        [
            'title' => 'Enjoy the Consultation',
            'description' => 'Book an appointment or connect through the consultation options currently available on '.$brandName.'.',
            'icon' => 'consult',
            'image' => $howwork->image3 ?? null,
        ],
    ];
@endphp

<section class="yk-home-how" aria-labelledby="yk-home-how-title">
    <div class="yk-home-container">
        <header class="yk-home-how__heading">
            <p class="yk-home-how__eyebrow">How {{ $brandName }} Works</p>
            <h2 id="yk-home-how-title">Expert Guidance in Three Simple Steps</h2>
            <p>Discover professionals, explore their expertise and connect in a way that works for you.</p>
        </header>

        <div class="yk-home-how__grid">
            @foreach ($howItWorksSteps as $step)
                @php
                    $stepImageUrl = null;
                    $imagePath = $step['image'];

                    if ($imagePath && \Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
                        $stepImageUrl = $imagePath;
                    } elseif ($imagePath) {
                        $cleanImagePath = ltrim($imagePath, '/');
                        $storagePath = \Illuminate\Support\Str::startsWith($cleanImagePath, 'storage/')
                            ? \Illuminate\Support\Str::after($cleanImagePath, 'storage/')
                            : $cleanImagePath;

                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath)) {
                            $stepImageUrl = asset('storage/' . $storagePath);
                        } elseif (file_exists(public_path($cleanImagePath))) {
                            $stepImageUrl = asset($cleanImagePath);
                        }
                    }
                @endphp

                <article class="yk-home-how-card">
                    <div class="yk-home-how-card__topline">
                        <span class="yk-home-how-card__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                        @if (!$loop->last)
                            <span class="yk-home-how-card__connector" aria-hidden="true">
                                <svg viewBox="0 0 48 16" focusable="false">
                                    <path d="M1 8h40m-7-6 7 6-7 6"/>
                                </svg>
                            </span>
                        @endif
                    </div>

                    <div class="yk-home-how-card__visual">
                        @if ($stepImageUrl)
                            <img
                                class="yk-home-how-card__photo"
                                src="{{ $stepImageUrl }}"
                                alt=""
                                width="320"
                                height="180"
                                loading="lazy"
                                decoding="async"
                            >
                        @endif
                        <span class="yk-home-how-card__icon" aria-hidden="true">
                            @switch($step['icon'])
                                @case('search')
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <circle cx="28" cy="28" r="14"/>
                                        <path d="m38.5 38.5 12 12"/>
                                        <path d="M22 28h12M28 22v12"/>
                                    </svg>
                                    @break
                                @case('profile')
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <rect x="12" y="10" width="40" height="44" rx="8"/>
                                        <circle cx="32" cy="26" r="8"/>
                                        <path d="M20 46c2.8-6.2 7-9.2 12-9.2S41.2 39.8 44 46"/>
                                        <path d="M42 18h6"/>
                                    </svg>
                                    @break
                                @default
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <path d="M18 42c0-8.8 6.2-16 14-16s14 7.2 14 16"/>
                                        <circle cx="32" cy="20" r="8"/>
                                        <path d="M14 48h36"/>
                                        <path d="M22 52h20"/>
                                        <path d="M40 28c4.5 1.2 8 5.2 8 10"/>
                                    </svg>
                            @endswitch
                        </span>
                    </div>

                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['description'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
