@extends('admin.layouts.layout')
@include('admin.yukti-points._styles')

@section('content')
<div class="main-content app-content yukti-admin">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb">
            <h1 class="page-title fw-medium fs-18 mb-2">Fraud Flags</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.yukti-points.dashboard') }}">YuktiPoints</a></li>
                    <li class="breadcrumb-item active">Fraud</li>
                </ol>
            </nav>
        </div>

        <p class="small text-muted mb-3">Read-only view of stored flags. No automatic user blocking is performed from this screen.</p>

        <div class="card custom-card">
            <div class="card-header">
                <form class="row g-2 align-items-end" method="get" action="{{ route('admin.yukti-points.fraud') }}">
                    <div class="col-md-4">
                        <label class="form-label" for="fraud-reason">Reason</label>
                        <input id="fraud-reason" class="form-control" type="search" name="reason" value="{{ $filters['reason'] ?? '' }}" maxlength="80">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fraud-sev">Severity / status</label>
                        <input id="fraud-sev" class="form-control" type="text" name="severity" value="{{ $filters['severity'] ?? '' }}" maxlength="20" placeholder="open, reviewed…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fraud-user">User ID</label>
                        <input id="fraud-user" class="form-control" type="number" min="1" name="user_id" value="{{ $filters['user_id'] ?? '' }}">
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
                                <th>Account</th>
                                <th>Reason</th>
                                <th>Severity / status</th>
                                <th>Reviewed</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>
                                        @if ($row->user)
                                            <div>{{ $row->user->name }}</div>
                                            <div class="small text-muted">{{ $row->user->email }} · #{{ $row->user_id }}</div>
                                        @else
                                            <span class="text-muted">#{{ $row->user_id ?? 'n/a' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->reason }}</td>
                                    <td>{{ $row->severity }}</td>
                                    <td class="small">
                                        @if ($row->reviewed_at)
                                            {{ $row->reviewed_at->toDateTimeString() }}
                                            @if ($row->reviewedByAdmin)
                                                <div class="text-muted">by {{ $row->reviewedByAdmin->name }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="small">{{ optional($row->created_at)->toDateTimeString() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">No fraud flags recorded.</td></tr>
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
