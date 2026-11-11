{{--
  Phase 4A reference partial — opt-in primitives for future tool redesigns.
  Include only when a redesign adopts data-yk-tools-ds. Not used by legacy tool shells yet.
--}}
@props([
    'title' => null,
    'eyebrow' => null,
    'lead' => null,
])

<div class="yk-tools-ds" data-yk-tools-ds>
    <div class="yk-ds-shell">
        @if ($eyebrow)
            <p class="yk-ds-eyebrow">{{ $eyebrow }}</p>
        @endif
        @if ($title)
            <h1 class="yk-ds-title">{{ $title }}</h1>
        @endif
        @if ($lead)
            <p class="yk-ds-lead">{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
