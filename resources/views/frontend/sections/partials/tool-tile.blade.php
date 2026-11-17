@php
    $label = (string) ($label ?? $tool['name'] ?? '');
    $url = (string) ($url ?? $tool['url'] ?? '#');
    $icon = (string) ($icon ?? $tool['icon'] ?? 'leaf');
@endphp
<a class="yk-tool-tile" href="{{ $url }}">
    <span class="yk-tool-tile__icon" aria-hidden="true">
        @include('frontend.sections.partials.tool-icon-svg', ['icon' => $icon])
    </span>
    <span class="yk-tool-tile__label">{{ $label }}</span>
</a>
