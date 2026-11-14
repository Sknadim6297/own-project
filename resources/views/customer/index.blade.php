@extends('customer.layout.layout')

@section('title', 'Dashboard | Yuktione')

@section('content')
@php
    $userId = Auth::guard('user')->id();
    $today = \Carbon\Carbon::today();
    $threeDaysLater = \Carbon\Carbon::today()->addDays(3);
    $user = Auth::guard('user')->user();
    $profile = $user?->customerProfile;
    $avatar = \App\Helpers\ImageHelper::customerAvatarUrl($user, $profile, 'img/default-avatar.png');
    $fallbackAvatar = \App\Helpers\ImageHelper::publicUrl('img/default-avatar.png');

    $upcomingAppointments = collect();
    $upcomingSlots = collect();
    $upcomingCount = 0;
    $todayCount = 0;
    $totalAppointments = 0;
    $completedAppointments = 0;
    $recentBookings = collect();
    $events = collect();
    $upcomingBookedEvents = 0;
    $dashboardError = null;
    $profileCompletion = 0;

    $profileFields = [
        $user?->name,
        $user?->email,
        $profile?->phone,
        $profile?->address,
        $profile?->city,
        $profile?->state,
        $profile?->zip_code,
        $profile?->profile_image,
    ];
    $filledFields = collect($profileFields)->filter(fn ($value) => filled($value))->count();
    $profileCompletion = (int) round(($filledFields / max(count($profileFields), 1)) * 100);

    try {
        $upcomingAppointments = \App\Models\Booking::where('user_id', $userId)
            ->whereHas('timedates', function ($query) use ($today) {
                $query->where('date', '>=', $today->format('Y-m-d'))
                    ->whereIn('status', ['pending', 'confirmed']);
            })
            ->with([
                'professional',
                'service',
                'timedates' => function ($query) use ($today) {
                    $query->where('date', '>=', $today->format('Y-m-d'))
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->orderBy('date', 'asc');
                },
            ])
            ->get();

        foreach ($upcomingAppointments as $appointment) {
            foreach ($appointment->timedates as $slot) {
                $upcomingSlots->push([
                    'booking' => $appointment,
                    'slot' => $slot,
                ]);
            }
        }

        $upcomingSlots = $upcomingSlots->sortBy(function ($item) {
            return $item['slot']->date . ' ' . ($item['slot']->time_slot ?? '');
        })->values();
        $upcomingCount = $upcomingSlots->count();
        $todayCount = $upcomingSlots->filter(function ($item) use ($today) {
            return \Carbon\Carbon::parse($item['slot']->date)->isSameDay($today);
        })->count();

        $allAppointments = \App\Models\Booking::where('user_id', $userId)->with('timedates')->get();
        foreach ($allAppointments as $appointment) {
            if ($appointment->timedates && $appointment->timedates->count() > 0) {
                $totalAppointments += $appointment->timedates->count();
                $completedAppointments += $appointment->timedates->where('status', 'completed')->count();
            }
        }

        $recentBookings = \App\Models\Booking::where('user_id', $userId)
            ->with(['professional', 'service'])
            ->latest()
            ->take(5)
            ->get();

        $upcomingEventBookings = \App\Models\EventBooking::where('user_id', $userId)
            ->whereHas('event', function ($query) use ($today, $threeDaysLater) {
                $query->whereBetween('date', [
                    $today->format('Y-m-d'),
                    $threeDaysLater->format('Y-m-d'),
                ]);
            })
            ->with(['event'])
            ->get();
        $upcomingBookedEvents = $upcomingEventBookings->count();

        $events = \App\Models\AllEvent::where('date', '>=', $today->format('Y-m-d'))
            ->orderBy('date', 'asc')
            ->take(3)
            ->get();
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Customer dashboard records could not be loaded.', [
            'user_id' => $userId,
            'exception' => $e,
        ]);
        $upcomingSlots = collect();
        $upcomingCount = 0;
        $todayCount = 0;
        $totalAppointments = 0;
        $completedAppointments = 0;
        $recentBookings = collect();
        $events = collect();
        $upcomingBookedEvents = 0;
        $dashboardError = 'Some dashboard information could not be loaded. Please try again later.';
    }

    $pfmCatalog = \App\Support\Pfm\PfmCatalog::TOOLS;
    $psCatalog = \App\Support\ParentingSpirituality\PsCatalog::TOOLS;
    $savedToolsPerPage = 5;
    $savedToolsTab = in_array(request('tools_tab'), ['recent', 'saved', 'drafts'], true)
        ? request('tools_tab')
        : 'recent';
    $savedToolsPage = max(1, (int) request('tools_page', 1));
    $savedToolsFetchLimit = 150;

    $wrapToolRows = function ($items, string $source) {
        return collect($items)->map(fn ($item) => (object) ['source' => $source, 'record' => $item]);
    };

    $pfmRows = collect();
    $psRows = collect();
    $savedToolsCounts = ['recent' => 0, 'saved' => 0, 'drafts' => 0];

    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('pfm_calculations')) {
            $pfmQuery = \App\Models\PfmCalculation::query()->where('user_id', $userId);
            $savedToolsCounts['recent'] += (clone $pfmQuery)->count();
            $savedToolsCounts['saved'] += (clone $pfmQuery)->where('is_draft', false)->count();
            $savedToolsCounts['drafts'] += (clone $pfmQuery)->where('is_draft', true)->count();

            $activePfm = clone $pfmQuery;
            if ($savedToolsTab === 'saved') {
                $activePfm->where('is_draft', false);
            } elseif ($savedToolsTab === 'drafts') {
                $activePfm->where('is_draft', true);
            }
            $pfmRows = $wrapToolRows(
                $activePfm->orderByDesc('updated_at')->orderByDesc('id')->limit($savedToolsFetchLimit)->get(),
                'pfm'
            );
        }
    } catch (\Throwable $e) {
        $pfmRows = collect();
    }

    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('ps_tool_results')) {
            $psQuery = \App\Models\PsToolResult::query()->where('user_id', $userId);
            $savedToolsCounts['recent'] += (clone $psQuery)->count();
            $savedToolsCounts['saved'] += (clone $psQuery)->where('is_draft', false)->count();
            $savedToolsCounts['drafts'] += (clone $psQuery)->where('is_draft', true)->count();

            $activePs = clone $psQuery;
            if ($savedToolsTab === 'saved') {
                $activePs->where('is_draft', false);
            } elseif ($savedToolsTab === 'drafts') {
                $activePs->where('is_draft', true);
            }
            $psRows = $wrapToolRows(
                $activePs->orderByDesc('updated_at')->orderByDesc('id')->limit($savedToolsFetchLimit)->get(),
                'ps'
            );
        }
    } catch (\Throwable $e) {
        $psRows = collect();
    }

    $savedToolsMerged = $pfmRows
        ->concat($psRows)
        ->sortByDesc(fn ($row) => optional($row->record->updated_at)->getTimestamp() ?? 0)
        ->values();

    $savedToolsTotal = $savedToolsMerged->count();
    if ($savedToolsPage > 1 && (($savedToolsPage - 1) * $savedToolsPerPage) >= max($savedToolsTotal, 1)) {
        $savedToolsPage = (int) max(1, (int) ceil($savedToolsTotal / $savedToolsPerPage));
    }

    $savedToolsPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
        $savedToolsMerged->forPage($savedToolsPage, $savedToolsPerPage)->values(),
        $savedToolsTotal,
        $savedToolsPerPage,
        $savedToolsPage,
        [
            'path' => url()->current(),
            'pageName' => 'tools_page',
            'query' => array_merge(
                request()->except(['tools_page']),
                ['tools_tab' => $savedToolsTab]
            ),
        ]
    );

    $resolveSavedToolRow = function (object $row) use ($pfmCatalog, $psCatalog): ?array {
        $item = $row->record;
        $meta = $row->source === 'ps'
            ? ($psCatalog[$item->tool_key] ?? null)
            : ($pfmCatalog[$item->tool_key] ?? null);
        if (! $meta) {
            return null;
        }

        $category = $row->source === 'ps'
            ? ($meta['category_label'] ?? 'Wellbeing')
            : 'Money & Finance';

        return [
            'item' => $item,
            'meta' => $meta,
            'category' => $category,
            'is_draft' => (bool) ($item->is_draft ?? false),
            'history_route' => route($meta['route'].'.history.show', $item),
            'continue_route' => route($meta['route'].'.continue', $item),
            'delete_route' => route($meta['route'].'.history.destroy', $item),
        ];
    };
@endphp

<div class="mx-auto grid max-w-7xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Customer', 'url' => route('user.dashboard')],
        ['label' => 'Dashboard'],
    ]" />

    @if ($dashboardError)
        <div class="rounded-card border border-warning bg-warning-surface px-4 py-3 text-sm text-warning" role="alert">
            {{ $dashboardError }}
        </div>
    @endif

    <section class="overflow-hidden rounded-panel border border-border bg-surface shadow-card">
        <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div class="flex items-start gap-4">
                <img
                    src="{{ $avatar }}"
                    alt="{{ optional($user)->name ?? 'Customer' }}"
                    class="size-16 rounded-full object-cover ring-2 ring-brand-100"
                    onerror="this.onerror=null;this.src='{{ $fallbackAvatar }}';"
                >
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Welcome back</p>
                    <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">
                        {{ optional($user)->name ?? 'Guest' }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">
                        Review upcoming consultations, billing, and account details from your Yuktione workspace.
                    </p>
                    <p class="mt-3 text-sm text-text-muted">
                        Profile completion: <span class="font-semibold text-text-primary">{{ $profileCompletion }}%</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <x-tazen-v2.ui.button href="{{ route('user.upcoming-appointment.index') }}" variant="outline">Upcoming appointments</x-tazen-v2.ui.button>
                <x-tazen-v2.ui.button href="{{ route('gridlisting') }}">Find an expert</x-tazen-v2.ui.button>
            </div>
        </div>
    </section>

    <section aria-labelledby="customer-metrics-heading">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 id="customer-metrics-heading" class="font-display text-section-title font-semibold text-brand-secondary">Overview</h2>
            <span class="text-xs font-medium text-text-muted">Updated {{ now()->diffForHumans() }}</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-tazen-v2.panel.metric-card
                label="Upcoming appointments"
                :value="$dashboardError ? '—' : $upcomingCount"
                :hint="$dashboardError ? 'Temporarily unavailable' : $todayCount . ' scheduled today'"
                :href="route('user.upcoming-appointment.index')"
                icon="clock"
            />
            <x-tazen-v2.panel.metric-card
                label="Total appointments"
                :value="$dashboardError ? '—' : $totalAppointments"
                :hint="$dashboardError ? 'Temporarily unavailable' : $completedAppointments . ' completed'"
                :href="route('user.all-appointment.index')"
                icon="calendar"
            />
            @if (config('yuktione.programmes_enabled'))
                <x-tazen-v2.panel.metric-card
                    label="Event bookings soon"
                    :value="$dashboardError ? '—' : $upcomingBookedEvents"
                    :hint="$dashboardError ? 'Temporarily unavailable' : 'Your bookings in the next 3 days'"
                    :href="route('user.customer-event.index')"
                    icon="spark"
                />
            @endif
            <x-tazen-v2.panel.metric-card
                label="Profile completion"
                :value="$profileCompletion . '%'"
                hint="Keep your account details up to date"
                :href="route('user.profile.index')"
                icon="user"
            />
        </div>
    </section>

    <section aria-labelledby="my-saved-tools-heading" class="overflow-hidden rounded-panel border border-border bg-surface shadow-card">
        <div class="flex flex-col gap-4 border-b border-divider bg-gradient-to-br from-[#F7FBF9] via-surface to-[#F3F6FA] px-5 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-6">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">Private workspace</p>
                <h2 id="my-saved-tools-heading" class="mt-1 font-display text-section-title font-semibold text-brand-secondary">My Saved Tools</h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-text-secondary">
                    Your latest finance, parenting, and spirituality results—saved privately on this account.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tools.pfm') }}" class="inline-flex items-center justify-center rounded-control border border-brand-primary/25 bg-surface px-3.5 py-2 text-sm font-semibold text-brand-primary transition hover:border-brand-primary hover:bg-brand-primary/5">
                    Finance tools
                </a>
                <a href="{{ route('tools.ps') }}" class="inline-flex items-center justify-center rounded-control bg-brand-primary px-3.5 py-2 text-sm font-semibold text-white transition hover:brightness-95">
                    Wellbeing tools
                </a>
            </div>
        </div>

        <div class="p-4 sm:p-6" id="my-saved-tools">
            <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Saved tools filters">
                @foreach ([
                    ['key' => 'recent', 'label' => 'Recent'],
                    ['key' => 'saved', 'label' => 'Saved'],
                    ['key' => 'drafts', 'label' => 'Drafts'],
                ] as $tab)
                    @php $isActiveTab = $savedToolsTab === $tab['key']; @endphp
                    <a
                        href="{{ route('user.dashboard', ['tools_tab' => $tab['key']]) }}#my-saved-tools"
                        class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition {{ $isActiveTab ? 'border-brand-primary bg-brand-primary text-white' : 'border-border bg-surface text-text-secondary hover:border-brand-primary/40 hover:text-brand-primary' }}"
                        role="tab"
                        aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                    >
                        {{ $tab['label'] }}
                        <span class="rounded-full px-1.5 py-0.5 text-[11px] {{ $isActiveTab ? 'bg-white/20 text-white' : 'bg-surface-muted text-text-muted' }}">{{ $savedToolsCounts[$tab['key']] }}</span>
                    </a>
                @endforeach
            </div>

            @if ($savedToolsPaginator->isEmpty())
                <div class="flex flex-col items-start gap-3 rounded-card border border-dashed border-border bg-[#FAFCFB] px-5 py-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-brand-secondary">
                            @if ($savedToolsTab === 'drafts')
                                No drafts yet
                            @elseif ($savedToolsTab === 'saved')
                                No saved results yet
                            @else
                                No recent tool activity
                            @endif
                        </p>
                        <p class="mt-1 max-w-xl text-sm leading-6 text-text-secondary">
                            Run a calculator or reflection tool, then save the result to keep it here privately.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('tools.pfm') }}" class="inline-flex rounded-control border border-border px-3 py-2 text-sm font-semibold text-brand-secondary hover:border-brand-primary hover:text-brand-primary">Open finance</a>
                        <a href="{{ route('tools.ps') }}" class="inline-flex rounded-control bg-brand-primary px-3 py-2 text-sm font-semibold text-white hover:brightness-95">Open wellbeing</a>
                    </div>
                </div>
            @else
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs text-text-muted">
                    <p>
                        Showing
                        <span class="font-semibold text-text-secondary">{{ $savedToolsPaginator->firstItem() }}–{{ $savedToolsPaginator->lastItem() }}</span>
                        of
                        <span class="font-semibold text-text-secondary">{{ $savedToolsPaginator->total() }}</span>
                    </p>
                    <p>{{ $savedToolsPerPage }} per page</p>
                </div>

                <ul class="grid gap-3">
                    @foreach ($savedToolsPaginator as $row)
                        @php $toolRow = $resolveSavedToolRow($row); @endphp
                        @continue(! $toolRow)
                        <li class="rounded-card border border-border bg-surface p-4 transition hover:border-brand-primary/35 hover:shadow-sm">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-primary/10 text-sm font-bold tracking-wide text-brand-primary" aria-hidden="true">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($toolRow['item']->title, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-semibold text-brand-secondary">{{ $toolRow['item']->title }}</h3>
                                            <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-text-muted">{{ $toolRow['category'] }}</span>
                                            @if ($toolRow['is_draft'])
                                                <span class="rounded-full bg-warning-surface px-2 py-0.5 text-[11px] font-semibold text-warning">Draft</span>
                                            @else
                                                <span class="rounded-full bg-brand-primary/10 px-2 py-0.5 text-[11px] font-semibold text-brand-primary">Saved</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm leading-6 text-text-secondary">{{ \Illuminate\Support\Str::limit($toolRow['item']->summary ?: ($toolRow['meta']['description'] ?? ''), 110) }}</p>
                                        <p class="mt-1 text-xs text-text-muted">
                                            Updated {{ optional($toolRow['item']->updated_at)->timezone('Asia/Kolkata')->format('d M Y · h:i A') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-nowrap items-center gap-2 overflow-x-auto pb-0.5 lg:justify-end">
                                    <a href="{{ $toolRow['history_route'] }}" class="inline-flex whitespace-nowrap rounded-control border border-border px-3 py-1.5 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary hover:text-brand-primary">View</a>
                                    <a href="{{ $toolRow['continue_route'] }}" class="inline-flex whitespace-nowrap rounded-control bg-brand-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:brightness-95">Edit</a>
                                    <form method="post" action="{{ $toolRow['delete_route'] }}" class="inline" onsubmit="return confirm('Delete this saved result? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex whitespace-nowrap rounded-control px-3 py-1.5 text-sm font-semibold text-text-muted transition hover:bg-red-50 hover:text-red-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($savedToolsPaginator->hasPages())
                    <div class="mt-4 flex flex-col gap-3 border-t border-divider pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-text-muted">
                            Page <span class="font-semibold text-text-secondary">{{ $savedToolsPaginator->currentPage() }}</span>
                            of <span class="font-semibold text-text-secondary">{{ $savedToolsPaginator->lastPage() }}</span>
                        </p>
                        <div class="flex flex-wrap gap-2" aria-label="Saved tools pagination">
                            @if ($savedToolsPaginator->onFirstPage())
                                <span class="inline-flex cursor-not-allowed rounded-control border border-border px-3 py-1.5 text-sm font-semibold text-text-muted opacity-50">Previous</span>
                            @else
                                <a href="{{ $savedToolsPaginator->previousPageUrl() }}#my-saved-tools" class="inline-flex rounded-control border border-border px-3 py-1.5 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary hover:text-brand-primary">Previous</a>
                            @endif

                            @if ($savedToolsPaginator->hasMorePages())
                                <a href="{{ $savedToolsPaginator->nextPageUrl() }}#my-saved-tools" class="inline-flex rounded-control bg-brand-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:brightness-95">Next</a>
                            @else
                                <span class="inline-flex cursor-not-allowed rounded-control border border-border px-3 py-1.5 text-sm font-semibold text-text-muted opacity-50">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-5">
        <x-tazen-v2.ui.card class="xl:col-span-3">
            <x-slot:heading>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-card-title font-semibold text-brand-secondary">Upcoming consultations</h2>
                    <a href="{{ route('user.upcoming-appointment.index') }}" class="text-sm font-semibold text-brand-primary hover:underline">View all</a>
                </div>
            </x-slot:heading>

            @if ($dashboardError)
                <x-tazen-v2.panel.empty-state
                    title="Appointments unavailable"
                    description="We couldn't load your upcoming appointments. Please try again later."
                    icon="calendar"
                />
            @elseif ($upcomingSlots->isEmpty())
                <x-tazen-v2.panel.empty-state
                    title="No upcoming appointments"
                    description="Book a consultation to see it listed here."
                    icon="calendar"
                >
                    <x-slot:actions>
                        <x-tazen-v2.ui.button href="{{ route('gridlisting') }}" size="sm">Find an expert</x-tazen-v2.ui.button>
                    </x-slot:actions>
                </x-tazen-v2.panel.empty-state>
            @else
                <x-tazen-v2.panel.table :headers="['Professional', 'When', 'Status', '']">
                    @foreach ($upcomingSlots->take(5) as $item)
                        @php
                            $booking = $item['booking'];
                            $slot = $item['slot'];
                            $professionalName = optional($booking->professional)->name ?? ($booking->professional_name ?? 'Professional');
                            $slotDate = $slot->date ? \Carbon\Carbon::parse($slot->date)->format('M d, Y') : 'Date unavailable';
                            $slotTime = $slot->time_slot ?? 'Time unavailable';
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ $professionalName }}</p>
                                <p class="text-xs text-text-muted">{{ $booking->service_name ?? optional($booking->service)->name ?? 'Consultation' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-text-secondary">{{ $slotDate }} · {{ $slotTime }}</td>
                            <td class="px-4 py-3"><x-tazen-v2.panel.status-badge :status="$slot->status" /></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('user.upcoming-appointment.index') }}" class="text-sm font-semibold text-brand-primary hover:underline">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </x-tazen-v2.panel.table>
            @endif
        </x-tazen-v2.ui.card>

        <x-tazen-v2.ui.card class="xl:col-span-2">
            <x-slot:heading>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-card-title font-semibold text-brand-secondary">Quick actions</h2>
                </div>
            </x-slot:heading>
            <div class="grid gap-2">
                <x-tazen-v2.ui.button href="{{ route('user.profile.index') }}" variant="outline" class="w-full justify-between">
                    Manage profile
                </x-tazen-v2.ui.button>
                <x-tazen-v2.ui.button href="{{ route('user.admin-chat.index') }}" variant="outline" class="w-full justify-between">
                    Message support
                </x-tazen-v2.ui.button>
                <x-tazen-v2.ui.button href="{{ route('user.billing.index') }}" variant="outline" class="w-full justify-between">
                    View billing
                </x-tazen-v2.ui.button>
                <x-tazen-v2.ui.button href="{{ route('user.my-bookings') }}" variant="outline" class="w-full justify-between">
                    My bookings
                </x-tazen-v2.ui.button>
                @if (config('yuktione.programmes_enabled'))
                    <x-tazen-v2.ui.button href="{{ route('user.customer-event.index') }}" variant="outline" class="w-full justify-between">
                        My events
                    </x-tazen-v2.ui.button>
                @endif
            </div>
        </x-tazen-v2.ui.card>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <x-tazen-v2.ui.card>
            <x-slot:heading>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-card-title font-semibold text-brand-secondary">Recent booking activity</h2>
                    <a href="{{ route('user.all-appointment.index') }}" class="text-sm font-semibold text-brand-primary hover:underline">History</a>
                </div>
            </x-slot:heading>

            @if ($dashboardError)
                <x-tazen-v2.panel.empty-state
                    title="Booking activity unavailable"
                    description="We couldn't load your recent booking activity. Please try again later."
                    icon="inbox"
                />
            @elseif ($recentBookings->isEmpty())
                <x-tazen-v2.panel.empty-state
                    title="No booking activity yet"
                    description="Your recent consultations will appear here after you book."
                    icon="inbox"
                />
            @else
                <ul class="grid gap-3">
                    @foreach ($recentBookings as $booking)
                        <li class="rounded-card border border-divider px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-text-primary">{{ $booking->service_name ?? optional($booking->service)->name ?? 'Consultation' }}</p>
                                    <p class="mt-1 text-sm text-text-secondary">{{ optional($booking->professional)->name ?? 'Professional' }}</p>
                                    <p class="mt-1 text-xs text-text-muted">{{ optional($booking->created_at)->diffForHumans() }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <x-tazen-v2.panel.status-badge :status="$booking->status ?? $booking->payment_status" />
                                    @if ($booking->payment_status)
                                        <x-tazen-v2.panel.status-badge :status="$booking->payment_status" />
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-tazen-v2.ui.card>

        @if (config('yuktione.programmes_enabled'))
            <x-tazen-v2.ui.card>
                <x-slot:heading>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-display text-card-title font-semibold text-brand-secondary">Upcoming programmes</h2>
                        <a href="{{ route('programmes.index') }}" class="text-sm font-semibold text-brand-primary hover:underline">Browse programmes</a>
                    </div>
                </x-slot:heading>

                @if ($dashboardError)
                    <x-tazen-v2.panel.empty-state
                        title="Programmes unavailable"
                        description="We couldn't load upcoming programmes. Please try again later."
                        icon="spark"
                    />
                @elseif ($events->isEmpty())
                    <x-tazen-v2.panel.empty-state
                        title="No upcoming programmes"
                        description="When programmes are published, they will appear here."
                        icon="spark"
                    />
                @else
                    <ul class="grid gap-3">
                        @foreach ($events as $event)
                            <li class="rounded-card border border-divider px-4 py-3">
                                <p class="font-semibold text-text-primary">{{ $event->heading }}</p>
                                <p class="mt-1 text-sm text-text-secondary">{{ \Illuminate\Support\Str::limit($event->short_description, 110) }}</p>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <x-tazen-v2.ui.badge variant="info">
                                        {{ $event->date ? \Carbon\Carbon::parse($event->date)->format('M d, Y') : 'Date unavailable' }}
                                    </x-tazen-v2.ui.badge>
                                    <span class="text-sm font-medium text-text-secondary">
                                        From {{ getCurrencySymbol() }}{{ number_format(convertCurrency($event->starting_fees ?? 0), 0) }}
                                    </span>
                                </div>
                                <a href="{{ route('event.details', $event->id) }}" class="mt-3 inline-flex text-sm font-semibold text-brand-primary hover:underline">View event</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-tazen-v2.ui.card>
        @else
            <x-tazen-v2.ui.card>
                <x-slot:heading>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-display text-card-title font-semibold text-brand-secondary">Account shortcuts</h2>
                    </div>
                </x-slot:heading>
                <div class="grid gap-2">
                    <x-tazen-v2.ui.button href="{{ route('user.additional-services.index') }}" variant="outline" class="w-full justify-between">
                        Additional services
                    </x-tazen-v2.ui.button>
                    <x-tazen-v2.ui.button href="{{ route('user.admin-chat.index') }}" variant="outline" class="w-full justify-between">
                        Message support
                    </x-tazen-v2.ui.button>
                </div>
            </x-tazen-v2.ui.card>
        @endif
    </section>
</div>
@endsection
