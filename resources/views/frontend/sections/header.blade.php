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
    $defaultAvatar = \App\Helpers\ImageHelper::publicUrl('frontend/assets/img/avatar.jpg');
    $profilePhoto = $user
        ? \App\Helpers\ImageHelper::customerAvatarUrl($user, $profile, 'frontend/assets/img/avatar.jpg')
        : ($professional
            ? \App\Helpers\ImageHelper::getProfileImageUrl($profile, 'frontend/assets/img/avatar.jpg')
            : null);
    $accountInitials = $account
        ? strtoupper(collect(preg_split('/\s+/', trim((string) $account->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode(''))
        : '';
    $messagesUrl = $user
        ? route('user.admin-chat.index')
        : ($professional ? route('professional.admin-chat.index') : null);

    $catalogCategories = \App\Services\ExploreCatalog::CATEGORIES;
    $toolCategories = \App\Services\ToolsCatalog::CATEGORIES;
    $programmesEnabled = (bool) config('yuktione.programmes_enabled');

    $exploreDropdown = collect($catalogCategories)
        ->map(fn (array $category, string $slug) => [
            'label' => $category['label'],
            'href' => route('categories.show', $slug),
        ])
        ->values()
        ->all();

    $solutionsDropdown = array_merge(
        [
            ['label' => 'All Tools', 'href' => route('tools.index')],
            ['label' => 'All Categories', 'href' => route('categories.index')],
        ],
        collect($toolCategories)
            ->map(fn (array $category, string $slug) => [
                'label' => $category['label'],
                'href' => route('tools.index', ['category' => $slug]),
            ])
            ->values()
            ->all()
    );

    $expertsDropdown = array_merge(
        [
            ['label' => 'Browse all experts', 'href' => route('professionals')],
        ],
        collect($catalogCategories)
            ->map(fn (array $category, string $slug) => [
                'label' => $category['label'],
                'href' => route('professionals', ['category' => $slug]),
            ])
            ->values()
            ->all()
    );

    $navLinks = [
        [
            'id' => 'explore',
            'label' => 'Explore',
            'href' => route('categories.index'),
            'active' => request()->routeIs('categories.*', 'gridlisting'),
            'placeholder' => false,
            'variant' => 'link',
            'dropdown' => $exploreDropdown,
        ],
        [
            'id' => 'solutions',
            'label' => 'Tools',
            'href' => route('tools.index'),
            'active' => \App\Support\Tools\ToolsSection::isActive(),
            'placeholder' => false,
            'variant' => 'link',
            'dropdown' => $solutionsDropdown,
        ],
        [
            'id' => 'experts',
            'label' => 'Experts',
            'href' => route('professionals'),
            'active' => request()->routeIs('professionals', 'professionals.details', 'professionals.details.simple'),
            'placeholder' => false,
            'variant' => 'link',
            'dropdown' => $expertsDropdown,
        ],
        [
            'id' => 'programmes',
            'label' => 'Programmes',
            'href' => $programmesEnabled ? route('programmes.index') : '#',
            'active' => $programmesEnabled && request()->routeIs('programmes.*', 'event.list', 'event.details'),
            'placeholder' => ! $programmesEnabled,
            'variant' => 'link',
            'dropdown' => [],
        ],
        [
            'id' => 'community',
            'label' => 'Community',
            'href' => '#',
            'active' => false,
            'placeholder' => true,
            'variant' => 'link',
            'dropdown' => [],
        ],
    ];

    // Whichever menu item belongs to the page being viewed gets the gradient pill; all others stay normal links.
    $navLinks = array_map(static function (array $link): array {
        $link['variant'] = ($link['active'] && ! $link['placeholder']) ? 'pill' : 'link';

        return $link;
    }, $navLinks);
@endphp

<header class="yk-navbar" id="tazen-public-header" data-public-header>
    <div class="yk-navbar__bar">
        <button class="yk-navbar__toggle" type="button" aria-expanded="false" aria-controls="yk-navbar-panel" aria-label="Open navigation menu" data-mobile-nav-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <a class="yk-navbar__brand" href="{{ route('home') }}" aria-label="YuktiOne home">
            <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="168" height="56">
        </a>

        {{-- Phone/tablet quick actions (hidden from 1024px up, where the full nav shows) --}}
        <div class="yk-navbar__quick">
            @if ($user || $professional)
                <div class="yk-navbar__quick-account" data-quick-account>
                    <button
                        class="yk-navbar__quick-btn yk-navbar__quick-avatar"
                        type="button"
                        aria-label="My account menu"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="yk-quick-account-menu"
                        data-quick-account-toggle
                    >
                        @if ($profilePhoto)
                            <img
                                src="{{ $profilePhoto }}"
                                alt=""
                                width="32"
                                height="32"
                                data-fallback-avatar="{{ $defaultAvatar }}"
                                onerror="this.onerror=null;this.src=this.dataset.fallbackAvatar||'';"
                            >
                        @else
                            <span aria-hidden="true">{{ $accountInitials !== '' ? $accountInitials : 'YO' }}</span>
                        @endif
        </button>
                    <div class="yk-navbar__quick-menu" id="yk-quick-account-menu" role="menu" aria-label="Account menu" hidden data-quick-account-panel>
                        <a href="{{ $user ? route('user.dashboard') : route('professional.dashboard') }}" role="menuitem">Dashboard</a>
                        @if ($messagesUrl)
                            <a href="{{ $messagesUrl }}" role="menuitem">Messages</a>
                        @endif
                        @if ($user)
                            <a href="{{ route('user.profile.index') }}" role="menuitem">Profile</a>
                            <a href="{{ route('user.all-appointment.index') }}" role="menuitem">My Bookings</a>
                            <a href="{{ route('user.billing.index') }}" role="menuitem">Billing</a>
                            <a href="{{ route('user.yukti-points.index') }}" role="menuitem">Refer &amp; Earn</a>
                            <a class="is-danger" href="{{ route('user.logout') }}" role="menuitem">Logout</a>
                        @else
                            <a href="{{ route('professional.profile.index') }}" role="menuitem">Profile</a>
                            <a href="{{ route('professional.booking.index') }}" role="menuitem">My Bookings</a>
                            <a class="is-danger" href="{{ route('professional.logout') }}" role="menuitem">Logout</a>
                        @endif
                    </div>
                </div>
            @else
                <a class="yk-navbar__quick-login" href="{{ route('login') }}" aria-label="Log in">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="8.5" r="3.6"></circle>
                        <path d="M4.8 20c.9-3.6 3.5-5.4 7.2-5.4s6.3 1.8 7.2 5.4"></path>
                    </svg>
                    <span>Log in</span>
                </a>
            @endif
        </div>

        <div class="yk-navbar__panel" id="yk-navbar-panel" data-mobile-nav>
            <div class="yk-navbar__mobile-heading">
                <a class="yk-navbar__drawer-brand" href="{{ route('home') }}" aria-label="YuktiOne home">
                    <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="168" height="56">
                </a>
                <button class="yk-navbar__close" type="button" aria-label="Close navigation menu" data-mobile-nav-close>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m6 6 12 12M18 6 6 18"></path>
                    </svg>
                </button>
            </div>

            <nav class="yk-navbar__links" aria-label="Main navigation">
                <ul class="yk-navbar__list">
                    {{-- Phone/tablet menu only: goes to the home page (the desktop bar uses the logo for this) --}}
                    <li class="yk-navbar__item yk-navbar__item--home">
                        <a
                            href="{{ route('home') }}"
                            @class([
                                'yk-navbar__link',
                                'yk-navbar__link--'.(request()->routeIs('home') ? 'pill' : 'link'),
                                'is-active' => request()->routeIs('home'),
                            ])
                            @if (request()->routeIs('home')) aria-current="page" @endif
                        >
                            <span>Home</span>
                        </a>
                    </li>
                    @foreach ($navLinks as $link)
                        @php
                            $hasDropdown = ! empty($link['dropdown']);
                            $menuId = 'yk-nav-menu-'.$link['id'];
                        @endphp
                        <li @class(['yk-navbar__item', 'has-dropdown' => $hasDropdown]) @if ($hasDropdown) data-nav-item @endif>
                            @if ($hasDropdown)
                                <div class="yk-navbar__item-row">
                                    <a
                                        href="{{ $link['href'] }}"
                                        @class([
                                            'yk-navbar__link',
                                            'yk-navbar__link--'.$link['variant'],
                                            'is-active' => $link['active'],
                                            'is-placeholder' => $link['placeholder'],
                                        ])
                                        @if ($link['active']) aria-current="page" @endif
                                    >
                                        <span>{{ $link['label'] }}</span>
                                    </a>
                                    <button
                                        class="yk-navbar__caret"
                                        type="button"
                                        aria-expanded="false"
                                        aria-controls="{{ $menuId }}"
                                        aria-label="{{ $link['label'] }} menu"
                                        data-nav-toggle
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="m6 9 6 6 6-6"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="yk-navbar__dropdown" id="{{ $menuId }}" hidden data-nav-panel>
                                    <ul class="yk-navbar__dropdown-list">
                                        @foreach ($link['dropdown'] as $item)
                                            <li>
                                                <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <a
                                    href="{{ $link['href'] }}"
                                    @class([
                                        'yk-navbar__link',
                                        'yk-navbar__link--'.$link['variant'],
                                        'is-active' => $link['active'],
                                        'is-placeholder' => $link['placeholder'],
                                    ])
                                    @if ($link['active']) aria-current="page" @endif
                                    @if ($link['placeholder'])
                                        data-placeholder-link
                                        aria-disabled="true"
                                        tabindex="-1"
                                        title="Not available yet"
                                    @endif
                                >
                                    <span>{{ $link['label'] }}</span>
                                    @if ($link['placeholder'])
                                        <span class="yk-navbar__soon" aria-hidden="true">Soon</span>
                                        <span class="visually-hidden">, not available yet</span>
                                    @endif
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="yk-navbar__end">
                <div class="yk-navbar__search-wrap">
                    <button
                        class="yk-navbar__search-toggle"
                        type="button"
                        aria-expanded="false"
                        aria-controls="yk-navbar-search"
                        aria-label="Open search"
                        data-home-search-toggle
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="11" cy="11" r="6.25"></circle>
                            <path d="m16 16 4 4"></path>
                        </svg>
                    </button>

                    <form
                        class="yk-navbar__search"
                        id="yk-navbar-search"
                        action="{{ route('search.services') }}"
                        method="get"
                        role="search"
                        data-header-search
                        data-search-endpoint="{{ route('search.services') }}"
                        data-service-url="{{ url('/service') }}"
                    >
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
                </div>

                <div class="yk-navbar__auth">
                    @if ($user || $professional)
                        @php
                            $onCustomerHome = $user && request()->routeIs('user.dashboard');
                            $onProfessionalHome = $professional && request()->routeIs('professional.dashboard');
                            // Public chrome: hide the big CTA when already in logged-in customer/pro surfaces.
                            $hideDashboardCta = ($user && request()->routeIs(
                                'user.dashboard',
                                'user.yukti-points.*',
                                'user.profile.*',
                                'user.all-appointment.*',
                                'user.upcoming-appointment.*',
                                'user.billing.*',
                                'user.admin-chat.*',
                                'user.additional-services.*',
                                'user.bookings.*'
                            )) || ($professional && request()->routeIs(
                                'professional.dashboard',
                                'professional.profile.*',
                                'professional.booking.*',
                                'professional.admin-chat.*',
                                'professional.plans.*'
                            ));
                        @endphp
                        @if ($user && ! $hideDashboardCta)
                            <a class="yk-navbar__dashboard" href="{{ route('user.dashboard') }}">Go to Dashboard</a>
                        @endif
                        @if ($professional && ! $hideDashboardCta)
                            <a class="yk-navbar__dashboard" href="{{ route('professional.dashboard') }}">Go to Dashboard</a>
                    @endif
                        <div class="yk-navbar__account" data-account-menu>
                            <button class="yk-navbar__account-toggle" type="button" aria-expanded="false" aria-haspopup="menu" aria-controls="tazen-account-menu" data-account-toggle>
                                @if ($profilePhoto)
                                    <img
                                        src="{{ $profilePhoto }}"
                                        alt=""
                                        width="36"
                                        height="36"
                                        data-fallback-avatar="{{ $defaultAvatar }}"
                                        onerror="this.onerror=null;this.src=this.dataset.fallbackAvatar||'';if(!this.getAttribute('src')){this.style.display='none';var s=this.nextElementSibling;if(s&&s.classList.contains('yk-navbar__avatar-fallback')){s.hidden=false;}}"
                                    >
                                    <span class="yk-navbar__avatar-fallback" aria-hidden="true" hidden>{{ $accountInitials !== '' ? $accountInitials : 'YO' }}</span>
                                @else
                                    <span class="yk-navbar__avatar-fallback" aria-hidden="true">{{ $accountInitials !== '' ? $accountInitials : 'YO' }}</span>
                @endif
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
                                    @unless ($onCustomerHome)
                                        <a href="{{ route('user.dashboard') }}" role="menuitem">Dashboard</a>
                                    @endunless
                                    <a href="{{ route('user.yukti-points.index') }}" role="menuitem">Refer &amp; Earn</a>
                                    <a href="{{ route('user.profile.index') }}" role="menuitem">My Profile</a>
                                    <a href="{{ route('user.all-appointment.index') }}" role="menuitem">My Bookings</a>
                                    <a href="{{ route('user.logout') }}" role="menuitem">Logout</a>
                                @elseif ($professional)
                                    @unless ($onProfessionalHome)
                                        <a href="{{ route('professional.dashboard') }}" role="menuitem">Dashboard</a>
                                    @endunless
                                    <a href="{{ route('professional.profile.index') }}" role="menuitem">My Profile</a>
                                    <a href="{{ route('professional.booking.index') }}" role="menuitem">My Bookings</a>
                                    <a href="{{ route('professional.logout') }}" role="menuitem">Logout</a>
                            @endif
                        </div>
                    </div>
                @else
                        <a class="yk-navbar__login" href="{{ route('login') }}">Log In</a>
                        <a class="yk-navbar__signup" href="{{ route('register') }}">Get Started</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <button class="yk-navbar__backdrop" type="button" aria-label="Close navigation menu" data-mobile-nav-backdrop hidden></button>
</header>

<script src="{{ asset('frontend/assets/js/public-chrome.js') }}?v={{ filemtime(public_path('frontend/assets/js/public-chrome.js')) }}" defer></script>
