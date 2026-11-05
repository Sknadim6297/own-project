@php
    $paths = [
        'mood' => '<circle cx="12" cy="12" r="8"/><path d="M9 10h.01M15 10h.01"/><path d="M9 14.2c.8 1 1.8 1.5 3 1.5s2.2-.5 3-1.5"/>',
        'journal' => '<path d="M6 4h10a2 2 0 0 1 2 2v14l-3-1.5L12 20l-3-1.5L6 20V6a2 2 0 0 1 2-2"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'gratitude' => '<path d="M12 19s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9Z"/>',
        'career' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4"/>',
        'skills' => '<path d="M4 19h16"/><path d="M7 16V9"/><path d="M12 16V6"/><path d="M17 16v-4"/>',
        'bmi' => '<rect x="5" y="4" width="14" height="16" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'water' => '<path d="M12 3c4 5 6 8 6 11a6 6 0 0 1-12 0c0-3 2-6 6-11Z"/>',
        'weight' => '<path d="M7 7h10l2 3v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-9l2-3Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/><path d="M9.5 13h5"/>',
        'nutrition' => '<path d="M12 4c-2 3-5 5-5 9a5 5 0 0 0 10 0c0-4-3-6-5-9Z"/><path d="M12 13v5"/>',
        'leaf' => '<path d="M5 19C14 19 19 13 19 5 11 5 5 10 5 19Z"/><path d="M8 16c3-1 6-4 7-8"/>',
    ];
@endphp
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">{!! $paths[$icon] ?? $paths['leaf'] !!}</svg>
