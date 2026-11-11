@extends('admin.layouts.layout')
@include('admin.yukti-points._styles')

@section('content')
<div class="main-content app-content yukti-admin">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb">
            <h1 class="page-title fw-medium fs-18 mb-2">Reward Ledger</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.yukti-points.dashboard') }}">YuktiPoints</a></li>
                    <li class="breadcrumb-item active">Ledger</li>
                </ol>
            </nav>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <form class="row g-2 align-items-end" method="get" action="{{ route('admin.yukti-points.ledger') }}">
                    <div class="col-md-3">
                        <label class="form-label" for="ledger-q">Search</label>
                        <input id="ledger-q" class="form-control" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120" placeholder="email, event, idempotency">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="ledger-user">User ID</label>
                        <input id="ledger-user" class="form-control" type="number" min="1" name="user_id" value="{{ $filters['user_id'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="ledger-event">Event</label>
                        <select id="ledger-event" class="form-select" name="event_type">
                            <option value="">All</option>
                            @foreach ($eventTypes as $type)
                                <option value="{{ $type }}" @selected(($filters['event_type'] ?? null) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="ledger-dir">Direction</label>
                        <select id="ledger-dir" class="form-select" name="direction">
                            <option value="">All</option>
                            <option value="credit" @selected(($filters['direction'] ?? null) === 'credit')>credit</option>
                            <option value="debit" @selected(($filters['direction'] ?? null) === 'debit')>debit</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="ledger-week">Week</label>
                        <input id="ledger-week" class="form-control" type="text" name="week_id" value="{{ $filters['week_id'] ?? '' }}" maxlength="16" placeholder="2026-W46">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100" type="submit">Go</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Dir</th>
                                <th>Points</th>
                                <th>Event</th>
                                <th>Balances after</th>
                                <th>Week</th>
                                <th>Idempotency</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row['id'] }}</td>
                                    <td>
                                        <div>{{ $row['user_name'] ?? '—' }}</div>
                                        <div class="small text-muted">{{ $row['user_email'] ?? '' }} · #{{ $row['user_id'] }}</div>
                                    </td>
                                    <td><span class="yukti-status yukti-status--{{ $row['direction'] }}">{{ $row['direction'] }}</span></td>
                                    <td>{{ number_format($row['points']) }}</td>
                                    <td>{{ $row['event_type'] }}</td>
                                    <td class="small">A {{ $row['available_after'] ?? '—' }} / P {{ $row['pending_after'] ?? '—' }}</td>
                                    <td>{{ $row['week_id'] ?? '—' }}</td>
                                    <td class="yukti-mono">{{ \Illuminate\Support\Str::limit($row['idempotency_key'], 28) }}</td>
                                    <td class="small">{{ $row['rewarded_at'] ?? $row['created_at'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-4 text-muted">No ledger rows match these filters.</td></tr>
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
