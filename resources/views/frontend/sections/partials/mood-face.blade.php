@switch($mood)
    @case('very-happy')
        <svg viewBox="0 0 40 40" focusable="false">
            <defs>
                <radialGradient id="yk-face-very-happy" cx="42%" cy="34%" r="72%">
                    <stop offset="0" stop-color="#fff07a"/>
                    <stop offset="0.62" stop-color="#ffd232"/>
                    <stop offset="1" stop-color="#f4a912"/>
                </radialGradient>
            </defs>
            <circle cx="20" cy="20" r="16.6" fill="url(#yk-face-very-happy)" stroke="#eeac17" stroke-width="0.9"/>
            <ellipse cx="11.6" cy="22.4" rx="2.4" ry="1.5" fill="#ff8a5c" opacity="0.4"/>
            <ellipse cx="28.4" cy="22.4" rx="2.4" ry="1.5" fill="#ff8a5c" opacity="0.4"/>
            <ellipse cx="14.6" cy="16.2" rx="1.55" ry="2" fill="#6b3a0c"/>
            <path d="M23.4 16.6q1.6-2 3.2 0" fill="none" stroke="#6b3a0c" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M12.6 21.6h14.8q-.7 7.4-7.4 7.4t-7.4-7.4Z" fill="#e5303a"/>
            <path d="M16 26.9q4-2.6 8 0q-1.7 2.1-4 2.1t-4-2.1Z" fill="#ff7c6e"/>
        </svg>
        @break
    @case('good')
        <svg viewBox="0 0 40 40" focusable="false">
            <circle cx="20" cy="20" r="16" fill="#eaf8d4" stroke="#7db441" stroke-width="1.7"/>
            <circle cx="14.8" cy="16.4" r="1.7" fill="#6aa233"/>
            <circle cx="25.2" cy="16.4" r="1.7" fill="#6aa233"/>
            <path d="M13.6 22.6h12.8q-.9 5.6-6.4 5.6t-6.4-5.6Z" fill="#7db441"/>
        </svg>
        @break
    @case('neutral')
        <svg viewBox="0 0 40 40" focusable="false">
            <circle cx="20" cy="20" r="16" fill="#fff5df" stroke="#d39a45" stroke-width="1.6"/>
            <path d="M13.4 16.6h3.4M23.2 16.6h3.4" stroke="#c98a35" stroke-width="1.7" stroke-linecap="round"/>
            <path d="M13.8 24.8h12.4" stroke="#c98a35" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        @break
    @case('sad')
        <svg viewBox="0 0 40 40" focusable="false">
            <circle cx="20" cy="20" r="16" fill="#fde4e6" stroke="#d24e50" stroke-width="1.6"/>
            <circle cx="14.8" cy="16.6" r="1.6" fill="#c9403f"/>
            <circle cx="25.2" cy="16.6" r="1.6" fill="#c9403f"/>
            <path d="M13.8 27.6q6.2-6 12.4 0" fill="none" stroke="#c9403f" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        @break
    @default
        <svg viewBox="0 0 40 40" focusable="false">
            <circle cx="20" cy="20" r="16" fill="#fde8e3" stroke="#d65e48" stroke-width="1.6"/>
            <path d="M12.4 13.2l3.6 1.6M27.6 13.2 24 14.8" stroke="#cc523d" stroke-width="1.5" stroke-linecap="round"/>
            <circle cx="14.8" cy="17.8" r="1.5" fill="#cc523d"/>
            <circle cx="25.2" cy="17.8" r="1.5" fill="#cc523d"/>
            <path d="M13.8 27.8q6.2-5.6 12.4 0" fill="none" stroke="#cc523d" stroke-width="1.7" stroke-linecap="round"/>
            <path d="M29.6 7.6q2 3 0 4.4q-2-1.4 0-4.4Z" fill="#8fc6ef" opacity="0.9"/>
        </svg>
@endswitch
