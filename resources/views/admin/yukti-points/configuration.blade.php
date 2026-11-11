@extends('admin.layouts.layout')
@include('admin.yukti-points._styles')

@section('content')
<div class="main-content app-content yukti-admin">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb">
            <h1 class="page-title fw-medium fs-18 mb-2">Reward Configuration</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.yukti-points.dashboard') }}">YuktiPoints</a></li>
                    <li class="breadcrumb-item active">Configuration</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="yukti-status {{ $data['engine_enabled'] ? 'yukti-status--on' : 'yukti-status--off' }}">
                Engine {{ $data['engine_enabled'] ? 'enabled' : 'disabled' }}
            </span>
            <span class="yukti-status {{ $data['redemption_enabled'] ? 'yukti-status--on' : 'yukti-status--off' }}">
                Redemption {{ $data['redemption_enabled'] ? 'enabled' : 'disabled' }}
            </span>
            <span class="badge bg-light text-dark">Timezone: {{ $data['week_timezone'] }}</span>
            <span class="badge bg-light text-dark">Referral accounting week: {{ $data['referral_accounting_week'] }}</span>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title">Approved reward amounts (read-only)</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Reward</th>
                                <th>Points</th>
                                <th>Weekly cap (count)</th>
                                <th>Weekly cap (points)</th>
                                <th>Active</th>
                                <th>Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['rewards'] as $reward)
                                <tr>
                                    <td>{{ $reward['label'] }} <span class="small text-muted">({{ $reward['key'] }})</span></td>
                                    <td>{{ $reward['points'] ?? '—' }}</td>
                                    <td>{{ $reward['weekly_cap_count'] ?? '—' }}</td>
                                    <td>{{ $reward['weekly_cap_points'] ?? '—' }}</td>
                                    <td>{{ $reward['is_active'] ? 'yes' : 'no' }}</td>
                                    <td>{{ $reward['approval_status'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">No reward configs found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title">Eligible tool completion / referral tools</div></div>
            <div class="card-body">
                @if (empty($data['eligible_tool_completion_slugs']))
                    <p class="text-muted mb-0">No eligible tools configured.</p>
                @else
                    <ul class="mb-0">
                        @foreach ($data['eligible_tool_completion_slugs'] as $slug)
                            <li class="yukti-mono">{{ $slug }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title">Unresolved business rules</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Approval</th>
                                <th>Active</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['unresolved_business_rules'] as $rule)
                                <tr>
                                    <td class="yukti-mono">{{ $rule['key'] }}</td>
                                    <td>{{ $rule['approval_status'] }}</td>
                                    <td>{{ $rule['is_active'] ? 'yes' : 'no' }}</td>
                                    <td>{{ $rule['note'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">No pending business rules.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Redemption readiness</div></div>
            <div class="card-body">
                <div class="yukti-note">
                    <p class="mb-2">Redemption processing is not available. Conversion rate, offerings, minimum threshold, and refund policy remain pending approval.</p>
                    <p class="mb-0 small text-muted">No fictional rupee equivalent is displayed.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
