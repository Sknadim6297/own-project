@extends('layouts.layout')

@section('title', config('yuktione.name', 'YuktiOne') . ' | Event Booking Confirmed')

@section('styles')
<link rel="stylesheet" href="{{ asset('frontend/assets/css/booking-sign_up.css') }}" />
<style>
    .success-icon {
        color: #36877F;
        display: block;
        font-size: 3rem;
        margin-bottom: 1rem;
        text-align: center;
    }

    .success-header {
        margin-bottom: 1.25rem;
        text-align: center;
    }

    .booking-id {
        background-color: #EAF6F2;
        border: 1px solid #E2E9EE;
        border-radius: 0.75rem;
        color: #142B4D;
        font-weight: 600;
        margin: 1rem 0;
        padding: 0.75rem;
        text-align: center;
    }
</style>
@endsection

@section('content')
<main class="bg_gray pattern">
    <div class="container margin_60_40">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="box_booking_2">
                    <div class="head">
                        <div class="title">
                            <h3>Booking Confirmed</h3>
                        </div>
                    </div>

                    @if(isset($booking))
                        <div class="main">
                            <div class="success-header">
                                <i class="icon_check_alt2 success-icon"></i>
                                <h4>Your booking has been confirmed!</h4>

                                <div class="booking-id">
                                    Booking ID: <strong>{{ $booking->id }}</strong>
                                </div>

                                <p>A confirmation has been sent to your email address.</p>
                            </div>

                            <h6>Programme Booking Summary</h6>
                            <ul>
                                <li>Programme Name: <span>{{ $event->heading ?? $booking->event_name ?? 'N/A' }}</span></li>
                                <li>Programme Date: <span>{{ \Carbon\Carbon::parse($booking->event_date ?? $booking->booking_date)->format('d M Y') }}</span></li>
                                <li>Total Amount: <span>{{ getCurrencySymbol() }}{{ number_format(convertCurrency($booking->amount ?? $booking->total_price ?? 0), 2) }}</span></li>
                                <li>Status: <span class="text-success">Confirmed</span></li>
                                <li>Location: <span>{{ $booking->location ?? 'N/A' }}</span></li>
                                <li>Type: <span>{{ ucfirst($booking->type ?? $booking->session_type ?? 'N/A') }}</span></li>
                                <li>Persons: <span>{{ $booking->persons ?? 'N/A' }}</span></li>
                                <li>Phone: <span>{{ $booking->phone ?? $user->phone ?? 'N/A' }}</span></li>
                                <li>Your Name: <span>{{ $user->name ?? 'N/A' }}</span></li>
                                <li>Your Email: <span>{{ $user->email ?? 'N/A' }}</span></li>
                            </ul>

                            @if($booking->razorpay_payment_id || $booking->payment_id)
                                <div class="payment-info">
                                    <h6>Payment Information</h6>
                                    <ul>
                                        <li>Payment ID: <span>{{ $booking->razorpay_payment_id ?? $booking->payment_id }}</span></li>
                                        <li>Payment Status: <span class="text-success">Paid</span></li>
                                        <li>Payment Date: <span>{{ $booking->created_at->format('d M Y, h:i A') }}</span></li>
                                    </ul>
                                </div>
                            @endif

                            @if(isset($event) && $event->meet_link)
                                <div class="meeting-info">
                                    <h6>Meeting Information</h6>
                                    <div class="alert alert-info">
                                        <i class="icon_video"></i> <strong>Join Meeting:</strong>
                                        <a href="{{ $event->meet_link }}" target="_blank" class="btn btn-primary btn-sm mt-2 d-block">
                                            <i class="icon_video"></i> Click to Join Meeting
                                        </a>
                                        <small class="text-muted d-block mt-2">Please save this link for the event date</small>
                                    </div>
                                </div>
                            @endif

                            <hr>
                            @if(config('yuktione.programmes_enabled'))
                                <a href="{{ route('user.customer-event.index') }}" class="btn_1 full-width mb_5">View All Bookings</a>
                                <a href="{{ route('programmes.index') }}" class="btn_1 full-width outline mb_25">Browse More Programmes</a>
                            @else
                                <a href="{{ route('user.dashboard') }}" class="btn_1 full-width mb_5">Back to Dashboard</a>
                                <a href="{{ route('home') }}" class="btn_1 full-width outline mb_25">Return to Home</a>
                            @endif
                        </div>
                    @else
                        <div class="main">
                            <div class="text-center">
                                <i class="icon_error-circle_alt text-danger" style="font-size: 60px; margin-bottom: 20px;"></i>
                                <h4>Booking information not found</h4>
                                <p>We couldn't find your booking details. Please check your email for confirmation or contact customer support.</p>
                            </div>
                            <hr>
                            <a href="{{ route('user.dashboard') }}" class="btn_1 full-width mb_5">Back to Dashboard</a>
                            <a href="{{ route('home') }}" class="btn_1 full-width outline mb_25">Return to Home</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Event booking success page loaded');
    });
</script>
@endsection