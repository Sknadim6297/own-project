@php
    $heading = $heading ?? 'Connect with a Psychologist';
    $copy = $copy ?? 'A supportive conversation can help you explore your feelings and thoughts.';
    $cta = $cta ?? 'Connect with a Psychologist';
    $url = $url ?? ($psychologistsUrl ?? route('professionals', ['category' => 'mental-health']));
@endphp

<section class="yk-psych-connect" aria-labelledby="{{ $headingId ?? 'psych-connect-heading' }}">
    <div class="yk-psych-connect__copy">
        <h2 id="{{ $headingId ?? 'psych-connect-heading' }}">{{ $heading }}</h2>
        <p>{{ $copy }}</p>
    </div>
    <a class="yk-psych-connect__cta" href="{{ $url }}">
        <span>{{ $cta }}</span>
        <span class="yk-psych-connect__arrow" aria-hidden="true">→</span>
    </a>
</section>
