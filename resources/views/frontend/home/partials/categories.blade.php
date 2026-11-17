@php
    $categoryCards = [
        ['label' => 'Career', 'key' => 'career-business', 'tone' => 'blue', 'img' => 'cat-lotus.jpg', 'blurb' => 'Clarity for work & growth'],
        ['label' => 'Mental Wellbeing', 'key' => 'mental-health', 'tone' => 'violet', 'img' => 'cat-stones.jpg', 'blurb' => 'Calm mind, steady support'],
        ['label' => 'Nutrition & Fitness', 'key' => 'nutrition-fitness', 'tone' => 'green', 'img' => 'cat-fruit.jpg', 'blurb' => 'Eat well, feel stronger'],
        ['label' => 'Spirituality', 'key' => 'spirituality', 'tone' => 'violet', 'img' => 'cat-meditate.jpg', 'blurb' => 'Find quiet & meaning'],
        ['label' => 'Relationships', 'key' => 'relationships', 'tone' => 'magenta', 'img' => 'cat-heart.jpg', 'blurb' => 'Connect with care'],
        ['label' => 'Finance', 'key' => 'money-finance', 'tone' => 'magenta', 'img' => 'cat-growth.jpg', 'blurb' => 'Grow money with clarity'],
        ['label' => 'Lifestyle & More', 'key' => 'lifestyle', 'tone' => 'amber', 'img' => 'cat-lifestyle.jpg', 'blurb' => 'Everyday better living'],
        ['label' => 'Astrology & Tarot', 'key' => 'astrology-tarot', 'tone' => 'violet', 'img' => 'cat-moon.jpg', 'blurb' => 'Insight for your path'],
        ['label' => 'Health & Wellness', 'key' => 'health-wellness', 'tone' => 'green', 'img' => 'cat-health.jpg', 'blurb' => 'Body care that lasts'],
        ['label' => 'Parenting', 'key' => 'parenting', 'tone' => 'amber', 'img' => 'cat-parent.jpg', 'blurb' => 'Support for family life'],
        ['label' => 'Creativity', 'key' => 'creativity', 'tone' => 'blue', 'img' => 'cat-create.jpg', 'blurb' => 'Unlock your ideas'],
    ];
@endphp

<section class="yk-home-cats yk-home-cats--rich" aria-labelledby="yk-home-cats-title" data-categories-carousel>
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
