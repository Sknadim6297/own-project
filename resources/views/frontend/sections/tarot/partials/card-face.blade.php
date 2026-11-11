@php
    $name = $card['card_name'] ?? 'Tarot card';
    $arcana = $card['arcana'] ?? 'minor';
    $suit = $card['suit'] ?? null;
    $isReversed = !empty($card['is_reversed']);
    $imageUrl = \App\Support\Tarot\TarotUi::imageUrl($card['image_path'] ?? null);
    $glyph = \App\Support\Tarot\TarotUi::suitGlyph($suit, $arcana);
    $suitLabel = \App\Support\Tarot\TarotUi::suitLabel($suit, $arcana);
    $size = $size ?? 'md';
@endphp
<figure class="yk-tarot__face yk-tarot__face--{{ $size }} @if ($isReversed) is-reversed @endif @if ($arcana === 'major') is-major @else is-minor is-{{ $suit ?: 'generic' }} @endif" aria-hidden="true">
    @if ($imageUrl)
        <img class="yk-tarot__face-img" src="{{ $imageUrl }}" alt="" loading="lazy" decoding="async">
    @else
        <div class="yk-tarot__face-fallback">
            <span class="yk-tarot__face-ornament" aria-hidden="true"></span>
            <span class="yk-tarot__face-glyph">{{ $glyph }}</span>
            <span class="yk-tarot__face-arcana">{{ $suitLabel }}</span>
            <span class="yk-tarot__face-name">{{ $name }}</span>
            @if ($isReversed)
                <span class="yk-tarot__face-orient">Reversed</span>
            @endif
        </div>
    @endif
</figure>
