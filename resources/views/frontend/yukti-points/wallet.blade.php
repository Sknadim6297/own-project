@extends('layouts.layout')

@section('title', 'YuktiPoints Wallet · YuktiOne')

@section('content')
@php
    $wallet = $wallet ?? [];
    $summary = $wallet['summary'] ?? [];
    $history = $wallet['history'] ?? null;
    $engineOn = (bool) ($wallet['engine_enabled'] ?? false);
@endphp

<section class="yk-rewards" data-yk-rewards>
    <div class="yk-rewards__wrap">
        <nav class="yk-rewards__tabs" aria-label="Rewards sections">
            <a href="{{ route('user.yukti-points.index') }}">Rewards hub</a>
            <a href="{{ route('user.yukti-points.wallet') }}" aria-current="page">Wallet</a>
            <a href="{{ route('user.yukti-points.activity') }}">Referrals</a>
        </nav>

        @unless ($engineOn)
            <div class="yk-rewards__banner" role="status">
                <strong>Rewards are not currently active.</strong>
                Balances below reflect the ledger only — new awards will not credit until the engine is enabled.
            </div>
        @endunless

        <div class="yk-rewards__hero" style="grid-template-columns:1fr">
            <div class="yk-rewards__hero-main">
                <p class="yk-rewards__eyebrow">Wallet</p>
                <h1 class="yk-rewards__hello">Your YuktiPoints</h1>
                <p class="yk-rewards__balance">
                    {{ number_format((int) ($summary['available_points'] ?? 0)) }}
                    <span>Available to spend when redemption opens</span>
                </p>
                <div class="yk-rewards__hero-stats">
                    <div class="yk-rewards__stat"><b>{{ number_format((int) ($summary['pending_points'] ?? 0)) }}</b><span>Pending</span></div>
                    <div class="yk-rewards__stat"><b>{{ number_format((int) ($summary['total_earned_points'] ?? 0)) }}</b><span>Total earned</span></div>
                    <div class="yk-rewards__stat"><b>{{ number_format((int) ($summary['total_redeemed_points'] ?? 0)) }}</b><span>Total redeemed</span></div>
                </div>
            </div>
        </div>

        <div class="yk-rewards__note" style="margin-bottom:1.25rem">
            <strong>Redemption is disabled.</strong>
            No redeem button is offered. Conversion rates and offerings remain pending approval.
        </div>

        <div class="yk-rewards__panel">
            <h2>Transaction history</h2>
            <p class="yk-rewards__muted">Server ledger only — balances are never calculated in the browser.</p>
            <div class="yk-rewards__table-wrap" style="margin-top:.75rem">
                <table class="yk-rewards__table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Event</th>
                            <th>Points</th>
                            <th>Direction</th>
                            <th>After (A / P)</th>
                            <th>Week</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($history?->items() ?? []) as $row)
                            <tr>
                                <td>{{ $row['rewarded_at'] ?? $row['created_at'] ?? '—' }}</td>
                                <td>{{ $row['event_type'] }}</td>
                                <td>{{ number_format((int) $row['points']) }}</td>
                                <td>{{ $row['direction'] }}</td>
                                <td>{{ $row['available_after'] ?? '—' }} / {{ $row['pending_after'] ?? '—' }}</td>
                                <td>{{ $row['week_id'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="yk-rewards__empty">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($history && method_exists($history, 'hasPages') && $history->hasPages())
                <div class="yk-rewards__pager">{{ $history->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
