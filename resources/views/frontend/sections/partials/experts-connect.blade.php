@if (is_array($discovery ?? null) && ! empty($discovery['url']))
@php
    $heading = $heading ?? 'Talk with an expert';
    $copy = $copy ?? 'Browse verified YuktiOne professionals when you want guided support.';
    $cta = $cta ?? ($discovery['label'] ?? 'Browse Experts');
    $url = $discovery['url'];
    $headingId = $headingId ?? 'experts-connect-heading';
@endphp
<section class="yk-psych-connect" aria-labelledby="{{ $headingId }}">
    <div class="yk-psych-connect__copy">
        <h2 id="{{ $headingId }}">{{ $heading }}</h2>
        <p>{{ $copy }}</p>
    </div>
    <a class="yk-psych-connect__cta" href="{{ $url }}">
        <span>{{ $cta }}</span>
        <span class="yk-psych-connect__arrow" aria-hidden="true">→</span>
    </a>
</section>
@endif
