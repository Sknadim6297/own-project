@extends('professional.layout.layout')

@section('styles')
@vite('resources/css/tazen.css')
@endsection

@section('content')
<div class="content-wrapper success-page min-h-full bg-slate-50 px-4 py-8 text-slate-900 sm:px-6">
    <div class="success-shell mx-auto max-w-3xl">
        <div class="success-card space-y-6 rounded-2xl border border-brand-100 bg-white p-6 text-center shadow-card sm:p-9">
            <div class="success-icon mx-auto grid h-16 w-16 place-items-center rounded-full bg-success-surface text-3xl text-success">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h2 class="success-title text-2xl font-bold text-brand-950 sm:text-3xl">Payment Successful!</h2>
            
            <div class="success-subtitle">
                <h4>Your {{ $purchase->plan_name }} Plan is Now Active</h4>
            </div>

            <div class="detail-card rounded-xl border border-brand-100 bg-brand-50/50 p-5 text-left">
                <h5 class="mb-4 font-semibold text-brand-950">Plan Details:</h5>
                <div class="detail-grid grid gap-4 sm:grid-cols-2">
                    <div class="detail-item">
                        <strong>Plan Name:</strong>
                        <span>{{ $purchase->plan_name }}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Price Paid:</strong>
                        <span>{{ $purchase->formatted_price }}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Lead Limit:</strong>
                        <span>{{ $purchase->lead_limit }} leads</span>
                    </div>
                    <div class="detail-item">
                        <strong>Payment Status:</strong>
                        <span class="badge">{{ ucfirst($purchase->payment_status) }}</span>
                    </div>
                    @if($purchase->payment_id)
                        <div class="detail-item">
                            <strong>Payment ID:</strong>
                            <code>{{ $purchase->payment_id }}</code>
                        </div>
                    @endif
                </div>
            </div>

            @if($purchase->features && is_array($purchase->features))
                <div class="features-section">
                    <h6>Your Plan Features</h6>
                    <ul>
                        @foreach($purchase->features as $feature)
                            <li><i class="fas fa-check"></i>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="action-buttons">
                <a href="{{ route('professional.plans.my-plan') }}" class="btn-action primary">
                    <i class="fas fa-eye"></i>
                    View My Plan
                </a>
                <a href="{{ route('professional.dashboard') }}" class="btn-action success">
                    <i class="fas fa-home"></i>
                    Go to Dashboard
                </a>
            </div>

            <div class="info-note mt-4">
                <i class="fas fa-info-circle"></i>
                If you have any issues or questions, please contact support.
            </div>
        </div>
    </div>
</div>
@endsection
