@php
    $title = (string) ($expert['title'] ?? '');
    $url = (string) ($expert['url'] ?? '#');
    $subtitle = trim((string) ($expert['subtitle'] ?? ''));
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
    $cta = 'View Profile';
    $bookCta = 'Book';
@endphp
<article class="yk-row yk-row--expert">
    <a class="yk-row__photo" href="{{ $url }}" tabindex="-1" aria-hidden="true">
        @if (!empty($image))
            <img src="{{ $image }}" alt="" width="48" height="48" loading="lazy" decoding="async" onerror="this.hidden=true; if(this.nextElementSibling){this.nextElementSibling.hidden=false;}">
            <span hidden>{{ $initials }}</span>
        @else
            <span>{{ $initials }}</span>
        @endif
    </a>
    <div class="yk-row__body">
        <a class="yk-row__title" href="{{ $url }}">{{ $title }}</a>
        @if ($subtitle !== '')
            <p class="yk-row__sub">{{ \Illuminate\Support\Str::limit($subtitle, 42) }}</p>
        @endif
        @if ($rating || $experience !== '')
            <p class="yk-row__meta">
                @if ($rating)
                    <svg class="yk-row__star" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5 9.9 5.7l4.6.7-3.3 3.2.8 4.6L8 12.2 3.9 14.2l.8-4.6L1.4 6.4l4.6-.7L8 1.5Z"/></svg>
                    <span class="yk-row__meta-strong">{{ $rating }}</span>
                    @if ($reviewCount > 0)
                        <span>({{ $reviewCount }})</span>
                    @endif
                @endif
                @if ($rating && $experience !== '')
                    <span aria-hidden="true">·</span>
                @endif
                @if ($experience !== '')
                    <span>{{ $experience }}</span>
                @endif
            </p>
        @endif
        @if ($priceLabel !== '')
            <p class="yk-row__price">
                {{ $priceLabel }}@if ($priceSuffix !== '') {{ $priceSuffix }}@endif
            </p>
        @endif
    </div>
    <div class="yk-row__actions">
        <a class="yk-row__action yk-row__action--ghost" href="{{ $url }}">{{ $cta }}</a>
        <a class="yk-row__action" href="{{ $url }}">{{ $bookCta }}</a>
    </div>
</article>
