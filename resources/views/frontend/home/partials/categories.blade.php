@php
    $categoryCards = [];
    foreach (array_keys(\App\Services\ExploreCatalog::CATEGORIES) as $key) {
        $categoryCards[] = [
            'label' => \App\Services\CategoryHub::DISPLAY_LABELS[$key]
                ?? (\App\Services\ExploreCatalog::CATEGORIES[$key]['label'] ?? $key),
            'key' => $key,
            'tone' => \App\Services\CategoryHub::PHOTO_TONES[$key] ?? 'magenta',
            'img' => \App\Services\CategoryHub::IMAGES[$key] ?? 'cat-lotus.jpg',
            'blurb' => \App\Services\CategoryHub::SHORT_BLURBS[$key] ?? '',
        ];
    }
@endphp

{{-- Task 52: homepage Solutions card rail (Explore by Need categories). Horizontal scroll must reach every card. --}}
<section
    class="yk-home-cats yk-home-cats--rich"
    aria-labelledby="yk-home-cats-title"
    data-categories-carousel
    data-yk-solutions-rail
>
    <div class="yk-home-cats__shell">
        <div class="yk-home-cats__head">
            <h2 id="yk-home-cats-title">Explore <span>by Need</span></h2>
            <div class="yk-home-cats__controls">
                <button class="yk-home-cats__nav" type="button" data-categories-prev aria-label="Previous categories" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 6 9 12l6 6"></path></svg>
                </button>
                <button class="yk-home-cats__nav" type="button" data-categories-next aria-label="Next categories">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"></path></svg>
                </button>
                <a class="yk-home-cats__more" href="{{ route('categories.index') }}">View more</a>
            </div>
        </div>

        <div class="yk-home-cats__viewport">
            <ul class="yk-home-cats__track" data-categories-track>
                @foreach ($categoryCards as $card)
                    <li>
                        <a
                            class="yk-home-cats__card yk-home-cats__card--photo yk-home-cats__card--{{ $card['tone'] }}"
                            href="{{ route('categories.show', $card['key']) }}"
                        >
                            <span class="yk-home-cats__media">
                                <img
                                    src="{{ asset('frontend/assets/img/home-cats/' . $card['img']) }}"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    width="320"
                                    height="320"
                                >
                            </span>
                            <span class="yk-home-cats__label">{{ $card['label'] }}</span>
                            <span class="yk-home-cats__blurb">{{ $card['blurb'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
