{{-- Colorful emotion artwork; gradients live in the shared defs printed by mood-emotions. --}}
@php($ink = '#4a2c10')
<svg viewBox="0 0 36 36" focusable="false">
@switch($key)
    @case('happy')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <ellipse cx="12.6" cy="13.4" rx="1.9" ry="2.9" fill="{{ $ink }}"/>
        <ellipse cx="23.4" cy="13.4" rx="1.9" ry="2.9" fill="{{ $ink }}"/>
        <path d="M8.6 19.2h18.8c0 5.4-4.2 9.4-9.4 9.4s-9.4-4-9.4-9.4Z" fill="{{ $ink }}"/>
        <path d="M10.2 19.2h15.6v1.3c0 .9-.7 1.6-1.6 1.6H11.8c-.9 0-1.6-.7-1.6-1.6Z" fill="#fff"/>
        <path d="M13 26.2c1.4-1.3 3.1-1.9 5-1.9s3.6.6 5 1.9c-1.4 1.3-3.1 2-5 2s-3.6-.7-5-2Z" fill="#ef6a5b"/>
        @break
    @case('calm')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <ellipse cx="9.4" cy="20.2" rx="2.6" ry="1.5" fill="#ff8a6a" opacity=".42"/>
        <ellipse cx="26.6" cy="20.2" rx="2.6" ry="1.5" fill="#ff8a6a" opacity=".42"/>
        <g fill="none" stroke="{{ $ink }}" stroke-linecap="round">
            <path d="M9.2 10.6c1.5-1.1 3.4-1.4 5.1-.8M26.8 10.6c-1.5-1.1-3.4-1.4-5.1-.8" stroke-width="1.5"/>
            <path d="M9.4 15c1.3 1.7 4.1 1.7 5.4 0M21.2 15c1.3 1.7 4.1 1.7 5.4 0" stroke-width="1.9"/>
            <path d="M12.8 22.6c3.1 2.9 7.3 2.9 10.4 0" stroke-width="1.9"/>
        </g>
        @break
    @case('excited')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <path d="M0-4.6 1.17-1.6 4.37-1.42 1.89.61 2.7 3.72 0 1.98-2.7 3.72-1.89.61-4.37-1.42-1.17-1.6Z" transform="translate(12.2 13.6)" fill="#f04a32" stroke="#c8261a" stroke-width=".5" stroke-linejoin="round"/>
        <path d="M0-4.6 1.17-1.6 4.37-1.42 1.89.61 2.7 3.72 0 1.98-2.7 3.72-1.89.61-4.37-1.42-1.17-1.6Z" transform="translate(23.8 13.6)" fill="#f04a32" stroke="#c8261a" stroke-width=".5" stroke-linejoin="round"/>
        <path d="M9 19.6h18c0 5.2-4 9-9 9s-9-3.8-9-9Z" fill="{{ $ink }}"/>
        <path d="M10.5 19.6h15v1.2c0 .9-.7 1.5-1.5 1.5H12c-.8 0-1.5-.6-1.5-1.5Z" fill="#fff"/>
        <path d="M13.2 26.4c1.4-1.2 3-1.8 4.8-1.8s3.4.6 4.8 1.8c-1.4 1.3-3 1.9-4.8 1.9s-3.4-.6-4.8-1.9Z" fill="#ef6a5b"/>
        @break
    @case('loved')
        <path d="M18 31.6S3 22.8 3 12.6a7.7 7.7 0 0 1 15-2.9 7.7 7.7 0 0 1 15 2.9C33 22.8 18 31.6 18 31.6Z" fill="url(#yk-emo-heart)"/>
        <path d="M7.6 11c1.2-1.9 3.6-2.6 5.4-1.6" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" opacity=".6"/>
        @break
    @case('grateful')
        <g fill="url(#yk-emo-hand)" stroke="#d48a14" stroke-width=".8" stroke-linejoin="round">
            <path d="M18 4.6c-2 .2-3.1 1.8-3.7 4.2L12 18.4l-3.9 5.6c-.8 1.2-.5 2.8.7 3.6l4.4 3.2c1 .7 2.4.6 3.2-.3l1.6-1.8Z"/>
            <path d="M18 4.6c2 .2 3.1 1.8 3.7 4.2l2.3 9.6 3.9 5.6c.8 1.2.5 2.8-.7 3.6l-4.4 3.2c-1 .7-2.4.6-3.2-.3L18 28.7Z"/>
        </g>
        <path d="M7.4 31.2 9.8 33M28.6 31.2 26.2 33" stroke="#5aa7e8" stroke-width="2.4" stroke-linecap="round"/>
        <path d="M5.6 9.4l1.6 1.2M4.8 14h2M30.4 9.4l-1.6 1.2M31.2 14h-2" stroke="#f3b23a" stroke-width="1.3" stroke-linecap="round"/>
        @break
    @case('neutral')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <ellipse cx="12.6" cy="14" rx="1.9" ry="2.8" fill="{{ $ink }}"/>
        <ellipse cx="23.4" cy="14" rx="1.9" ry="2.8" fill="{{ $ink }}"/>
        <path d="M12.2 23.8h11.6" stroke="{{ $ink }}" stroke-width="2.2" stroke-linecap="round"/>
        @break
    @case('thoughtful')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <g fill="none" stroke="{{ $ink }}" stroke-linecap="round" stroke-width="1.6">
            <path d="M9 9.4c1.7-1.7 4.1-2.2 6-1.3"/>
            <path d="M21.2 10.8c1.6-.6 3.6-.4 5 .4"/>
            <path d="M14.6 23.2c2.6-1.1 5.6-1.1 8.2-.1" stroke-width="1.9"/>
        </g>
        <ellipse cx="12.8" cy="14.2" rx="1.8" ry="2.7" fill="{{ $ink }}"/>
        <ellipse cx="23.6" cy="14.8" rx="1.8" ry="2.4" fill="{{ $ink }}"/>
        <path d="M7.6 34.2c-.5-2.2.2-4.2 1.9-5.3l1.4-6.6c.2-1.1 1.8-1.2 2.2-.1l1.2 4.2 4.6-1.6c1.2-.4 2.2.9 1.6 2l-.6 1.1 2.4.2c1.2.1 1.7 1.6.8 2.4l-3.4 3c-.9.8-2 1.2-3.2 1.2H8.2Z" fill="url(#yk-emo-hand)" stroke="#d48a14" stroke-width=".8" stroke-linejoin="round"/>
        @break
    @case('quiet')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <ellipse cx="12.6" cy="13" rx="1.8" ry="2.7" fill="{{ $ink }}"/>
        <ellipse cx="23.4" cy="13" rx="1.8" ry="2.7" fill="{{ $ink }}"/>
        <path d="M9.4 8.8c1.4-.9 3.2-1.1 4.8-.5M26.6 8.8c-1.4-.9-3.2-1.1-4.8-.5" fill="none" stroke="{{ $ink }}" stroke-width="1.4" stroke-linecap="round"/>
        <ellipse cx="18" cy="23.4" rx="3.2" ry="1.9" fill="{{ $ink }}"/>
        <path d="M12.6 34.6c-.5-2.6.6-4.6 2.5-5.4v-9.4c0-1.3.9-2.3 2.1-2.3s2.1 1 2.1 2.3v7.6l3.3.5c1.4.2 2.3 1.6 1.9 3l-.9 3.7Z" fill="url(#yk-emo-hand)" stroke="#d48a14" stroke-width=".8" stroke-linejoin="round"/>
        @break
    @case('tired')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <g fill="none" stroke="{{ $ink }}" stroke-linecap="round">
            <path d="M9.6 11.2c1.6.1 3.2-.6 4.4-1.8M26.4 11.2c-1.6.1-3.2-.6-4.4-1.8" stroke-width="1.5"/>
            <path d="M9.6 15.4c1.6 1 3.8 1 5.4 0M21 15.4c1.6 1 3.8 1 5.4 0" stroke-width="1.9"/>
        </g>
        <ellipse cx="17" cy="24.2" rx="2.7" ry="2.3" fill="{{ $ink }}"/>
        <path d="M27.2 20.2c1.5 2.1 2.5 3.6 2.5 5a2.5 2.5 0 0 1-5 0c0-1.4 1-2.9 2.5-5Z" fill="#5ab4f4"/>
        <path d="M26.2 25.4c0 .6.4 1 .9 1.1" fill="none" stroke="#fff" stroke-width=".9" stroke-linecap="round" opacity=".8"/>
        @break
    @case('unsure')
        <circle cx="18" cy="18" r="15.2" fill="#d8ecfd" stroke="#5d9fe4" stroke-width="1.6"/>
        <path d="M13.6 13.8a4.4 4.4 0 1 1 6.6 3.8c-1.4.8-2.2 1.6-2.2 3.2v.7" fill="none" stroke="#1f5fb6" stroke-width="3.1" stroke-linecap="round"/>
        <circle cx="18" cy="26.4" r="2" fill="#1f5fb6"/>
        @break
    @case('worried')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <path d="M8.8 11.8c1.9-.2 3.7-1.2 4.9-2.7M27.2 11.8c-1.9-.2-3.7-1.2-4.9-2.7" fill="none" stroke="{{ $ink }}" stroke-width="1.6" stroke-linecap="round"/>
        <ellipse cx="12.8" cy="15.4" rx="1.8" ry="2.6" fill="{{ $ink }}"/>
        <ellipse cx="23.2" cy="15.4" rx="1.8" ry="2.6" fill="{{ $ink }}"/>
        <path d="M12.6 26c3-3 7.8-3 10.8 0" fill="none" stroke="{{ $ink }}" stroke-width="2" stroke-linecap="round"/>
        @break
    @case('sad')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <path d="M9.2 13.8c1.4-1.9 4.3-1.9 5.7 0M21.1 13.8c1.4-1.9 4.3-1.9 5.7 0" fill="none" stroke="{{ $ink }}" stroke-width="1.9" stroke-linecap="round"/>
        <path d="M10 15.4h4.2v15.2a2.1 2.1 0 0 1-4.2 0ZM21.8 15.4H26v15.2a2.1 2.1 0 0 1-4.2 0Z" fill="#58b0f2"/>
        <path d="M13.2 22.6c0-1 .9-1.8 1.9-1.8h5.8c1 0 1.9.8 1.9 1.8 0 3-2.2 5.4-4.8 5.4s-4.8-2.4-4.8-5.4Z" fill="{{ $ink }}"/>
        <path d="M15 26.4c1.6-1 4.4-1 6 0-.9.9-1.9 1.4-3 1.4s-2.1-.5-3-1.4Z" fill="#ef6a5b"/>
        @break
    @case('angry')
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-angry)"/>
        <path d="M8.4 10.4l6.2 2.8M27.6 10.4l-6.2 2.8" stroke="#4a1408" stroke-width="2.2" stroke-linecap="round"/>
        <ellipse cx="12.9" cy="16" rx="1.8" ry="2.2" fill="#4a1408"/>
        <ellipse cx="23.1" cy="16" rx="1.8" ry="2.2" fill="#4a1408"/>
        <path d="M12.4 25.8c3.2-2.7 8-2.7 11.2 0" fill="none" stroke="#4a1408" stroke-width="2.2" stroke-linecap="round"/>
        @break
    @case('lonely')
        <path d="M16.8 31.2C11.2 27.8 3 21.2 3 12.6a7.7 7.7 0 0 1 13.8-3.2l-1.4 4.4 3 3.2-2.6 4.2 1.8 3.4Z" fill="url(#yk-emo-heart)"/>
        <path d="M19.4 31.2c5.6-3.4 13.6-10 13.6-18.6a7.7 7.7 0 0 0-13.6-2.2l-1.2 4 3 3.2-2.6 4.2 1.8 3.4Z" fill="url(#yk-emo-heart)"/>
        <path d="M7.4 11.2c1.1-1.7 3.2-2.3 4.8-1.5" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" opacity=".6"/>
        @break
    @default
        <circle cx="18" cy="18" r="16" fill="url(#yk-emo-face)"/>
        <g fill="none" stroke="{{ $ink }}" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 9.6c1.5 0 3-.6 4.2-1.6M27 9.6c-1.5 0-3-.6-4.2-1.6" stroke-width="1.5"/>
            <path d="M9.6 12.4l4.6 2.6-4.6 2.6M26.4 12.4 21.8 15l4.6 2.6" stroke-width="2"/>
            <path d="M11.8 25.6c1-1.3 2-1.3 3.1 0s2.1 1.3 3.1 0 2.1-1.3 3.1 0 2.1 1.3 3.1 0" stroke-width="1.9"/>
        </g>
@endswitch
</svg>
