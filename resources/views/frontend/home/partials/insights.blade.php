@php
    $brand = config('yuktione.name', 'YuktiOne');
    $insightCards = [
        [
            'badge' => 'Mood Check-in',
            'title' => 'Understand your emotions',
            'text' => 'A short private check-in to notice how you feel today.',
            'tone' => 'blush',
            'accent' => 'pink',
            'emoji' => '😊',
            'cta' => 'Start Check-in',
            'url' => route('tools.psychology.mood'),
        ],
        [
            'badge' => 'Career Interest',
            'title' => 'Discover what drives you',
            'text' => 'Map your interests and explore career directions that fit.',
            'tone' => 'mint',
            'accent' => 'teal',
            'emoji' => '🎯',
            'cta' => 'Open Assessment',
            'url' => route('tools.career-interest'),
        ],
        [
            'badge' => 'Experts',
            'title' => 'Guidance from professionals',
            'text' => 'Browse verified experts and book a consultation that fits your goals.',
            'tone' => 'lavender',
            'accent' => 'purple',
            'emoji' => '🌟',
            'cta' => 'Explore Experts',
            'url' => route('professionals'),
        ],
        [
            'badge' => 'Journal',
            'title' => 'Fresh perspectives',
            'text' => 'Ideas and practical guidance from the '.$brand.' community.',
            'tone' => 'cream',
            'accent' => 'orange',
            'emoji' => '✨',
            'cta' => 'Read the Blog',
            'url' => route('blog.index'),
        ],
    ];
@endphp

<section class="yk-insight" aria-labelledby="yk-insight-title">
    <div class="yk-insight__inner">
        <header class="yk-insight__head">
            <div class="yk-insight__copy">
                <p class="yk-insight__eyebrow">Today's insight</p>
                <h2 class="yk-insight__title" id="yk-insight-title">
                    <span class="yk-insight__heading-navy">Fresh perspectives for</span>
                    <span class="yk-insight__heading-lavender">a brighter you.</span>
                </h2>
                <p class="yk-insight__lead">Explore tools, experts and articles to guide your day.</p>
            </div>
            <svg class="yk-insight__leaves" viewBox="0 0 180 150" aria-hidden="true" focusable="false">
                <path d="M92 146c10-48 26-86 52-122" fill="none" stroke="#7d9a68" stroke-width="2.2" stroke-linecap="round"></path>
                <path d="M136 16c18 16 18 40 4 58-22-6-38-30-34-54 10-2 20-4 30-4Z" fill="#b7a4e4"></path>
                <path d="M126 56c24 4 42 22 46 46-28 2-46-12-58-30 2-6 6-12 12-16Z" fill="#8aaf74"></path>
                <path d="M116 98c20 10 28 32 20 54-24-2-40-20-42-42 6-6 14-10 22-12Z" fill="#9d81db" opacity=".8"></path>
                <path d="M104 128c14 8 16 24 6 40-18-6-28-22-22-38 4-2 10-2 16-2Z" fill="#7ea36a"></path>
            </svg>
        </header>

        <ul class="yk-insight__grid">
            @foreach ($insightCards as $card)
                <li>
                    <article class="yk-insight__card yk-insight__card--{{ $card['tone'] }}">
                        <div class="yk-insight__body">
                            <p class="yk-insight__badge yk-insight__badge--{{ $card['accent'] }}">{{ $card['badge'] }}</p>
                            <h3 class="yk-insight__name">{{ $card['title'] }}</h3>
                            <p class="yk-insight__text">{{ $card['text'] }}</p>
                            <a class="yk-insight__cta yk-insight__cta--{{ $card['accent'] }}" href="{{ $card['url'] }}">
                                {{ $card['cta'] }} <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <div class="yk-insight__art" aria-hidden="true">
                            <span class="yk-insight__emoji">{{ $card['emoji'] }}</span>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    </div>
</section>
