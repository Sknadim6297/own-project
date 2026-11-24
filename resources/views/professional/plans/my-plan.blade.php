@extends('professional.layout.layout')

@section('title', 'My Subscription | Yuktione')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6">
    <header>
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Subscription</p>
        <h1 class="mt-1 text-2xl font-semibold leading-tight text-brand-950">My Subscription</h1>
        <p class="mt-1 text-sm text-text-muted">Manage your current plan, benefits and renewal information.</p>
    </header>

    @if(!$purchase)
        <section class="rounded-panel border border-divider bg-white px-5 py-10 text-center shadow-card sm:px-8" aria-labelledby="no-active-plan-title">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true">
                <svg class="size-6" viewBox="0 0 24 24" fill="none"><path d="M4.75 7.25h14.5v12H4.75v-12ZM8 7.25V5.5a4 4 0 0 1 8 0v1.75" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </div>
            <h2 id="no-active-plan-title" class="mt-4 text-lg font-semibold text-brand-950">No Active Subscription</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-text-muted">Choose a plan to access the available professional features.</p>
            <a href="{{ route('professional.plans.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-control bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                Browse Plans
            </a>
        </section>
    @else
        @php
            $displayStart = $purchase->start_date ?? ($purchase->payment_status === 'success' ? $purchase->created_at : null);
            $displayEnd = $purchase->end_date;
            $leadLimit = max(0, (int) $purchase->lead_limit);
            $leadsUsed = max(0, (int) $purchase->leads_used);
            $usagePercent = $leadLimit > 0 ? min(100, ($leadsUsed / $leadLimit) * 100) : 0;
        @endphp

        @if($purchase->isExpired())
            <div class="rounded-panel border border-warning/20 bg-warning-surface px-4 py-3 text-sm text-warning">
                This plan expired{{ $purchase->end_date ? ' on '.$purchase->end_date->format('d M Y') : '' }}.
            </div>
        @elseif($purchase->payment_status !== 'success')
            <div class="rounded-panel border border-warning/20 bg-warning-surface px-4 py-3 text-sm text-warning">
                Your payment is pending approval.
            </div>
        @endif

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(16rem,.85fr)]">
            <article class="rounded-panel border border-divider bg-white p-5 shadow-card sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-text-muted">Current plan</p>
                        <h2 class="mt-1 text-xl font-semibold text-brand-950">{{ $purchase->plan_name }}</h2>
                    </div>
                    <x-yuktione.status-badge :status="$purchase->isExpired() ? 'expired' : $purchase->payment_status" :label="$purchase->isExpired() ? 'Expired' : ucfirst($purchase->payment_status)" />
                </div>
                <p class="mt-4 text-2xl font-semibold text-brand-800">{{ $purchase->formatted_price }}</p>
                @if($purchase->plan?->validity_days)
                    <p class="mt-1 text-xs text-text-muted">Plan validity: {{ $purchase->plan->validity_days }} days</p>
                @endif

                @if($leadLimit > 0)
                    <div class="mt-5 border-t border-divider pt-4">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-xs font-semibold text-text-primary">Lead usage</h3>
                            <p class="text-xs text-text-muted">{{ $leadsUsed }} of {{ $leadLimit }} used</p>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-100" role="progressbar" aria-label="Lead usage" aria-valuemin="0" aria-valuemax="{{ $leadLimit }}" aria-valuenow="{{ min($leadsUsed, $leadLimit) }}">
                            <div class="h-full rounded-full bg-brand-primary" style="width: {{ $usagePercent }}%"></div>
                        </div>
                        <p class="mt-1.5 text-[11px] text-text-muted">{{ $purchase->remaining_leads }} leads remaining</p>
                    </div>
                @endif
            </article>

            <article class="rounded-panel border border-divider bg-white p-5 shadow-card sm:p-6">
                <h2 class="text-sm font-semibold text-brand-950">Subscription details</h2>
                <dl class="mt-3 divide-y divide-divider text-xs">
                    <div class="flex justify-between gap-3 py-2.5">
                        <dt class="text-text-muted">Purchase date</dt>
                        <dd class="text-right font-medium text-text-primary">{{ $purchase->created_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    @if($displayStart)
                        <div class="flex justify-between gap-3 py-2.5">
                            <dt class="text-text-muted">Start date</dt>
                            <dd class="text-right font-medium text-text-primary">{{ $displayStart->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if($displayEnd)
                        <div class="flex justify-between gap-3 py-2.5">
                            <dt class="text-text-muted">{{ $displayEnd->isFuture() ? 'Renewal / expiry' : 'Expired' }}</dt>
                            <dd class="text-right font-medium text-text-primary">{{ $displayEnd->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if($purchase->payment_method)
                        <div class="flex justify-between gap-3 py-2.5">
                            <dt class="text-text-muted">Payment method</dt>
                            <dd class="text-right font-medium text-text-primary">{{ ucfirst($purchase->payment_method) }}</dd>
                        </div>
                    @endif
                    @if($purchase->payment_id)
                        <div class="flex justify-between gap-3 py-2.5">
                            <dt class="text-text-muted">Payment ID</dt>
                            <dd class="max-w-[60%] break-all text-right font-medium text-text-primary">{{ $purchase->payment_id }}</dd>
                        </div>
                    @endif
                </dl>
            </article>
        </section>

        @if(is_array($purchase->features) && count($purchase->features))
            <section class="rounded-panel border border-divider bg-white p-5 shadow-card sm:p-6" aria-labelledby="plan-benefits-title">
                <h2 id="plan-benefits-title" class="text-sm font-semibold text-brand-950">Included features</h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach($purchase->features as $feature)
                        <li class="flex gap-2 text-xs leading-5 text-text-secondary">
                            <span class="font-semibold text-success" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.2 4.2L19 7"/></svg></span>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($purchase->admin_notes)
            <section class="rounded-panel border border-info/20 bg-info-surface p-4 text-xs leading-5 text-text-secondary">
                <h2 class="font-semibold text-brand-950">Account note</h2>
                <p class="mt-1">{{ $purchase->admin_notes }}</p>
            </section>
        @endif

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('professional.plans.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-control bg-brand-primary px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Browse Plans</a>
            <a href="{{ route('professional.dashboard') }}" class="inline-flex min-h-10 items-center justify-center rounded-control border border-border bg-white px-4 py-2 text-xs font-semibold text-text-secondary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Dashboard</a>
        </div>
    @endif

    @if($pendingRequests->isNotEmpty())
        <section class="overflow-hidden rounded-panel border border-divider bg-white shadow-card" aria-labelledby="pending-requests-title">
            <div class="border-b border-divider px-4 py-3.5 sm:px-5">
                <h2 id="pending-requests-title" class="text-sm font-semibold text-brand-950">Pending subscription requests</h2>
                <p class="mt-1 text-xs text-text-muted">These requests are awaiting review; a plan is not activated until the existing approval/payment flow confirms it.</p>
            </div>
            <div class="divide-y divide-divider">
                @foreach($pendingRequests as $pending)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-5">
                        <div>
                            <p class="text-xs font-semibold text-text-primary">{{ $pending->plan_name }}</p>
                            <p class="mt-1 text-[11px] text-text-muted">Requested {{ $pending->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-text-primary">{{ $pending->formatted_price }}</span>
                            <x-yuktione.status-badge status="pending" label="Pending" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($subscriptionHistory->isNotEmpty())
        <section class="overflow-hidden rounded-panel border border-divider bg-white shadow-card" aria-labelledby="subscription-history-title">
            <div class="border-b border-divider px-4 py-3.5 sm:px-5">
                <h2 id="subscription-history-title" class="text-sm font-semibold text-brand-950">Subscription history</h2>
            </div>
            <div class="divide-y divide-divider">
                @foreach($subscriptionHistory as $history)
                    @php
                        $historyStatus = $history->isExpired() ? 'expired' : ($history->payment_status === 'success' ? 'active' : $history->payment_status);
                        $historyLabel = $history->isExpired() ? 'Expired' : ($history->payment_status === 'success' ? 'Active' : ucfirst($history->payment_status));
                    @endphp
                    <div class="grid gap-2 px-4 py-3.5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:px-5">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-text-primary">{{ $history->plan_name }}</p>
                            <p class="mt-1 text-[11px] text-text-muted">
                                {{ $history->created_at->format('d M Y') }}
                                @if($history->start_date) · Starts {{ $history->start_date->format('d M Y') }} @endif
                                @if($history->end_date) · Ends {{ $history->end_date->format('d M Y') }} @endif
                            </p>
                        </div>
                        <p class="text-xs font-medium text-brand-950">{{ $history->formatted_price }}</p>
                        <x-yuktione.status-badge :status="$historyStatus" :label="$historyLabel" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
