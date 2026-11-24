@php
    $compact = ! empty($compact);
    $keywords = trim((string) ($card['keywords'] ?? ''));
    $keywordList = $keywords !== ''
        ? array_values(array_filter(array_map('trim', explode(',', $keywords))))
        : [];
@endphp
<div class="yk-tarot__interp">
    <p class="yk-tarot__position">{{ $card['position_label'] ?? '' }}</p>
    <h2>{{ $card['card_name'] ?? 'Card' }} · {{ $card['orientation_label'] ?? 'Upright' }}</h2>

    @if ($keywordList !== [])
        <ul class="yk-tarot__keywords" aria-label="Keywords">
            @foreach ($keywordList as $keyword)
                <li>{{ $keyword }}</li>
            @endforeach
        </ul>
    @endif

    @if (! empty($card['core_meaning']))
        <div class="yk-tarot__interp-block">
            @unless ($compact)
                <h3>Card Meaning</h3>
            @endunless
            <p class="yk-tarot__meaning">{{ $card['core_meaning'] }}</p>
        </div>
    @endif

    @unless ($compact)
        @if (! empty($card['insight']))
            <div class="yk-tarot__interp-block">
                <h3>Meaning in Your Reading</h3>
                <p class="yk-tarot__insight">{{ $card['insight'] }}</p>
            </div>
        @endif

        @if (! empty($card['reflection_question']))
            <div class="yk-tarot__interp-block">
                <h3>Reflection Question</h3>
                <p class="yk-tarot__reflect">{{ $card['reflection_question'] }}</p>
            </div>
        @endif

        @if (! empty($card['suggested_action']))
            <div class="yk-tarot__interp-block">
                <h3>Suggested Action</h3>
                <p class="yk-tarot__action">{{ $card['suggested_action'] }}</p>
            </div>
        @endif
    @endunless
</div>
