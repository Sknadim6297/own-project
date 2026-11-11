@extends('layouts.layout')

@section('title', 'Referral Activity · YuktiOne')

@section('content')
@php
    $page = $page ?? [];
    $rows = $page['rows'] ?? [];
    $filters = $page['filters'] ?? [];
    $engineOn = (bool) ($page['engine_enabled'] ?? false);
    $share = $page['share'] ?? [];
@endphp

<section class="yk-rewards" data-yk-rewards>
    <div class="yk-rewards__wrap">
        <nav class="yk-rewards__tabs" aria-label="Rewards sections">
            <a href="{{ route('user.yukti-points.index') }}">Rewards hub</a>
            <a href="{{ route('user.yukti-points.wallet') }}">Wallet</a>
            <a href="{{ route('user.yukti-points.activity') }}" aria-current="page">Referrals</a>
        </nav>

        @unless ($engineOn)
            <div class="yk-rewards__banner" role="status">
                <strong>Rewards are not currently active.</strong>
                Referral links still work for attribution, but points will not credit until go-live.
            </div>
        @endunless

        <div class="yk-rewards__panel" style="margin-bottom:1rem">
            <h2>Lifecycle</h2>
            <p class="yk-rewards__muted">Invited → Joined → Activated → Rewarded. Activated estimates are not spendable.</p>
            <div class="yk-rewards__actions">
                <button type="button" class="yk-rewards__btn yk-rewards__btn--primary" data-yk-action="copy" data-yk-url="{{ $share['general_url'] ?? '' }}">Copy invite link</button>
                <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost" data-yk-action="whatsapp" data-yk-url="{{ $share['general_url'] ?? '' }}" data-yk-text="Join me on YuktiOne">WhatsApp</button>
                <a class="yk-rewards__btn yk-rewards__btn--ghost" href="{{ route('user.yukti-points.index') }}#yk-share">Refer tool / expert</a>
            </div>
            <p class="yk-rewards__live" data-yk-live role="status" aria-live="polite"></p>
        </div>

        <div class="yk-rewards__panel">
            <form method="get" action="{{ route('user.yukti-points.activity') }}" class="yk-rewards__actions" style="align-items:end">
                <div style="flex:1;min-width:140px">
                    <label class="yk-rewards__muted" for="yk-status">Status</label>
                    <select id="yk-status" name="status" class="yk-rewards__select">
                        <option value="">All statuses</option>
                        @foreach (['invited','joined','activated','rewarded','rejected'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1;min-width:140px">
                    <label class="yk-rewards__muted" for="yk-type">Type</label>
                    <select id="yk-type" name="referral_type" class="yk-rewards__select">
                        <option value="">All types</option>
                        @foreach (['general','tool','expert'] as $type)
                            <option value="{{ $type }}" @selected(($filters['referral_type'] ?? null) === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="yk-rewards__btn yk-rewards__btn--primary" type="submit">Filter</button>
            </form>

            <div class="yk-rewards__table-wrap" style="margin-top:1rem">
                <table class="yk-rewards__table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Target</th>
                            <th>Joined</th>
                            <th>Activated</th>
                            <th>Rewarded</th>
                            <th>Estimate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['referral_type'] }}</td>
                                <td><span class="yk-rewards__status yk-rewards__status--{{ $row['status'] }}">{{ $row['status'] }}</span></td>
                                <td>
                                    {{ $row['target_type'] ?? '—' }}
                                    @if (!empty($row['target_id'])) #{{ $row['target_id'] }} @endif
                                </td>
                                <td>{{ $row['signed_up_at'] ?? '—' }}</td>
                                <td>{{ $row['activated_at'] ?? '—' }}</td>
                                <td>{{ $row['rewarded_at'] ?? '—' }}</td>
                                <td>
                                    @if ($row['status'] === 'activated' && $row['estimate_points'] !== null)
                                        {{ $row['estimate_points'] }} pts
                                        <div class="yk-rewards__muted">{{ $row['estimate_label'] }}</div>
                                    @elseif ($row['status'] === 'rewarded')
                                        Ledger-backed
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="yk-rewards__empty">
                                    No referrals yet.
                                    <div class="yk-rewards__actions" style="justify-content:center;margin-top:.75rem">
                                        <a class="yk-rewards__btn yk-rewards__btn--primary" href="{{ route('user.yukti-points.index') }}#yk-share">Share your link</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
