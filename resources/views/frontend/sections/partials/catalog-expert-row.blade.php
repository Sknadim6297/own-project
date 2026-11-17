@php
    $title = (string) ($expert['title'] ?? '');
    $url = (string) ($expert['url'] ?? '#');
    $subtitle = trim((string) ($expert['subtitle'] ?? ''));
    $qualification = trim((string) ($expert['qualification'] ?? ''));
    $image = $expert['image'] ?? null;
    $initials = (string) ($expert['initials'] ?? '?');
    $rating = $expert['rating'] ?? null;
    $reviewCount = (int) ($expert['review_count'] ?? 0);
    $experience = trim((string) ($expert['experience'] ?? ''));
    $priceLabel = trim((string) ($expert['price_label'] ?? ''));
    $priceSuffix = trim((string) ($expert['price_suffix'] ?? ''));
    if ($priceSuffix === '/session') {
        $priceSuffix = '/ session';
    }
    $chips = collect($expert['chips'] ?? [])->filter()->values();
    $chipExtra = (int) ($expert['chip_extra'] ?? 0);
    $tones = ['plum', 'magenta', 'lilac', 'copper', 'charcoal', 'berry'];
    $tone = $tones[(int) ($loop->index ?? 0) % count($tones)];
    $category = $chips->first() ?: $subtitle;
    $cta = 'View Profile';
    $bookCta = 'Book';
@endphp
<article class="yk-xcard yk-xcard--{{ $tone }}" style="--yk-xcard-delay: {{ min(($loop->index ?? 0) * 55, 480) }}ms">
    <span class="yk-xcard__shine" aria-hidden="true"></span>
    <header class="yk-xcard__head">
        @if ($category !== '')
            <span class="yk-xcard__badge">{{ \Illuminate\Support\Str::limit($category, 22) }}</span>
        @endif
        <a class="yk-xcard__avatar" href="{{ $url }}" tabindex="-1" aria-hidden="true">
            @if (!empty($image))
                <img src="{{ $image }}" alt="" width="72" height="72" loading="lazy" decoding="async" onerror="this.hidden=true; if(this.nextElementSibling){this.nextElementSibling.hidden=false;}">
                <span class="yk-xcard__initials" hidden>{{ $initials }}</span>
            @else
                <span class="yk-xcard__initials">{{ $initials }}</span>
            @endif
        </a>
        <div class="yk-xcard__identity">
            <h2 class="yk-xcard__name">
                <a href="{{ $url }}">{{ $title }}</a>
            </h2>
            @if ($qualification !== '')
                <p class="yk-xcard__cred">{{ \Illuminate\Support\Str::limit($qualification, 48) }}</p>
            @elseif ($subtitle !== '' && $subtitle !== $category)
                <p class="yk-xcard__cred">{{ \Illuminate\Support\Str::limit($subtitle, 48) }}</p>
            @endif
        </div>
    </header>

    <div class="yk-xcard__body">
        @if ($chips->isNotEmpty())
            <ul class="yk-xcard__chips">
                @foreach ($chips->take(3) as $chip)
                    <li style="--yk-chip-i: {{ $loop->index }}">{{ $chip }}</li>
                @endforeach
                @if ($chipExtra > 0 || $chips->count() > 3)
                    <li class="yk-xcard__chip-more" style="--yk-chip-i: 3">+{{ max($chipExtra, $chips->count() - 3) }}</li>
                @endif
            </ul>
        @endif

        <div class="yk-xcard__meta">
            @if ($rating)
                <span class="yk-xcard__rating">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5 9.9 5.7l4.6.7-3.3 3.2.8 4.6L8 12.2 3.9 14.2l.8-4.6L1.4 6.4l4.6-.7L8 1.5Z"/></svg>
                    {{ $rating }}
                    @if ($reviewCount > 0)
                        <span>({{ $reviewCount }})</span>
                    @endif
                </span>
            @endif
            @if ($experience !== '')
                <span class="yk-xcard__exp">
                    <span class="yk-xcard__exp-label">Experience</span>
                    <span class="yk-xcard__exp-value">{{ $experience }}</span>
                </span>
            @endif
        </div>

        @if ($priceLabel !== '')
            <p class="yk-xcard__price">
                {{ $priceLabel }}@if ($priceSuffix !== '') <small>{{ $priceSuffix }}</small>@endif
            </p>
        @endif

        <div class="yk-xcard__actions">
            <a class="yk-xcard__btn yk-xcard__btn--ghost" href="{{ $url }}">{{ $cta }}</a>
            <a class="yk-xcard__btn yk-xcard__btn--solid" href="{{ $url }}"><span>{{ $bookCta }}</span></a>
        </div>
    </div>
</article>
