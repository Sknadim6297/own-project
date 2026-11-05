@php
    $user = Auth::guard('user')->user();
    $professional = Auth::guard('professional')->user();
    $account = $user ?? $professional;
    $accountRole = $user ? 'Customer' : 'Professional';
    $profile = $user
        ? \App\Models\CustomerProfile::where('user_id', $user->id)->first()
        : ($professional
            ? \App\Models\Profile::where('professional_id', $professional->id)->first()
            : null);
    $profilePhoto = $user
        ? ($profile?->profile_image
            ? asset($profile->profile_image)
            : ($user->profile_photo_path
                ? asset('uploads/user_images/' . $user->profile_photo_path)
                : asset('frontend/assets/img/avatar.jpg')))
        : ($professional
            ? ($profile?->photo
                ? asset('storage/' . $profile->photo)
                : ($professional->photo
                    ? asset('storage/' . $professional->photo)
                    : asset('frontend/assets/img/avatar.jpg')))
            : null);
    $messagesUrl = $user
        ? route('user.admin-chat.index')
        : ($professional ? route('professional.admin-chat.index') : null);

    $navLinks = [
        [
            'label' => 'Explore',
            'href' => route('gridlisting'),
            'active' => request()->routeIs('gridlisting'),
            'placeholder' => false,
        ],
        [
            'label' => 'Tools',
            'href' => route('tools.index'),
            'active' => \App\Support\Tools\ToolsSection::isActive(),
            'placeholder' => false,
        ],
        [
            'label' => 'Experts',
            'href' => route('professionals'),
            'active' => request()->routeIs('professionals', 'professionals.details', 'professionals.details.simple'),
            'placeholder' => false,
        ],
        [
            'label' => 'Community',
            'href' => '#',
            'active' => false,
            'placeholder' => true,
        ],
        [
            'label' => 'Shop',
            'href' => '#',
            'active' => false,
            'placeholder' => true,
        ],
    ];

    if (config('yuktione.programmes_enabled')) {
        array_splice($navLinks, 3, 0, [[
            'label' => 'Programmes',
            'href' => route('programmes.index'),
            'active' => request()->routeIs('programmes.*', 'event.list', 'event.details'),
            'placeholder' => false,
        ]]);
    }
@endphp

<header class="yk-navbar" id="tazen-public-header" data-public-header>
    <div class="yk-navbar__bar">
        <a class="yk-navbar__brand" href="{{ route('home') }}" aria-label="YuktiOne home">
            <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="168" height="56">
        </a>

        <button class="yk-navbar__bell yk-navbar__bell--bar" type="button" aria-disabled="true" aria-label="Notifications, not available yet">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M6 9.5a6 6 0 0 1 12 0c0 4.2 1.4 5.6 1.4 5.6H4.6S6 13.7 6 9.5Z"></path>
                <path d="M10 18.2a2 2 0 0 0 4 0"></path>
            </svg>
        </button>

        <button class="yk-navbar__toggle" type="button" aria-expanded="false" aria-controls="yk-navbar-panel" aria-label="Open navigation menu" data-mobile-nav-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="yk-navbar__panel" id="yk-navbar-panel" data-mobile-nav>
            <div class="yk-navbar__mobile-heading">
                <span>Menu</span>
                <button class="yk-navbar__close" type="button" aria-label="Close navigation menu" data-mobile-nav-close>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m6 6 12 12M18 6 6 18"></path>
                    </svg>
                </button>
            </div>

            <nav class="yk-navbar__links" aria-label="Main navigation">
                <ul class="yk-navbar__list">
                    @foreach ($navLinks as $link)
                        <li>
                            <a
                                href="{{ $link['href'] }}"
                                @class(['is-active' => $link['active']])
                                @if ($link['active']) aria-current="page" @endif
                                @if ($link['placeholder']) data-placeholder-link aria-disabled="true" @endif
                            >
                                {{ $link['label'] }}
                                @if ($link['placeholder'])
                                    <span class="visually-hidden">, not available yet</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="yk-navbar__end">
                <form class="yk-navbar__search" action="{{ route('search.services') }}" method="get" role="search" data-header-search data-search-endpoint="{{ route('search.services') }}" data-service-url="{{ url('/service') }}">
                    <label class="visually-hidden" for="tazen-header-search">Search tools, experts, and topics</label>
                    <button type="submit" aria-label="Search">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="11" cy="11" r="6.25"></circle>
                            <path d="m16 16 4 4"></path>
                        </svg>
                    </button>
                    <input id="tazen-header-search" type="search" name="query" placeholder="Search tools, experts, topics..." autocomplete="off" enterkeyhint="search" required>
                    <span class="yk-navbar__search-status" role="status" aria-live="polite" data-search-status></span>
                </form>

                <button class="yk-navbar__bell yk-navbar__bell--toolbar" type="button" aria-disabled="true" aria-label="Notifications, not available yet">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M6 9.5a6 6 0 0 1 12 0c0 4.2 1.4 5.6 1.4 5.6H4.6S6 13.7 6 9.5Z"></path>
                        <path d="M10 18.2a2 2 0 0 0 4 0"></path>
                    </svg>
                </button>

                <div class="yk-navbar__auth">
                    @if ($account)
                        @if ($user)
                            <a class="yk-navbar__dashboard" href="{{ route('user.dashboard') }}">View Dashboard</a>
                        @endif
                        <div class="yk-navbar__account" data-account-menu>
                            <button class="yk-navbar__account-toggle" type="button" aria-expanded="false" aria-haspopup="menu" aria-controls="tazen-account-menu" data-account-toggle>
                                <img src="{{ $profilePhoto }}" alt="" width="36" height="36">
                                <span class="yk-navbar__account-copy">
                                    <span class="yk-navbar__account-name">{{ $account->name }}</span>
                                    <span class="yk-navbar__account-role">{{ $accountRole }}</span>
                                </span>
                                <svg class="yk-navbar__chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div class="yk-navbar__account-menu" id="tazen-account-menu" role="menu" aria-label="Account menu" hidden data-account-panel>
                                <span class="yk-navbar__account-label">{{ $accountRole }}</span>
                                @if ($messagesUrl)
                                    <a href="{{ $messagesUrl }}" role="menuitem">Messages</a>
                                @endif
                                @if ($user)
                                    <a href="{{ route('user.profile.index') }}" role="menuitem">My Profile</a>
                                    <a href="{{ route('user.all-appointment.index') }}" role="menuitem">My Bookings</a>
                                    <form action="{{ route('user.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" role="menuitem">Logout</button>
                                    </form>
                                @else
                                    <a href="{{ route('professional.dashboard') }}" role="menuitem">Dashboard</a>
                                    <a href="{{ route('professional.profile.index') }}" role="menuitem">My Profile</a>
                                    <a href="{{ route('professional.booking.index') }}" role="menuitem">My Bookings</a>
                                    <form action="{{ route('professional.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" role="menuitem">Logout</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @else
                        <a class="yk-navbar__login" href="{{ route('login') }}">Login</a>
                        <a class="yk-navbar__signup" href="{{ route('register') }}">Sign Up</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <button class="yk-navbar__backdrop" type="button" aria-label="Close navigation menu" data-mobile-nav-backdrop hidden></button>
</header>

<script src="{{ asset('frontend/assets/js/public-chrome.js') }}?v={{ filemtime(public_path('frontend/assets/js/public-chrome.js')) }}" defer></script>
