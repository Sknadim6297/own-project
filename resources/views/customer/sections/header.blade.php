@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    $user = Auth::guard('user')->user();
    $profile = $user ? $user->customerProfile : null;
    $avatar = $profile && $profile->profile_image
        ? asset($profile->profile_image)
        : asset('img/default-avatar.png');
    $fallbackAvatar = asset('img/default-avatar.png');

    $unreadBookingChats = collect();
    $unreadChatCount = 0;
    $unreadNotifications = collect();
    $unreadNotificationsCount = 0;
    $unreadAdminMessages = collect();
    $unreadAdminMessagesCount = 0;
    $allNotifications = collect();
    $totalNotifications = 0;

    if ($user) {
        $userId = $user->id;

        try {
            $unreadBookingChats = \App\Models\BookingChat::whereHas('booking', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->where('sender_type', 'professional')
                ->where('is_read', false)
                ->where('is_system_message', false)
                ->select('booking_id', DB::raw('COUNT(*) as unread_count'), DB::raw('MAX(created_at) as last_message_at'))
                ->groupBy('booking_id')
                ->orderBy('last_message_at', 'desc')
                ->get();

            if ($unreadBookingChats->isNotEmpty()) {
                $bookingIds = $unreadBookingChats->pluck('booking_id')->toArray();
                $bookings = \App\Models\Booking::with(['professional', 'service'])
                    ->whereIn('id', $bookingIds)
                    ->get()
                    ->keyBy('id');

                foreach ($unreadBookingChats as $chatGroup) {
                    $chatGroup->booking = $bookings->get($chatGroup->booking_id);
                }
            }

            $unreadChatCount = $unreadBookingChats->sum('unread_count');

            $unreadNotifications = $user->unreadNotifications()
                ->where('type', 'App\Notifications\AdditionalServiceNotification')
                ->orderBy('created_at', 'desc')
                ->get();

            $unreadNotificationsCount = $unreadNotifications->count();

            $unreadAdminMessages = \App\Models\AdminProfessionalChatMessage::whereHas('chat', function ($q) use ($userId) {
                    $q->where('customer_id', $userId)
                      ->where('chat_type', 'admin_customer');
                })
                ->where('sender_type', 'App\\Models\\Admin')
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->get();

            $unreadAdminMessagesCount = $unreadAdminMessages->count();

            foreach ($unreadNotifications as $notification) {
                $allNotifications->push([
                    'type' => 'service_notification',
                    'timestamp' => $notification->created_at,
                    'data' => $notification,
                ]);
            }

            foreach ($unreadAdminMessages as $adminMessage) {
                $allNotifications->push([
                    'type' => 'admin_message',
                    'timestamp' => $adminMessage->created_at,
                    'data' => $adminMessage,
                ]);
            }

            foreach ($unreadBookingChats as $chatGroup) {
                $allNotifications->push([
                    'type' => 'chat_notification',
                    'timestamp' => \Carbon\Carbon::parse($chatGroup->last_message_at),
                    'data' => $chatGroup,
                ]);
            }

            $allNotifications = $allNotifications->sortByDesc('timestamp');
        } catch (\Exception $e) {
            $unreadBookingChats = collect();
            $unreadChatCount = 0;
            $unreadNotifications = collect();
            $unreadNotificationsCount = 0;
            $unreadAdminMessages = collect();
            $unreadAdminMessagesCount = 0;
            $allNotifications = collect();
        }

        $totalNotifications = $unreadChatCount + $unreadNotificationsCount + $unreadAdminMessagesCount;
    }
@endphp

<header class="sticky top-0 z-30 border-b border-divider bg-surface/95 backdrop-blur">
    <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                data-panel-open
                aria-expanded="false"
                aria-controls="customer-panel-navigation"
                class="inline-flex size-11 items-center justify-center rounded-control border border-border bg-surface text-text-primary hover:bg-surface-muted lg:hidden"
                aria-label="Open navigation menu"
            >
                <x-tazen-v2.panel.icon name="menu" />
            </button>
            <a href="{{ route('user.dashboard') }}" class="inline-flex items-center lg:hidden">
                <img
                    src="{{ asset('frontend/assets/Yuktione_logo.png') }}"
                    alt="Yuktione"
                    width="120"
                    height="48"
                    class="h-8 w-auto object-contain object-left"
                >
            </a>
            <p class="hidden truncate text-sm font-semibold text-text-secondary sm:block lg:text-base">Customer workspace</p>
        </div>

        @if ($user)
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="relative" data-panel-dropdown>
                    <button
                        type="button"
                        data-panel-dropdown-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                        class="relative inline-flex size-11 items-center justify-center rounded-control border border-border bg-surface text-text-primary hover:bg-surface-muted"
                        aria-label="Notifications"
                    >
                        <x-tazen-v2.panel.icon name="bell" />
                        @if ($totalNotifications > 0)
                            <span data-notification-badge class="absolute right-1.5 top-1.5 inline-flex min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-semibold text-white">
                                {{ $totalNotifications }}
                            </span>
                        @else
                            <span data-notification-badge class="absolute right-1.5 top-1.5 hidden min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-semibold text-white"></span>
                        @endif
                    </button>
                    <div data-panel-dropdown-panel class="absolute right-0 mt-2 hidden w-[min(24rem,calc(100vw-2rem))] rounded-panel border border-border bg-surface shadow-popover">
                        <div class="flex items-center justify-between border-b border-divider px-4 py-3">
                            <h2 class="text-sm font-semibold text-text-primary">Notifications</h2>
                        </div>
                        <div data-notification-list class="max-h-80 overflow-y-auto p-3">
                            @forelse ($allNotifications as $notificationItem)
                                @if ($notificationItem['type'] === 'service_notification')
                                    @php
                                        $notification = $notificationItem['data'];
                                        $data = $notification->data;
                                        $serviceId = $data['additional_service_id'] ?? null;
                                        $serviceName = $data['service_name'] ?? 'Additional Service';
                                    @endphp
                                    <article class="notification-item mb-2 rounded-card border border-border p-3" data-notification-id="{{ $notification->id }}">
                                        <p class="text-sm font-semibold text-text-primary">{{ $data['title'] ?? 'Service update' }}</p>
                                        <p class="mt-1 text-sm text-text-secondary">{{ $data['message'] ?? 'You have a new update regarding your additional service.' }}</p>
                                        <p class="mt-1 text-xs text-text-muted">{{ $serviceName }} · {{ $notification->created_at->diffForHumans() }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($serviceId)
                                                <x-tazen-v2.ui.button href="{{ route('user.additional-services.show', $serviceId) }}" size="sm" variant="outline">View details</x-tazen-v2.ui.button>
                                            @endif
                                            <button type="button" class="mark-read-btn rounded-control border border-border px-3 py-2 text-sm font-semibold" data-notification-id="{{ $notification->id }}">Mark as read</button>
                                        </div>
                                    </article>
                                @elseif ($notificationItem['type'] === 'admin_message')
                                    @php
                                        $adminMessage = $notificationItem['data'];
                                        $messagePreview = $adminMessage->message
                                            ? \Illuminate\Support\Str::limit($adminMessage->message, 100)
                                            : 'File attachment';
                                    @endphp
                                    <article class="notification-item mb-2 rounded-card border border-border p-3" data-message-id="{{ $adminMessage->id }}">
                                        <p class="text-sm font-semibold text-text-primary">New message from admin</p>
                                        <p class="mt-1 text-sm text-text-secondary">{{ $messagePreview }}</p>
                                        <p class="mt-1 text-xs text-text-muted">{{ $adminMessage->created_at->diffForHumans() }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <x-tazen-v2.ui.button href="{{ route('user.admin-chat.index') }}" size="sm">Open chat</x-tazen-v2.ui.button>
                                            <button type="button" class="mark-admin-read-btn rounded-control border border-border px-3 py-2 text-sm font-semibold" data-message-id="{{ $adminMessage->id }}">Mark as read</button>
                                        </div>
                                    </article>
                                @elseif ($notificationItem['type'] === 'chat_notification')
                                    @php
                                        $chatGroup = $notificationItem['data'];
                                        $booking = $chatGroup->booking ?? null;
                                    @endphp
                                    @if ($booking)
                                        @php
                                            $professionalName = optional($booking->professional)->name ?? 'Professional';
                                            $serviceName = $booking->service_name ?? (optional($booking->service)->name ?? 'Service');
                                        @endphp
                                        <article class="notification-item mb-2 rounded-card border border-border p-3">
                                            <p class="text-sm font-semibold text-text-primary">New message from {{ $professionalName }}</p>
                                            <p class="mt-1 text-sm text-text-secondary">{{ $chatGroup->unread_count }} unread message(s) for booking #{{ $booking->id }}</p>
                                            <p class="mt-1 text-xs text-text-muted">{{ $serviceName }} · {{ \Carbon\Carbon::parse($chatGroup->last_message_at)->diffForHumans() }}</p>
                                            <div class="mt-3">
                                                <x-tazen-v2.ui.button href="{{ route('user.chat.open', $booking->id) }}" size="sm">Open chat</x-tazen-v2.ui.button>
                                            </div>
                                        </article>
                                    @endif
                                @endif
                            @empty
                                <div class="notification-empty px-3 py-8 text-center">
                                    <p class="text-sm font-semibold text-text-primary">You're all caught up</p>
                                    <p class="mt-1 text-sm text-text-secondary">No new notifications right now.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <a
                    href="{{ route('user.admin-chat.index') }}"
                    class="relative inline-flex size-11 items-center justify-center rounded-control border border-border bg-surface text-text-primary hover:bg-surface-muted"
                    aria-label="Chat with admin"
                >
                    <x-tazen-v2.panel.icon name="chat" />
                    <span id="chatUnreadBadge" class="absolute right-1.5 top-1.5 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-semibold text-white {{ $unreadAdminMessagesCount > 0 ? 'inline-flex' : 'hidden' }}">
                        {{ $unreadAdminMessagesCount }}
                    </span>
                </a>

                <div class="relative" data-panel-dropdown>
                    <button
                        type="button"
                        data-panel-dropdown-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                        class="flex items-center gap-2 rounded-control border border-border bg-surface py-1 pl-1 pr-2 hover:bg-surface-muted"
                    >
                        <img
                            src="{{ $avatar }}"
                            alt="{{ $user->name }}"
                            class="size-9 rounded-full object-cover"
                            onerror="this.onerror=null;this.src='{{ $fallbackAvatar }}';"
                        >
                        <span class="hidden text-left sm:block">
                            <span class="block max-w-36 truncate text-sm font-semibold text-text-primary">{{ $user->name }}</span>
                            <span class="block text-xs text-text-muted">Customer</span>
                        </span>
                    </button>
                    <div data-panel-dropdown-panel class="absolute right-0 mt-2 hidden w-52 rounded-panel border border-border bg-surface p-2 shadow-popover">
                        <a href="{{ route('user.profile.index') }}" class="block rounded-control px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary">Profile</a>
                        <a href="{{ route('user.billing.index') }}" class="block rounded-control px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary">Billing</a>
                        <a href="{{ route('user.yukti-points.index') }}" class="block rounded-control px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary">Refer &amp; Earn</a>
                        <a href="{{ route('user.logout') }}" class="block rounded-control px-3 py-2 text-sm font-medium text-error hover:bg-error-surface">Logout</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        document.querySelectorAll('.mark-read-btn').forEach((button) => {
            button.addEventListener('click', () => markNotificationAsRead(button.dataset.notificationId));
        });

        document.querySelectorAll('.mark-admin-read-btn').forEach((button) => {
            button.addEventListener('click', () => markAdminMessageAsRead(button.dataset.messageId));
        });

        startAdminMessageNotificationCheck();

        function markNotificationAsRead(notificationId) {
            fetch(@json(url('/user/notifications')) + '/' + notificationId + '/mark-as-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    removeNotificationItem(`[data-notification-id="${notificationId}"]`);
                }
            })
            .catch((error) => console.error('Error marking notification as read:', error));
        }

        function markAdminMessageAsRead(messageId) {
            fetch(@json(url('/user/admin-chat/messages')) + '/' + messageId + '/mark-as-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ message_id: messageId })
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    removeNotificationItem(`[data-message-id="${messageId}"]`);
                }
            })
            .catch((error) => console.error('Error marking admin message as read:', error));
        }

        function removeNotificationItem(selector) {
            const notificationItem = document.querySelector(selector);
            if (!notificationItem) {
                return;
            }
            notificationItem.style.opacity = '0.5';
            setTimeout(() => {
                notificationItem.remove();
                updateNotificationBadge();
            }, 200);
        }

        function updateNotificationBadge() {
            const remaining = document.querySelectorAll('.notification-item').length;
            const badge = document.querySelector('[data-notification-badge]');
            const list = document.querySelector('[data-notification-list]');

            if (remaining === 0) {
                if (badge) {
                    badge.classList.add('hidden');
                    badge.textContent = '';
                }
                if (list && !list.querySelector('.notification-empty')) {
                    list.innerHTML = '<div class="notification-empty px-3 py-8 text-center"><p class="text-sm font-semibold text-text-primary">You\'re all caught up</p><p class="mt-1 text-sm text-text-secondary">No new notifications right now.</p></div>';
                }
            } else if (badge) {
                badge.textContent = remaining;
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
            }
        }

        function startAdminMessageNotificationCheck() {
            setInterval(function () {
                fetch(@json(route('user.admin-chat.unread-count')), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                })
                .then((response) => response.json())
                .then((data) => {
                    const chatBadge = document.getElementById('chatUnreadBadge');
                    if (data.success && data.unread_count > 0) {
                        if (chatBadge) {
                            chatBadge.textContent = data.unread_count;
                            chatBadge.classList.remove('hidden');
                            chatBadge.classList.add('inline-flex');
                        }
                    } else if (chatBadge) {
                        chatBadge.classList.add('hidden');
                    }
                })
                .catch((error) => console.error('Error checking for new admin messages:', error));
            }, 30000);
        }
    });
</script>
