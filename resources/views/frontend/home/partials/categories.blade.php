@php
    $categoryCards = [
        ['label' => 'Mental Wellbeing', 'key' => 'mental-health', 'tone' => 'violet', 'icon' => 'brain'],
        ['label' => 'Career', 'key' => 'career-business', 'tone' => 'blue', 'icon' => 'clipboard'],
        ['label' => 'Nutrition & Fitness', 'key' => 'nutrition-fitness', 'tone' => 'green', 'icon' => 'fitness'],
        ['label' => 'Finance', 'key' => 'money-finance', 'tone' => 'magenta', 'icon' => 'wallet'],
        ['label' => 'Lifestyle & More', 'key' => 'lifestyle', 'tone' => 'amber', 'icon' => 'pin'],
        ['label' => 'Astrology & Tarot', 'key' => 'astrology-tarot', 'tone' => 'violet', 'icon' => 'moon'],
        ['label' => 'Relationships', 'key' => 'relationships', 'tone' => 'magenta', 'icon' => 'heart'],
        ['label' => 'Health & Wellness', 'key' => 'health-wellness', 'tone' => 'green', 'icon' => 'leaf'],
        ['label' => 'Parenting', 'key' => 'parenting', 'tone' => 'amber', 'icon' => 'family'],
        ['label' => 'Spirituality', 'key' => 'spirituality', 'tone' => 'violet', 'icon' => 'spark'],
        ['label' => 'Creativity', 'key' => 'creativity', 'tone' => 'blue', 'icon' => 'bulb'],
    ];
@endphp

<section class="yk-home-cats" aria-labelledby="yk-home-cats-title" data-categories-carousel>
    <div class="yk-home-cats__shell">
        <div class="yk-home-cats__head">
            <h2 id="yk-home-cats-title">Explore categories</h2>
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
                            class="yk-home-cats__card yk-home-cats__card--{{ $card['tone'] }}"
                            href="{{ route('categories.show', $card['key']) }}"
                        >
                            <span class="yk-home-cats__glow" aria-hidden="true"></span>
                            <svg class="yk-home-cats__icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                                @switch($card['icon'])
                                    @case('brain')
                                        <path d="M18.5 14.5c-3.2.4-5.5 3.2-5.5 6.5 0 2.1.9 3.9 2.4 5.1-.4 1-.6 2.1-.6 3.2 0 3.8 2.7 6.9 6.2 7.4"></path>
                                        <path d="M29.5 14.5c3.2.4 5.5 3.2 5.5 6.5 0 2.1-.9 3.9-2.4 5.1.4 1 .6 2.1.6 3.2 0 3.8-2.7 6.9-6.2 7.4"></path>
                                        <path d="M24 12.5c1.8-1.6 4.4-2 6.5-.9M24 12.5c-1.8-1.6-4.4-2-6.5-.9"></path>
                                        <path d="M20.5 22.5c1.1 1.4 2.3 2.1 3.5 2.1s2.4-.7 3.5-2.1"></path>
                                        <path d="M24 24.6v8.2"></path>
                                        @break
                                    @case('clipboard')
                                        <rect x="14" y="12" width="20" height="26" rx="3.5"></rect>
                                        <path d="M19 12.5v-1.2a5 5 0 0 1 10 0v1.2"></path>
                                        <path d="M19.5 22h9M19.5 27h9M19.5 32h6"></path>
                                        @break
                                    @case('fitness')
                                        <path d="M16 30c2.2-6.4 6.2-10.8 12.8-13.2"></path>
                                        <path d="M28.8 16.8c2.4 1.2 4.2 3.4 5.2 6.4"></path>
                                        <path d="M18.5 20.5c2.8-1.4 5.6-1.2 8.2.4"></path>
                                        <circle cx="17.2" cy="31.5" r="2.2"></circle>
                                        <path d="M28 34.5c3.5-1.2 6.2-3.8 7.5-7.2"></path>
                                        @break
                                    @case('wallet')
                                        <rect x="11" y="14" width="26" height="20" rx="4"></rect>
                                        <path d="M11 20h26"></path>
                                        <path d="M29 27.5h4.5a2.5 2.5 0 0 0 0-5H29"></path>
                                        <circle cx="31.5" cy="25" r="1.2"></circle>
                                        @break
                                    @case('pin')
                                        <path d="M24 36.5s-8.5-7.4-8.5-14.2a8.5 8.5 0 1 1 17 0c0 6.8-8.5 14.2-8.5 14.2Z"></path>
                                        <circle cx="24" cy="22" r="3.2"></circle>
                                        @break
                                    @case('moon')
                                        <path d="M28.5 14.8a9.5 9.5 0 1 0 4.7 16.8 11.2 11.2 0 1 1-4.7-16.8Z"></path>
                                        @break
                                    @case('heart')
                                        <path d="M24 36s-10.5-6.5-10.5-14A5.8 5.8 0 0 1 24 17.2 5.8 5.8 0 0 1 34.5 22C34.5 29.5 24 36 24 36Z"></path>
                                        @break
                                    @case('leaf')
                                        <path d="M14 34C14 20.5 23.2 12 36 12c0 12.2-8.8 22-22 22Z"></path>
                                        <path d="M18.5 29.5c2.8-4.6 7-8.2 12.2-10.2"></path>
                                        @break
                                    @case('family')
                                        <circle cx="19" cy="17" r="3.4"></circle>
                                        <circle cx="30.5" cy="18.5" r="2.8"></circle>
                                        <path d="M12.5 34c.8-4 3.6-6 6.8-6s6 2 6.8 6"></path>
                                        <path d="M26.5 28.5c2-.8 3.8-.6 5.5.8 1.6 1.3 2.4 3.1 2.7 5"></path>
                                        @break
                                    @case('spark')
                                        <path d="M24 12.5v6.2M24 29.3v6.2M12.5 24h6.2M29.3 24h6.2"></path>
                                        <path d="m16.8 16.8 4.2 4.2M27 27l4.2 4.2M31.2 16.8 27 21M21 27l-4.2 4.2"></path>
                                        @break
                                    @case('bulb')
                                        <path d="M20 35h8M21.2 38.5h5.6"></path>
                                        <path d="M18.5 27.5a7.8 7.8 0 1 1 11 0c-1 1.2-1.8 2.4-1.8 4H20.3c0-1.6-.8-2.8-1.8-4Z"></path>
                                        @break
                                @endswitch
                            </svg>
                            <span class="yk-home-cats__label">{{ $card['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
