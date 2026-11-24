@extends('professional.layout.layout')

@section('title', 'Browse Plans | Yuktione')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6">
    <header>
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Professional subscription</p>
        <h1 class="mt-1 text-2xl font-semibold leading-tight text-brand-950">Choose Your Plan</h1>
        <p class="mt-1 text-sm text-text-muted">Explore subscription plans designed for Yuktione professionals.</p>
    </header>

    @if($currentPlan)
        <section class="flex flex-col gap-4 rounded-panel border border-brand-200 bg-brand-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-label="Current subscription">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-medium text-brand-800">Your current plan</p>
                    <x-yuktione.status-badge status="active" label="Active" />
                </div>
                <h2 class="mt-1 text-lg font-semibold text-brand-950">{{ $currentPlan->plan_name }}</h2>
                <p class="mt-1 text-xs text-text-secondary">
                    {{ $currentPlan->remaining_leads }} of {{ $currentPlan->lead_limit }} leads remaining
                    @if($currentPlan->end_date)
                        <span aria-hidden="true"> · </span>Expires {{ $currentPlan->end_date->format('d M Y') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('professional.plans.my-plan') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-brand-200 bg-white px-4 py-2 text-xs font-semibold text-brand-800 transition hover:bg-brand-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                View My Subscription
            </a>
        </section>
    @elseif($hasExpiredPlan)
        <section class="rounded-panel border border-warning/20 bg-warning-surface p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-yuktione.status-badge status="expired" label="Plan expired" />
                    <h2 class="mt-2 text-base font-semibold text-brand-950">Choose a plan to continue</h2>
                    <p class="mt-1 text-xs leading-5 text-text-secondary">Your previous subscription has expired. Review the available plans below.</p>
                </div>
                <a href="{{ route('professional.plans.my-plan') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-white px-4 py-2 text-xs font-semibold text-text-secondary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    View subscription history
                </a>
            </div>
        </section>
    @else
        <section class="rounded-panel border border-info/20 bg-info-surface p-4 sm:p-5">
            <p class="text-sm font-semibold text-brand-950">No Active Subscription</p>
            <p class="mt-1 text-xs leading-5 text-text-secondary">Choose a plan below to access the professional features available with a subscription.</p>
        </section>
    @endif

    @if($pendingRequests->isNotEmpty())
        <section class="rounded-panel border border-divider bg-white p-4 shadow-card sm:p-5" aria-labelledby="pending-subscriptions-title">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="pending-subscriptions-title" class="text-sm font-semibold text-brand-950">Pending subscription requests</h2>
                <x-yuktione.status-badge status="pending" label="Awaiting review" />
            </div>
            <p class="mt-1 text-xs leading-5 text-text-muted">Your current plan remains active until a pending request is reviewed.</p>
            <div class="mt-3 divide-y divide-divider">
                @foreach($pendingRequests as $pending)
                    <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-text-primary">{{ $pending->plan_name }}</p>
                            <p class="mt-1 text-[11px] text-text-muted">Requested {{ $pending->created_at->format('d M Y') }}</p>
                        </div>
                        <p class="shrink-0 text-xs font-medium text-text-primary">{{ $pending->formatted_price }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section aria-labelledby="available-plans-title">
        <div class="mb-3">
            <h2 id="available-plans-title" class="text-base font-semibold text-brand-950">Available plans</h2>
            <p class="mt-1 text-xs text-text-muted">Plan prices and features shown below are configured for your account.</p>
        </div>

        @if($plans->isEmpty())
            <div class="rounded-panel border border-dashed border-border bg-white px-5 py-9 text-center">
                <h3 class="text-sm font-semibold text-brand-950">
                    @if($currentPlan)
                        You’re on the highest available plan
                    @else
                        No plans are available right now
                    @endif
                </h3>
                <p class="mt-1 text-xs text-text-muted">
                    @if($currentPlan)
                        Your current subscription includes the highest plan currently available to you.
                    @else
                        Please check again later.
                    @endif
                </p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($plans as $plan)
                    <article class="flex h-full flex-col rounded-panel border border-divider bg-white p-5 shadow-card">
                        <div>
                            <h3 class="text-lg font-semibold text-brand-950">{{ $plan->name }}</h3>
                            @if($plan->description)
                                <p class="mt-1 text-xs leading-5 text-text-muted">{{ $plan->description }}</p>
                            @endif
                        </div>

                        <div class="mt-4 rounded-control bg-brand-50 px-4 py-3">
                            <p class="text-2xl font-semibold tracking-tight text-brand-800">{{ $plan->formatted_price }}</p>
                            <p class="mt-1 text-[11px] text-text-muted">
                                @if($plan->validity_days)
                                    Valid for {{ $plan->validity_days }} days
                                @else
                                    No expiry specified
                                @endif
                            </p>
                        </div>

                        <div class="mt-4 flex-1">
                            <p class="text-xs font-semibold text-text-primary">Plan includes</p>
                            <ul class="mt-2 space-y-2 text-xs leading-5 text-text-secondary">
                                <li class="flex gap-2">
                                    <span class="text-success" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.2 4.2L19 7"/></svg></span>
                                    <span>{{ $plan->lead_limit }} leads</span>
                                </li>
                                @if(is_array($plan->features))
                                    @foreach($plan->features as $feature)
                                        <li class="flex gap-2">
                                            <span class="text-success" aria-hidden="true"></span>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>

                        <a href="{{ route('professional.plans.purchase', $plan->id) }}" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            @if($currentPlan)
                                Upgrade to {{ $plan->name }}
                            @elseif($hasExpiredPlan)
                                Select {{ $plan->name }}
                            @else
                                Choose {{ $plan->name }}
                            @endif
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-panel border border-divider bg-white shadow-card" aria-labelledby="subscription-history-title">
        <div class="border-b border-divider px-4 py-3.5 sm:px-5">
            <h2 id="subscription-history-title" class="text-sm font-semibold text-brand-950">Subscription history</h2>
            <p class="mt-1 text-xs text-text-muted">Previous plan requests and their recorded payment status.</p>
        </div>
        @if($subscriptionHistory->isEmpty())
            <p class="px-4 py-7 text-center text-xs text-text-muted sm:px-5">No subscription history is available yet.</p>
        @else
            <div class="divide-y divide-divider">
                @foreach($subscriptionHistory as $history)
                    @php
                        $historyStatus = $history->isExpired()
                            ? 'expired'
                            : ($history->payment_status === 'success' ? 'active' : $history->payment_status);
                        $historyLabel = $history->isExpired()
                            ? 'Expired'
                            : ($history->payment_status === 'success' ? 'Active' : ucfirst($history->payment_status));
                    @endphp
                    <article class="grid gap-2 px-4 py-3.5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:px-5">
                        <div class="min-w-0">
                            <h3 class="truncate text-xs font-semibold text-text-primary">{{ $history->plan_name }}</h3>
                            <p class="mt-1 text-[11px] text-text-muted">
                                Requested {{ $history->created_at->format('d M Y') }}
                                @if($history->start_date)
                                    · Started {{ $history->start_date->format('d M Y') }}
                                @endif
                                @if($history->end_date)
                                    · Ends {{ $history->end_date->format('d M Y') }}
                                @endif
                            </p>
                            <p class="mt-1 break-all text-[11px] text-text-muted">
                                {{ ucfirst($history->payment_method ?? 'Payment method unavailable') }}
                                @if($history->payment_id)
                                    · Payment ID {{ $history->payment_id }}
                                @elseif($history->payment_screenshot)
                                    · <a class="font-medium text-brand-700 underline underline-offset-2" href="{{ asset('storage/'.$history->payment_screenshot) }}" target="_blank" rel="noopener noreferrer">View submitted receipt</a>
                                @endif
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-brand-950">{{ $history->formatted_price }}</p>
                        <x-yuktione.status-badge :status="$historyStatus" :label="$historyLabel" />
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
