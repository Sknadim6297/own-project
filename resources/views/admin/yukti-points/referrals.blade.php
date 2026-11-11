@extends('admin.layouts.layout')
@include('admin.yukti-points._styles')

@section('content')
<div class="main-content app-content yukti-admin">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb">
            <h1 class="page-title fw-medium fs-18 mb-2">Referral Monitoring</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.yukti-points.dashboard') }}">YuktiPoints</a></li>
                    <li class="breadcrumb-item active">Referrals</li>
                </ol>
            </nav>
        </div>

        <p class="small text-muted mb-3">Activated referrals are not spendable until status is rewarded and a ledger credit exists.</p>

        <div class="card custom-card">
            <div class="card-header">
                <form class="row g-2 align-items-end" method="get" action="{{ route('admin.yukti-points.referrals') }}">
                    <div class="col-md-4">
                        <label class="form-label" for="ref-q">Search</label>
                        <input id="ref-q" class="form-control" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="code, referrer, referee">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="ref-status">Status</label>
                        <select id="ref-status" class="form-select" name="status">
                            <option value="">All</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="ref-type">Type</label>
                        <select id="ref-type" class="form-select" name="referral_type">
                            <option value="">All</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(($filters['referral_type'] ?? null) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Referrer</th>
                                <th>Referee</th>
                                <th>Target</th>
                                <th>Joined</th>
                                <th>Activated</th>
                                <th>Rewarded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->referral_type }}</td>
                                    <td><span class="yukti-status yukti-status--{{ $row->status }}">{{ $row->status }}</span></td>
                                    <td>
                                        <div>{{ $row->referrer->name ?? '—' }}</div>
                                        <div class="small text-muted">{{ $row->referrer->email ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $row->referee->name ?? '—' }}</div>
                                        <div class="small text-muted">{{ $row->referee->email ?? '' }}</div>
                                    </td>
                                    <td class="small">
                                        {{ $row->target_type ?? '—' }}
                                        @if ($row->target_id) #{{ $row->target_id }} @endif
                                    </td>
                                    <td class="small">{{ optional($row->signed_up_at)->toDateTimeString() ?? '—' }}</td>
                                    <td class="small">{{ optional($row->activated_at)->toDateTimeString() ?? '—' }}</td>
                                    <td class="small">{{ optional($row->rewarded_at)->toDateTimeString() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-4 text-muted">No referrals match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($rows->hasPages())
                <div class="card-footer">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
