@php
    // Keep homepage cards aligned with ExploreCatalog category keys / Experts filters.
    $categoryCards = [
        [
            'label' => 'Astrology & Tarot',
            'tone' => 'lavender',
            'icon' => 'sun-moon',
            'url' => route('professionals', ['category' => 'astrology-tarot']),
        ],
        [
            'label' => 'Mental Health & Mindset',
            'tone' => 'mint',
            'icon' => 'lotus',
            'url' => route('professionals', ['category' => 'mental-health']),
        ],
        [
            'label' => 'Relationships',
            'tone' => 'blush',
            'icon' => 'heart',
            'url' => route('professionals', ['category' => 'relationships']),
        ],
        [
            'label' => 'Career & Business',
            'tone' => 'lavender',
            'icon' => 'chart',
            'url' => route('professionals', ['category' => 'career-business']),
        ],
        [
            'label' => 'Money & Finance',
            'tone' => 'cream',
            'icon' => 'coins',
            'url' => route('professionals', ['category' => 'money-finance']),
        ],
        [
            'label' => 'Health & Wellness',
            'tone' => 'mint',
            'icon' => 'leaf',
            'url' => route('professionals', ['category' => 'health-wellness']),
        ],
        [
            'label' => 'Parenting',
            'tone' => 'blush',
            'icon' => 'family',
            'url' => route('professionals', ['category' => 'parenting']),
        ],
        [
            'label' => 'Lifestyle',
            'tone' => 'mint',
            'icon' => 'coffee',
            'url' => route('professionals', ['category' => 'lifestyle']),
        ],
        [
            'label' => 'Spirituality',
            'tone' => 'lavender',
            'icon' => 'lotus',
            'url' => route('professionals', ['category' => 'spirituality']),
        ],
        [
            'label' => 'Creativity',
            'tone' => 'blue',
            'icon' => 'bulb',
            'url' => route('professionals', ['category' => 'creativity']),
        ],
        [
            'label' => 'Nutrition & Fitness',
            'tone' => 'mint',
            'icon' => 'leaf',
            'url' => route('professionals', ['category' => 'nutrition-fitness']),
        ],
    ];
@endphp

<section class="yk-categories" aria-labelledby="yk-categories-title">
    <h2 class="visually-hidden" id="yk-categories-title">Explore categories</h2>
    <ul class="yk-categories__grid">
        @foreach ($categoryCards as $card)
            <li>
                @if (!empty($card['url']))
                    <a class="yk-categories__card yk-categories__card--{{ $card['tone'] }}" href="{{ $card['url'] }}">
                @else
                    <div class="yk-categories__card yk-categories__card--{{ $card['tone'] }}">
                @endif
                    <svg class="yk-categories__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        @switch($card['icon'])
                            @case('sun-moon')
                                <circle cx="9" cy="12" r="3.1"></circle>
                                <path d="M9 5.4v1.3M9 17.3v1.3M3.8 12H5.1M12.9 12h1.3M5.2 8.2l.9.9M11.9 14.9l.9.9M5.2 15.8l.9-.9M11.9 9.1l.9-.9"></path>
                                <path d="M16.2 8.4a4.2 4.2 0 1 0 0 7.2 3.3 3.3 0 0 1 0-7.2Z"></path>
                                @break
                            @case('lotus')
                                <path d="M12 19.2c0-3.4 1.8-5.3 4-6.2C14.2 11.6 12 8.6 12 4.8c0 3.8-2.2 6.8-4 8.2 2.2.9 4 2.8 4 6.2Z"></path>
                                <path d="M12 19.2c-2.8-.3-5.2-2.2-6-4.8 2 .2 3.6.8 4.6 1.8"></path>
                                <path d="M12 19.2c2.8-.3 5.2-2.2 6-4.8-2 .2-3.6.8-4.6 1.8"></path>
                                @break
                            @case('heart')
                                <path d="M12 18.6s-5.6-3.4-5.6-7.3A3.1 3.1 0 0 1 12 8.6a3.1 3.1 0 0 1 5.6 2.7c0 3.9-5.6 7.3-5.6 7.3Z"></path>
                                @break
                            @case('chart')
                                <path d="M4.5 16.8 9.2 12l3 2.4L19.5 7"></path>
                                <path d="M14.6 7H19.5v4.8"></path>
                                @break
                            @case('coins')
                                <ellipse cx="12" cy="7.2" rx="6.4" ry="2.3"></ellipse>
                                <path d="M5.6 7.2v3.8c0 1.3 2.9 2.3 6.4 2.3s6.4-1 6.4-2.3V7.2"></path>
                                <path d="M5.6 11v3.8c0 1.3 2.9 2.3 6.4 2.3s6.4-1 6.4-2.3V11"></path>
                                @break
                            @case('leaf')
                                <path d="M5.2 18.4C5.2 11.2 10.6 5.6 18.6 5.6c0 7.4-5.4 12.8-13.4 12.8Z"></path>
                                <path d="M8.4 15.4c1.6-2.8 4.2-5 7.4-6.2"></path>
                                @break
                            @case('family')
                                <circle cx="9" cy="8.2" r="2.1"></circle>
                                <circle cx="15.8" cy="9.1" r="1.7"></circle>
                                <path d="M4.8 17.8c.4-2.3 2.1-3.4 4.2-3.4s3.8 1.1 4.2 3.4"></path>
                                <path d="M13.4 14.6c1.3-.4 2.4-.3 3.5.5 1 .8 1.5 1.8 1.7 3"></path>
                                @break
                            @case('coffee')
                                <path d="M5.2 8.2h9.6v4.6a3.5 3.5 0 0 1-3.5 3.5H8.7a3.5 3.5 0 0 1-3.5-3.5V8.2Z"></path>
                                <path d="M14.8 9.3h1.3a2 2 0 1 1 0 4h-1.3"></path>
                                <path d="M8 5.2c.3.6.3 1.1 0 1.8M10.6 5.2c.3.6.3 1.1 0 1.8"></path>
                                @break
                            @case('bulb')
                                <path d="M9.2 18h5.6M10 20.4h4"></path>
                                <path d="M8.2 13.8a4.6 4.6 0 1 1 7.6 0c-.6.7-1.1 1.4-1.1 2.4H9.3c0-1-.5-1.7-1.1-2.4Z"></path>
                                @break
                        @endswitch
                    </svg>
                    <span>{{ $card['label'] }}</span>
                @if (!empty($card['url']))
                    </a>
                @else
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</section>
