@extends('admin.layouts.layout')
@include('admin.yukti-points._styles')

@section('content')
<div class="main-content app-content yukti-admin">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb">
            <h1 class="page-title fw-medium fs-18 mb-2">Customer Wallets</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.yukti-points.dashboard') }}">YuktiPoints</a></li>
                    <li class="breadcrumb-item active">Wallets</li>
                </ol>
            </nav>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <form class="row g-2 align-items-end" method="get" action="{{ route('admin.yukti-points.wallets') }}">
                    <div class="col-md-8">
                        <label class="form-label" for="wallet-q">Search by name, email, or user id</label>
                        <input id="wallet-q" class="form-control" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Available</th>
                                <th>Pending</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($wallets as $wallet)
                                <tr>
                                    <td>
                                        <div>{{ $wallet->user->name ?? '—' }}</div>
                                        <div class="small text-muted">{{ $wallet->user->email ?? '' }} · #{{ $wallet->user_id }}</div>
                                    </td>
                                    <td>{{ number_format((int) $wallet->available_points) }}</td>
                                    <td>{{ number_format((int) $wallet->pending_points) }}</td>
                                    <td class="small">{{ optional($wallet->updated_at)->toDateTimeString() ?? '—' }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.yukti-points.ledger', ['user_id' => $wallet->user_id]) }}">Ledger</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No customer wallets found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($wallets->hasPages())
                <div class="card-footer">{{ $wallets->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
