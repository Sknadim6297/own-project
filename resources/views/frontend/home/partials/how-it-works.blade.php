@if ($howworks->isNotEmpty())
    @php
        $howwork = $howworks->first();
        $howItWorksSteps = [
            [
                'title' => 'Search for a Professional',
                'description' => 'Explore professionals and find expertise that matches your needs.',
                'image' => $howwork->image1,
            ],
            [
                'title' => 'View Professional Profile',
                'description' => 'Review professional profiles, services and available information before choosing.',
                'image' => $howwork->image2,
            ],
            [
                'title' => 'Enjoy the Consultation',
                'description' => 'Book an appointment or connect through the consultation options currently available on '.config('yuktione.name', 'YuktiOne').'.',
                'image' => $howwork->image3,
            ],
        ];
    @endphp

    <section class="yk-home-how" aria-labelledby="yk-home-how-title">
        <div class="yk-home-container">
            <header class="yk-home-how__heading">
                <p class="yk-home-how__eyebrow">How {{ config('yuktione.name', 'YuktiOne') }} Works</p>
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
                                    <svg viewBox="0 0 40 16" focusable="false">
                                        <path d="M1 8h34m-7-6 7 6-7 6"/>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <div class="yk-home-how-card__image">
                            @if ($stepImageUrl)
                                <img
                                    src="{{ $stepImageUrl }}"
                                    alt="{{ $step['title'] }} illustration"
                                    width="320"
                                    height="220"
                                    loading="lazy"
                                    decoding="async"
                                    data-how-image
                                >
                                <span class="yk-home-how-card__image-fallback" aria-hidden="true" hidden>
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <rect x="9" y="13" width="46" height="34" rx="4"/>
                                        <path d="M20 54h24m-12-7v7M17 23h30M20 31h12m-12 7h19"/>
                                    </svg>
                                </span>
                            @else
                                <span class="yk-home-how-card__image-fallback" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <rect x="9" y="13" width="46" height="34" rx="4"/>
                                        <path d="M20 54h24m-12-7v7M17 23h30M20 31h12m-12 7h19"/>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
