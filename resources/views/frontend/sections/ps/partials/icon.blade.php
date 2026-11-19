@php
    $size = $size ?? 20;
    $paths = [
        'heart-pulse' => '<path d="M20.800 5.600a5 5 0 0 0-7.100 0L12 7.300l-1.700-1.700a5 5 0 0 0-7.100 7.100L12 21l8.800-8.300a5 5 0 0 0 0-7.100z"/><path d="M3.500 12h4l2-3 3 6 2-3h6"/>',
        'calendar' => '<rect x="3.500" y="5" width="17" height="15.500" rx="3"/><path d="M8 3v4M16 3v4M3.500 10h17"/><path d="M8 14h3M13 14h3M8 17.500h3" />',
        'chat' => '<path d="M20 11.500c0 4-3.600 7-8 7-1 0-2-.150-2.900-.450L4.500 20l1.200-3.600C4.400 15.200 4 13.400 4 11.500c0-4 3.600-7 8-7s8 3 8 7z"/><path d="M9 11.500h.01M12 11.500h.01M15 11.500h.01" stroke-width="2.600"/>',
        'leaf' => '<path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14z"/><path d="M5 19c3-5 6-8 10-10"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.500 8.500-2 5-5 2 2-5z"/>',
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'shield' => '<path d="M12 3 4.500 6v5.500c0 4.700 3.100 8 7.500 9.500 4.400-1.500 7.500-4.800 7.500-9.500V6z"/><path d="m9 12 2.200 2.200L15.200 10"/>',
        'sparkle' => '<path d="m12 3 1.800 5.200L19 10l-5.200 1.800L12 17l-1.800-5.200L5 10l5.200-1.800z"/><path d="M19 17v4M17 19h4"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-width="2.400"/>',
        'check' => '<path d="m5 12.500 4.500 4.500L19 7" stroke-width="3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'bulb' => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.500 10.900c.6.500 1 1.200 1 2V16h5v-.100c0-.800.400-1.500 1-2A6 6 0 0 0 12 3z"/>',
        'alert' => '<path d="M12 3.500 2.500 20h19z"/><path d="M12 10v4.500M12 17.500h.01" stroke-width="2.400"/>',
        'history' => '<path d="M3.500 12a8.500 8.500 0 1 0 2.600-6.100L3.500 8.500"/><path d="M3.500 4v4.500H8M12 8v4.500l3 1.500"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12.500a1.500 1.500 0 0 0 1.500 1.500h7a1.500 1.500 0 0 0 1.500-1.500L18 7M9 7V4.500h6V7"/>',
        'edit' => '<path d="M4 20h4L19.500 8.500a2.800 2.800 0 0 0-4-4L4 16z"/><path d="m13.500 6.500 4 4"/>',
        'save' => '<path d="M5 3.500h11l3.500 3.500v12.500a1.500 1.500 0 0 1-1.500 1.500H5a1.500 1.500 0 0 1-1.500-1.500V5A1.500 1.500 0 0 1 5 3.500z"/><path d="M8 3.500v5h7v-5M8 20.500v-6h8v6"/>',
        'play' => '<path d="M7 4.500v15l12-7.500z" fill="currentColor"/>',
        'pause' => '<path d="M8 5v14M16 5v14" stroke-width="3.200"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.300 5.700M20 4v7h-7"/>',
        'users' => '<circle cx="9" cy="8" r="3.500"/><path d="M2.500 20c0-3.600 3-5.500 6.500-5.500s6.500 1.900 6.500 5.500"/><path d="M16 4.800a3.500 3.500 0 0 1 0 6.400M18.500 14.800c1.800.800 3 2.300 3 5.200"/>',
        'lock' => '<rect x="4.500" y="10.500" width="15" height="10" rx="3"/><path d="M8 10.500V8a4 4 0 0 1 8 0v2.500"/>',
        'book' => '<path d="M4 5.500A2.500 2.500 0 0 1 6.500 3H20v15H6.500A2.500 2.500 0 0 0 4 20.500z"/><path d="M4 20.500A2.500 2.500 0 0 0 6.500 23H20v-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14" stroke-width="2.600"/>',
    ];
@endphp
<svg class="yk-ps__icon" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? '' !!}</svg>
