@php
    $consultationLinks = [
        [
            'label' => 'Upcoming appointments',
            'url' => route('user.upcoming-appointment.index'),
            'icon' => 'clock',
            'active' => request()->routeIs('user.upcoming-appointment.*'),
        ],
        [
            'label' => 'All appointments',
            'url' => route('user.all-appointment.index'),
            'icon' => 'calendar',
            'active' => request()->routeIs('user.all-appointment.*'),
        ],
        [
            'label' => 'My Bookings',
            'url' => route('user.my-bookings'),
            'icon' => 'inbox',
            'active' => request()->routeIs('user.my-bookings'),
        ],
        [
            'label' => 'Additional services',
            'url' => route('user.additional-services.index'),
            'icon' => 'plus',
            'active' => request()->routeIs('user.additional-services.*'),
        ],
        [
            'label' => 'Messages',
            'url' => route('user.admin-chat.index'),
            'icon' => 'chat',
            'active' => request()->routeIs('user.admin-chat.*'),
        ],
    ];

    if (config('yuktione.programmes_enabled')) {
        $consultationLinks[] = [
            'label' => 'Programmes',
            'url' => route('user.customer-event.index'),
            'icon' => 'spark',
            'active' => request()->routeIs('user.customer-event.*'),
        ];
    }

    $customerNav = [
        [
            'title' => 'Overview',
            'links' => [
                [
                    'label' => 'Dashboard',
                    'url' => route('user.dashboard'),
                    'icon' => 'home',
                    'active' => request()->routeIs('user.dashboard'),
                ],
            ],
        ],
        [
            'title' => 'Consultations',
            'links' => $consultationLinks,
        ],
        [
            'title' => 'Account',
            'links' => [
                [
                    'label' => 'Billing',
                    'url' => route('user.billing.index'),
                    'icon' => 'wallet',
                    'active' => request()->routeIs('user.billing.*'),
                ],
                [
                    'label' => 'Profile',
                    'url' => route('user.profile.index'),
                    'icon' => 'user',
                    'active' => request()->routeIs('user.profile.*'),
                ],
                [
                    'label' => 'Back to home',
                    'url' => route('home'),
                    'icon' => 'inbox',
                    'active' => false,
                ],
            ],
        ],
    ];
@endphp

<x-tazen-v2.panel.sidebar :items="$customerNav" id="customer-panel-navigation">
    <x-slot:footer>
        <a
            href="{{ route('user.logout') }}"
            class="flex min-h-11 items-center gap-3 rounded-control px-3 text-sm font-medium text-text-secondary hover:bg-error-surface hover:text-error"
        >
            <x-tazen-v2.panel.icon name="logout" class="size-5" />
            Logout
        </a>
    </x-slot:footer>
</x-tazen-v2.panel.sidebar>
