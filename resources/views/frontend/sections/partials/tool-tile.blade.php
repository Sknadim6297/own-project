@php
    $label = (string) ($label ?? $tool['name'] ?? '');
    $url = (string) ($url ?? $tool['url'] ?? '#');
    $icon = (string) ($icon ?? $tool['icon'] ?? 'leaf');
@endphp
<a class="yk-tool-tile" href="{{ $url }}">
    <span class="yk-tool-tile__icon" aria-hidden="true">
        @switch($icon)
            @case('mood')
                <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8.25"/><path d="M8.6 10.1h.02M15.4 10.1h.02"/><path d="M8.4 14c1.15 1.45 2.45 2.15 3.6 2.15S14.45 15.45 15.6 14"/></svg>
                @break
            @case('journal')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M7.5 4.75h9.5A1.75 1.75 0 0 1 18.75 6.5v13.1l-3.1-1.35-3.15 1.45-3.15-1.45-3.1 1.35V6.5A1.75 1.75 0 0 1 7.5 4.75Z"/><path d="M10 9.25h5.5M10 12.5h3.75"/><path d="M8.15 4.75v14.85"/></svg>
                @break
            @case('goal')
                <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8.25"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1.35"/><path d="m14.7 9.3 3.55-3.55M18.25 5.75v2.55M18.25 5.75h-2.55"/></svg>
                @break
            @case('bmi')
                <svg viewBox="0 0 24 24" focusable="false"><rect x="5.5" y="3.75" width="13" height="16.5" rx="2.2"/><path d="M8.25 7.5h2.4M13.35 7.5h2.4M8.25 11h2.4M13.35 11h2.4M8.25 14.5h2.4M13.35 14.5h2.4"/><path d="M9.2 18h5.6"/></svg>
                @break
            @case('finance')
            @case('coins')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 4.6 4.8 8.1v.2L12 11.7l7.2-3.4v-.2L12 4.6Z"/><path d="M4.8 12.05 12 15.55l7.2-3.5"/><path d="M4.8 15.85 12 19.4l7.2-3.55"/></svg>
                @break
            @case('parenting')
            @case('family')
                <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="7.2" r="2.35"/><circle cx="7.1" cy="9.1" r="1.85"/><circle cx="16.9" cy="9.1" r="1.85"/><path d="M8.8 18.6c.45-3.05 1.95-4.55 3.2-4.55s2.75 1.5 3.2 4.55"/><path d="M4.85 18.6c.35-2.35 1.45-3.45 2.45-3.45"/><path d="M19.15 18.6c-.35-2.35-1.45-3.45-2.45-3.45"/></svg>
                @break
            @case('lotus')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 18.4c-2.2-1.7-3.55-3.55-3.55-5.55A3.55 3.55 0 0 1 12 9.3a3.55 3.55 0 0 1 3.55 3.55c0 2-1.35 3.85-3.55 5.55Z"/><path d="M7.2 15.1c-1.55-.35-2.7-1.45-2.95-3.05 1.7-.15 3.15.55 4.15 1.85"/><path d="M16.8 15.1c1.55-.35 2.7-1.45 2.95-3.05-1.7-.15-3.15.55-4.15 1.85"/><path d="M8.35 9.7C7.4 8.35 7.15 6.7 7.85 5.35c1.45.85 2.35 2.2 2.55 3.85"/><path d="M15.65 9.7c.95-1.35 1.2-3 .5-4.35-1.45.85-2.35 2.2-2.55 3.85"/><path d="M12 19.25v.01"/></svg>
                @break
            @case('gratitude')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 19.2s-6.6-4.1-6.6-8.45a3.75 3.75 0 0 1 6.6-2.45 3.75 3.75 0 0 1 6.6 2.45c0 4.35-6.6 8.45-6.6 8.45Z"/></svg>
                @break
            @case('career')
            @case('skills')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3.6 14.2 8l4.8.7-3.5 3.4.8 4.85L12 14.7 7.7 16.95l.8-4.85L5 8.7 9.8 8 12 3.6Z"/></svg>
                @break
            @case('water')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 19.5c3.55-2.85 5.35-5.55 5.35-8.2A5.35 5.35 0 0 0 6.65 11.3c0 2.65 1.8 5.35 5.35 8.2Z"/><path d="M10.2 11.1c.7-1.35 1.6-2.25 2.7-2.7"/></svg>
                @break
            @case('weight')
            @case('nutrition')
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 20.25c3.7-2.95 5.6-5.7 5.6-8.55A5.6 5.6 0 0 0 6.4 11.7c0 2.85 1.9 5.6 5.6 8.55Z"/><path d="M10.1 11.35c.75-1.4 1.7-2.35 2.85-2.85"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24" focusable="false"><path d="M5.5 18.5C13.8 18.5 18.5 13 18.5 5.5 11.2 5.5 5.5 10.5 5.5 18.5Z"/><path d="M8.2 15.6c2.7-.9 5.4-3.5 6.3-7.1"/></svg>
        @endswitch
    </span>
    <span class="yk-tool-tile__label">{{ $label }}</span>
</a>
