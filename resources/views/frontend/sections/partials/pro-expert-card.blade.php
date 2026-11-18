@php
    $id = (int) ($expert['id'] ?? 0);
    $title = (string) ($expert['title'] ?? '');
    $url = (string) ($expert['url'] ?? '#');
    $image = $expert['image'] ?? null;
    $initials = (string) ($expert['initials'] ?? '?');
    $role = \Illuminate\Support\Str::limit(trim((string) ($expert['role'] ?? '')), 34);
    $topics = collect($expert['topics'] ?? [])->filter()->take(2);
    // Unique per result set when rendered from the directory; standalone fallback otherwise.
    $decor = ($decor ?? null) ?: \App\Support\ExpertDecoration::resolve(
        $expert['role'] ?? null,
        array_merge((array) ($expert['topics'] ?? []), (array) ($expert['chips'] ?? [])),
        $expert['icon'] ?? null
    );

    // "2–4 years" -> "2–4 yrs"
    $experienceLabel = trim((string) ($expert['experience'] ?? ''));
    $experienceLabel = $experienceLabel !== '' ? preg_replace('/\s*years?$/i', ' yrs', $experienceLabel) : '';

    $sessionCount = (int) ($expert['sessions'] ?? 0);
    $sessionLabel = null;
    if ($sessionCount >= 1000) {
        $sessionLabel = number_format($sessionCount / 1000, $sessionCount % 1000 === 0 ? 0 : 1).'k+ sessions';
    } elseif ($sessionCount > 0) {
        $sessionLabel = number_format($sessionCount).'+ sessions';
    }
    $metaParts = array_values(array_filter([$experienceLabel, $sessionLabel]));

    $priceLabel = trim((string) ($expert['price_label'] ?? ''));
    $priceSuffix = trim((string) ($expert['price_suffix'] ?? ''));
    $priceSuffix = $priceSuffix === '/session' ? '/ session' : $priceSuffix;

    $ratingValue = $expert['rating'] ?? null;
    $ratingCount = (int) ($expert['review_count'] ?? 0);
    if (! $ratingValue && config('yuktione.placeholder_ratings', true)) {
        // TEMPORARY placeholder (stable per expert). Disable via config/yuktione.php.
        $ratingValue = ['4.9', '4.8', '4.9', '4.7'][$id % 4];
        $ratingCount = 120 + (($id * 37) % 280);
    }
@endphp
<article class="yk-pcard" data-expert-id="{{ $id }}" data-tone="{{ $decor['tone'] }}" data-decor="{{ $decor['asset'] }}" style="--yk-pcard-delay: {{ min(($loop->index ?? 0) * 50, 400) }}ms">
    <span class="yk-pcard__wave" aria-hidden="true"></span>
    <img class="yk-pcard__mark" src="{{ asset('assets/experts/decorations/'.$decor['asset'].'.svg') }}" alt="" width="64" height="64" loading="lazy" decoding="async" aria-hidden="true">

    <button
        type="button"
        class="yk-pcard__fav"
        data-pro-fav
        data-expert-id="{{ $id }}"
        aria-pressed="false"
        aria-label="Save {{ $title }} to favourites"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.4s-7.6-4.7-7.6-10.1A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.6 2.7c0 5.4-7.6 10.1-7.6 10.1Z"/></svg>
    </button>

    <div class="yk-pcard__top">
        <a class="yk-pcard__photo" href="{{ $url }}" tabindex="-1" aria-hidden="true">
            @if (! empty($image))
                <img src="{{ $image }}" alt="" width="96" height="108" loading="lazy" decoding="async" onerror="this.hidden=true; if(this.nextElementSibling){this.nextElementSibling.hidden=false;}">
                <span class="yk-pcard__initials" hidden>{{ $initials }}</span>
            @else
                <span class="yk-pcard__initials">{{ $initials }}</span>
            @endif
        </a>

        <div class="yk-pcard__info">
            <h2 class="yk-pcard__name" title="{{ $title }}"><a href="{{ $url }}">{{ $title }}</a></h2>
            @if ($role !== '')
                <p class="yk-pcard__role" title="{{ $role }}">{{ $role }}</p>
            @endif
            @if ($metaParts !== [])
                <p class="yk-pcard__meta">{{ implode(' • ', $metaParts) }}</p>
            @endif
            @if ($ratingValue)
                <p class="yk-pcard__rating">
                    <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 1.5 9.9 5.7l4.6.7-3.3 3.2.8 4.6L8 12.2 3.9 14.2l.8-4.6L1.4 6.4l4.6-.7L8 1.5Z"/></svg>
                    <strong>{{ $ratingValue }}</strong>
                    @if ($ratingCount > 0)
                        <span>({{ number_format($ratingCount) }})</span>
                    @endif
                </p>
            @endif
            @if ($topics->isNotEmpty())
                <ul class="yk-pcard__chips">
                    @foreach ($topics as $chip)
                        <li title="{{ $chip }}">{{ $chip }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="yk-pcard__foot">
        @if ($priceLabel !== '')
            <p class="yk-pcard__price">
                @if ($priceSuffix !== '')
                    <span>From</span> <strong>{{ $priceLabel }}</strong> <small>{{ $priceSuffix }}</small>
                @else
                    <strong>{{ $priceLabel }}</strong>
                @endif
            </p>
        @endif
        <div class="yk-pcard__actions">
            <a class="yk-pcard__btn yk-pcard__btn--ghost" href="{{ $url }}">View Profile</a>
            <a class="yk-pcard__btn yk-pcard__btn--solid" href="{{ $url }}">Book a Session <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>
