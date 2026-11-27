@php
    use App\Services\CategoryCatalog;
    use App\Services\CategoryHub;

    $imageFallbacks = [
        'mental-wellness' => 'cat-stones.jpg',
        'career-counselling' => 'cat-lotus.jpg',
        'nutrition' => 'cat-fruit.jpg',
        'fitness' => 'cat-health.jpg',
        'astrology' => 'cat-moon.jpg',
        'tarot' => 'cat-moon.jpg',
        'business-consulting' => 'cat-growth.jpg',
        'tax-matters' => 'cat-growth.jpg',
        'investments' => 'cat-growth.jpg',
    ];
    $toneFallbacks = [
        'mental-wellness' => 'violet',
        'career-counselling' => 'blue',
        'nutrition' => 'green',
        'fitness' => 'green',
        'astrology' => 'violet',
        'tarot' => 'violet',
        'business-consulting' => 'blue',
        'tax-matters' => 'magenta',
        'investments' => 'magenta',
    ];

    $source = isset($homepageCategories) && $homepageCategories instanceof \Illuminate\Support\Collection
        ? $homepageCategories
        : app(CategoryCatalog::class)->forHomepage();

    $categoryCards = [];
    foreach ($source as $category) {
        $slug = $category->slug;
        $categoryCards[] = [
            'label' => $category->name,
            'key' => $slug,
            'tone' => $toneFallbacks[$slug] ?? 'magenta',
            'img' => $category->image
                ? null
                : ($imageFallbacks[$slug] ?? 'cat-lotus.jpg'),
            'image_url' => $category->image ? asset('storage/'.$category->image) : null,
            'blurb' => $category->short_description
                ?: (CategoryHub::SHORT_BLURBS[$slug] ?? 'Explore trusted guidance'),
        ];
    }
@endphp

@php
    use App\Support\Homepage\TopicLayout;

    $topicLayout = $topicLayout ?? TopicLayout::resolve();
    $topicLayoutClass = TopicLayout::solutionsClasses($topicLayout);
@endphp

{{-- Homepage Explore by Need — database-driven Categories (services). --}}
<section
    class="yk-home-cats yk-home-cats--rich{{ $topicLayoutClass !== '' ? ' '.$topicLayoutClass : '' }}"
    aria-labelledby="yk-home-cats-title"
    data-categories-carousel
    data-yk-solutions-rail
    data-topic-layout="{{ $topicLayout }}"
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
            @if(count($categoryCards) === 0)
                <p class="text-muted px-3 py-4">Categories will appear here once published for the homepage.</p>
            @else
                <ul class="yk-home-cats__track" data-categories-track>
                    @foreach ($categoryCards as $card)
                        <li>
                            <a
                                class="yk-home-cats__card yk-home-cats__card--photo yk-home-cats__card--{{ $card['tone'] }}"
                                href="{{ route('categories.show', $card['key']) }}"
                            >
                                <span class="yk-home-cats__media">
                                    <img
                                        src="{{ $card['image_url'] ?? asset('frontend/assets/img/home-cats/' . $card['img']) }}"
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
            @endif
        </div>
    </div>
</section>
