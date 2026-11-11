@extends('layouts.layout')

@section('title', 'Refer & Earn · YuktiOne')

@section('content')
@php
    $hub = $hub ?? [];
    $summary = $hub['summary'] ?? [];
    $engineOn = (bool) ($hub['engine_enabled'] ?? false);
    $share = $hub['share'] ?? [];
    $weeklyCards = $hub['weekly_cards'] ?? [];
    $recent = $hub['recent_activity'] ?? [];
    $referrals = $hub['referrals'] ?? [];
@endphp

<section class="yk-rewards" data-yk-rewards>
    <div class="yk-rewards__wrap">
        <nav class="yk-rewards__tabs" aria-label="Rewards sections">
            <a href="{{ route('user.yukti-points.index') }}" aria-current="page">Rewards hub</a>
            <a href="{{ route('user.yukti-points.wallet') }}">Wallet</a>
            <a href="{{ route('user.yukti-points.activity') }}">Referrals</a>
        </nav>

        @unless ($engineOn)
            <div class="yk-rewards__banner" role="status">
                <strong>Rewards are not currently active.</strong>
                You can explore your wallet and share referral links, but YuktiPoints will not be awarded until rewards go live.
            </div>
        @endunless

        <div class="yk-rewards__hero">
            <div class="yk-rewards__hero-main">
                <p class="yk-rewards__eyebrow">Refer &amp; Earn</p>
                <h1 class="yk-rewards__hello">Welcome back, {{ $hub['customer']['first_name'] ?? 'there' }}</h1>
                <p class="yk-rewards__balance">
                    {{ number_format((int) ($summary['available_points'] ?? 0)) }}
                    <span>Available YuktiPoints</span>
                </p>
                <div class="yk-rewards__hero-stats">
                    <div class="yk-rewards__stat">
                        <b>{{ number_format((int) ($summary['pending_points'] ?? 0)) }}</b>
                        <span>Pending (wallet)</span>
                    </div>
                    <div class="yk-rewards__stat">
                        <b>{{ number_format((int) ($summary['total_earned_points'] ?? 0)) }}</b>
                        <span>Total earned</span>
                    </div>
                </div>
                <div class="yk-rewards__actions">
                    <a class="yk-rewards__btn yk-rewards__btn--gold" href="#yk-share">Share &amp; invite</a>
                    <a class="yk-rewards__btn yk-rewards__btn--ghost" href="{{ route('user.yukti-points.wallet') }}" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.2)">View wallet</a>
                </div>
            </div>

            <div class="yk-rewards__hero-side">
                <div class="yk-rewards__panel">
                    <h2>This week</h2>
                    <p class="yk-rewards__muted">{{ $hub['weekly']['week_id'] ?? '—' }} · {{ $hub['weekly']['timezone'] ?? 'Asia/Kolkata' }}</p>
                    <div class="yk-rewards__actions">
                        <a class="yk-rewards__btn yk-rewards__btn--ghost" href="{{ $hub['routes']['tools'] ?? route('tools.index') }}">Explore tools</a>
                        <a class="yk-rewards__btn yk-rewards__btn--ghost" href="{{ $hub['routes']['experts'] ?? route('professionals') }}">Find experts</a>
                    </div>
                </div>
                <div class="yk-rewards__panel">
                    <h2>Referral snapshot</h2>
                    <p class="yk-rewards__muted">
                        Activated estimate:
                        <strong>{{ number_format((int) ($referrals['activated_estimate_points'] ?? 0)) }}</strong>
                        pts
                    </p>
                    <p class="yk-rewards__muted" style="margin-top:.4rem">{{ $referrals['activated_estimate_label'] ?? 'Estimates are not spendable until ledger-backed.' }}</p>
                    <div class="yk-rewards__actions">
                        <a class="yk-rewards__btn yk-rewards__btn--primary" href="{{ route('user.yukti-points.activity') }}">Referral activity</a>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="yk-rewards__section-title">Weekly activities</h2>
        <div class="yk-rewards__grid">
            @forelse ($weeklyCards as $card)
                @php
                    $cap = $card['cap_count'];
                    $count = (int) $card['count'];
                    $pct = $cap ? min(100, (int) round(($count / max(1, (int) $cap)) * 100)) : ($card['earned'] ? 100 : 0);
                    $pill = ! $engineOn ? 'off' : ($card['earned'] ? 'done' : ($card['remaining_count'] === 0 ? 'wait' : 'ok'));
                @endphp
                <article class="yk-rewards__card">
                    <div class="yk-rewards__card-top">
                        <div>
                            <h3 style="margin:0;font-size:1rem">{{ $card['title'] }}</h3>
                            <p class="yk-rewards__muted" style="margin-top:.3rem">{{ $card['blurb'] }}</p>
                        </div>
                        <span class="yk-rewards__pill yk-rewards__pill--{{ $pill }}">{{ $card['status_label'] }}</span>
                    </div>
                    <p style="margin:.35rem 0 0;font-weight:700">
                        {{ $card['points_per_event'] !== null ? $card['points_per_event'].' pts' : '—' }}
                        @if ($card['cap_count'] !== null)
                            <span class="yk-rewards__muted" style="font-weight:500"> · up to {{ $card['cap_count'] }}/week</span>
                        @endif
                    </p>
                    <div class="yk-rewards__progress" data-yk-progress="{{ $pct }}" aria-hidden="true"><span></span></div>
                    <div class="yk-rewards__meta">
                        <span>{{ $count }}{{ $cap !== null ? ' / '.$cap : '' }} this week</span>
                        <span>{{ (int) $card['points_awarded'] }} pts recorded</span>
                    </div>
                    @if ($card['cta_label'] && $card['cta_url'])
                        <div class="yk-rewards__actions">
                            <a class="yk-rewards__btn yk-rewards__btn--ghost" href="{{ $card['cta_url'] }}">{{ $card['cta_label'] }}</a>
                        </div>
                    @endif
                </article>
            @empty
                <div class="yk-rewards__card yk-rewards__empty">Weekly activity will appear once reward configs are available.</div>
            @endforelse
        </div>

        <div class="yk-rewards__share" id="yk-share">
            <div class="yk-rewards__panel">
                <h2>General invite</h2>
                <p class="yk-rewards__muted">Share your personal YuktiOne link. Friends who join are attributed to you.</p>
                <div class="yk-rewards__linkbox">
                    <input type="text" readonly value="{{ $share['general_url'] ?? '' }}" aria-label="General referral link" data-yk-url-field>
                    <button type="button" class="yk-rewards__btn yk-rewards__btn--primary" data-yk-action="copy" data-yk-url="{{ $share['general_url'] ?? '' }}">Copy</button>
                </div>
                <div class="yk-rewards__actions">
                    <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost" data-yk-action="whatsapp" data-yk-url="{{ $share['general_url'] ?? '' }}" data-yk-text="Join me on YuktiOne">WhatsApp</button>
                    <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost" data-yk-action="native-share" data-yk-url="{{ $share['general_url'] ?? '' }}" data-yk-title="YuktiOne" data-yk-text="Join me on YuktiOne">Share</button>
                </div>
                <p class="yk-rewards__live" data-yk-live role="status" aria-live="polite"></p>
                <p class="yk-rewards__muted" style="margin-top:.75rem">Your code: <strong>{{ $share['code'] ?? '—' }}</strong></p>
            </div>

            <div class="yk-rewards__panel">
                <h2>Refer a tool or expert</h2>
                <p class="yk-rewards__muted">Selectors only include eligible, publicly available records.</p>

                <div id="yk-share-tool" data-yk-share-group>
                    <label class="yk-rewards__muted" for="yk-tool-select">Refer a tool</label>
                    <select id="yk-tool-select" class="yk-rewards__select" data-yk-select-target>
                        <option value="" data-url="">Select an eligible tool</option>
                        @foreach (($share['tools'] ?? []) as $tool)
                            <option value="{{ $tool['slug'] }}" data-url="{{ $tool['url'] }}">{{ $tool['name'] }}</option>
                        @endforeach
                    </select>
                    <div class="yk-rewards__linkbox">
                        <input type="text" readonly value="" aria-label="Tool referral link" data-yk-url-field>
                        <button type="button" class="yk-rewards__btn yk-rewards__btn--primary is-disabled" aria-disabled="true" data-yk-action="copy" data-yk-url="">Copy</button>
                    </div>
                    <div class="yk-rewards__actions">
                        <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost is-disabled" aria-disabled="true" data-yk-action="whatsapp" data-yk-url="" data-yk-text="Try this YuktiOne tool with me">WhatsApp</button>
                        <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost is-disabled" aria-disabled="true" data-yk-action="native-share" data-yk-url="" data-yk-title="YuktiOne tool" data-yk-text="Try this YuktiOne tool with me">Share</button>
                    </div>
                </div>

                <div id="yk-share-expert" data-yk-share-group style="margin-top:1rem">
                    <label class="yk-rewards__muted" for="yk-expert-select">Refer an expert</label>
                    <select id="yk-expert-select" class="yk-rewards__select" data-yk-select-target>
                        <option value="" data-url="">Select an expert</option>
                        @foreach (($share['experts'] ?? []) as $expert)
                            <option value="{{ $expert['id'] }}" data-url="{{ $expert['url'] }}">{{ $expert['name'] }}</option>
                        @endforeach
                    </select>
                    <div class="yk-rewards__linkbox">
                        <input type="text" readonly value="" aria-label="Expert referral link" data-yk-url-field>
                        <button type="button" class="yk-rewards__btn yk-rewards__btn--primary is-disabled" aria-disabled="true" data-yk-action="copy" data-yk-url="">Copy</button>
                    </div>
                    <div class="yk-rewards__actions">
                        <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost is-disabled" aria-disabled="true" data-yk-action="whatsapp" data-yk-url="" data-yk-text="Book this YuktiOne expert with me">WhatsApp</button>
                        <button type="button" class="yk-rewards__btn yk-rewards__btn--ghost is-disabled" aria-disabled="true" data-yk-action="native-share" data-yk-url="" data-yk-title="YuktiOne expert" data-yk-text="Book this YuktiOne expert with me">Share</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="yk-rewards__panel" style="margin-bottom:1.25rem">
            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
                <h2 style="margin:0">Recent activity</h2>
                <a class="yk-rewards__btn yk-rewards__btn--ghost" href="{{ route('user.yukti-points.wallet') }}">Full history</a>
            </div>
            <div class="yk-rewards__table-wrap" style="margin-top:.75rem">
                <table class="yk-rewards__table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Event</th>
                            <th>Points</th>
                            <th>Direction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $row)
                            <tr>
                                <td>{{ $row['rewarded_at'] ?? $row['created_at'] ?? '—' }}</td>
                                <td>{{ $row['event_type'] }}</td>
                                <td>{{ number_format((int) $row['points']) }}</td>
                                <td>{{ $row['direction'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="yk-rewards__empty">No ledger activity yet. Explore tools or share your link when you are ready.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="yk-rewards__note">
            <strong>Redemption is not available yet.</strong>
            {{ $summary['redemption']['message'] ?? 'Wallet debits and offers will appear here after business approval — no invented conversion rates.' }}
        </div>
    </div>
</section>
@endsection
