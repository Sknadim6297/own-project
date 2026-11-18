@php
    $label = (string) ($label ?? $tool['name'] ?? '');
    $url = (string) ($url ?? $tool['url'] ?? '#');
    $icon = (string) ($icon ?? $tool['icon'] ?? 'leaf');
    $slug = (string) ($slug ?? $tool['slug'] ?? '');
    // Same 3D illustration the /tools catalogue card uses for this tool.
    $illustration = \App\Support\Tools\ToolIllustration::for($slug, $icon);
@endphp
<a class="yk-tool-tile" href="{{ $url }}">
    <span class="yk-tool-tile__icon" aria-hidden="true">
        @if ($illustration)
            <img
                class="yk-tool-tile__illust"
                src="{{ asset(\App\Support\Tools\ToolIllustration::DIRECTORY.$illustration) }}"
                alt=""
                width="180"
                height="140"
                loading="lazy"
                decoding="async"
            >
        @else
            @include('frontend.sections.partials.tool-icon-svg', ['icon' => $icon])
        @endif
    </span>
    <span class="yk-tool-tile__label">{{ $label }}</span>
</a>
