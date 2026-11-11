@extends('admin.layouts.layout')
@include('admin.yukti-points._styles')

@section('content')
<div class="main-content app-content yukti-admin">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="page-title fw-medium fs-18 mb-2">YuktiPoints Dashboard</h1>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">YuktiPoints</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="yukti-status {{ $data['engine_enabled'] ? 'yukti-status--on' : 'yukti-status--off' }}">
                    Engine {{ $data['engine_enabled'] ? 'enabled' : 'disabled' }}
                </span>
                <span class="yukti-status {{ $data['redemption_enabled'] ? 'yukti-status--on' : 'yukti-status--off' }}">
                    Redemption {{ $data['redemption_enabled'] ? 'enabled' : 'disabled' }}
                </span>
            </div>
        </div>

        <div class="yukti-metric-grid mb-4">
            <div class="yukti-metric"><div class="yukti-metric__label">Customer wallets</div><div class="yukti-metric__value">{{ $data['wallet_count'] }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Available points</div><div class="yukti-metric__value">{{ number_format($data['available_points']) }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Pending points</div><div class="yukti-metric__value">{{ number_format($data['pending_points']) }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Total credited</div><div class="yukti-metric__value">{{ number_format($data['credited_points']) }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Total debited</div><div class="yukti-metric__value">{{ number_format($data['debited_points']) }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Fraud flags</div><div class="yukti-metric__value">{{ $data['fraud_flag_count'] }}</div></div>
        </div>

        <div class="yukti-metric-grid mb-4">
            <div class="yukti-metric"><div class="yukti-metric__label">Joined referrals</div><div class="yukti-metric__value">{{ $data['referrals']['joined'] }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Activated (not spendable)</div><div class="yukti-metric__value">{{ $data['referrals']['activated'] }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Rewarded referrals</div><div class="yukti-metric__value">{{ $data['referrals']['rewarded'] }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Rejected referrals</div><div class="yukti-metric__value">{{ $data['referrals']['rejected'] }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Pending activation (joined)</div><div class="yukti-metric__value">{{ $data['referrals']['pending_activation'] }}</div></div>
            <div class="yukti-metric"><div class="yukti-metric__label">Current week</div><div class="yukti-metric__value">{{ $data['current_week_id'] }}</div></div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title">Redemption readiness</div></div>
            <div class="card-body">
                <div class="yukti-note">
                    <p class="mb-2"><strong>Redemption is disabled.</strong> No rupee conversion is shown because the rate is not approved.</p>
                    <ul class="mb-0 small">
                        <li>Conversion rate: pending approval</li>
                        <li>Eligible offerings: pending</li>
                        <li>Minimum threshold: pending</li>
                        <li>Refund / clawback policy: pending (bookings lack a refunded payment state)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="card-title mb-0">Recent reward transactions</div>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.yukti-points.ledger') }}">View ledger</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Direction</th>
                                <th>Points</th>
                                <th>Event</th>
                                <th>Week</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['recent_transactions'] as $row)
                                <tr>
                                    <td>{{ $row['id'] }}</td>
                                    <td>
                                        <div>{{ $row['user_name'] ?? '—' }}</div>
                                        <div class="small text-muted">{{ $row['user_email'] ?? ('#'.$row['user_id']) }}</div>
                                    </td>
                                    <td><span class="yukti-status yukti-status--{{ $row['direction'] }}">{{ $row['direction'] }}</span></td>
                                    <td>{{ number_format($row['points']) }}</td>
                                    <td>{{ $row['event_type'] }}</td>
                                    <td>{{ $row['week_id'] ?? '—' }}</td>
                                    <td class="small">{{ $row['rewarded_at'] ?? $row['created_at'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">No reward transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
