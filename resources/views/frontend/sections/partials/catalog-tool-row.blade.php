@php
    $name = (string) ($tool['name'] ?? '');
    $url = (string) ($tool['url'] ?? '#');
    $icon = (string) ($tool['icon'] ?? 'leaf');
    $desc = trim((string) ($tool['description'] ?? ''));
    if ($desc !== '') {
        $desc = \Illuminate\Support\Str::limit($desc, 56, '…');
    }
    $tone = match ($icon) {
        'mood' => 'mood',
        'journal' => 'journal',
        'gratitude' => 'gratitude',
        'career' => 'career',
        'skills' => 'skills',
        'bmi', 'water', 'weight', 'nutrition' => $icon,
        'coins' => 'coins',
        default => 'leaf',
    };
@endphp
<a class="yk-row yk-row--tool" href="{{ $url }}">
    <span class="yk-row__icon yk-row__icon--{{ $tone }}" aria-hidden="true">
        @switch($icon)
            @case('mood')
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8.5 10h.01M15.5 10h.01"/><path d="M8.2 14.2c1.2 1.4 2.5 2 3.8 2s2.6-.6 3.8-2"/></svg>
                @break
            @case('journal')
                <svg viewBox="0 0 24 24"><path d="M7 4h10a2 2 0 0 1 2 2v14l-3-1.5L13 20l-3-1.5L7 20V6a2 2 0 0 1 2-2"/><path d="M10 9h6M10 13h4"/></svg>
                @break
            @case('gratitude')
                <svg viewBox="0 0 24 24"><path d="M12 19s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9Z"/></svg>
                @break
            @case('career')
            @case('skills')
                <svg viewBox="0 0 24 24"><path d="M12 3l2.2 4.5L19 9l-3.5 3.4.8 5.1L12 15.4 7.7 17.5l.8-5.1L5 9l4.8-1.5L12 3Z"/></svg>
                @break
            @case('nutrition')
            @case('bmi')
            @case('water')
            @case('weight')
                <svg viewBox="0 0 24 24"><path d="M12 21c4-3.2 6-6.2 6-9.2A6 6 0 0 0 6 11.8C6 14.8 8 17.8 12 21Z"/><path d="M10 11.5c.8-1.5 1.8-2.5 3-3"/></svg>
                @break
            @case('coins')
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v8M9.5 10.5c.5-1 1.4-1.5 2.5-1.5s2 .6 2 1.6-1 1.5-2.5 1.9-2.5.8-2.5 1.9 1 1.6 2.5 1.6 2-.5 2.5-1.5"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24"><path d="M5 19C14 19 19 13 19 5 11 5 5 10 5 19Z"/><path d="M8 16c3-1 6-4 7-8"/></svg>
        @endswitch
    </span>
    <span class="yk-row__body">
        <span class="yk-row__title">{{ $name }}</span>
        @if ($desc !== '')
            <span class="yk-row__sub">{{ $desc }}</span>
        @endif
    </span>
    <span class="yk-row__chev" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg>
    </span>
</a>
