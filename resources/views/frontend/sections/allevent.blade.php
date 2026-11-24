@extends('layouts.layout')
@section('title', (isset($allEvent) ? $allEvent->heading : ($event->heading ?? 'Programme')) . ' | Programmes | Yuktione')
@section('styles')
   {{-- <link rel="stylesheet" href="{{ asset('admin/css/styles.css') }}" /> --}}
   <link rel="stylesheet" href="{{ asset('frontend/assets/css/responsive2.css') }}" media="screen and (max-width: 992px)">
   <style>
    /* Improved Banner Slider Styles - Two Column Layout */
    .hero_single.event-slide {
        position: relative;
        width: 100%;
        padding: 60px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .banner-carousel-wrapper {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        width: 100%;
        aspect-ratio: 1 / 1;
        max-width: 480px;
        margin: 0 auto;
    }
    
    .banner-carousel {
        height: 100%;
    }
    
    .banner-carousel .owl-stage-outer,
    .banner-carousel .owl-stage,
    .banner-carousel .owl-item {
        height: 100%;
    }
    
    .banner-carousel .item {
        height: 100%;
    }
    
    .banner-carousel .item img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        background: #f1f5f6;
    }
    
    .banner-carousel .owl-dots {
        position: absolute;
        bottom: 12px;
        width: 100%;
        text-align: center;
        z-index: 10;
    }
    
    .banner-carousel .owl-dot span {
        width: 10px;
        height: 10px;
        margin: 0 4px;
        background: rgba(255,255,255,0.5);
        display: block;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .banner-carousel .owl-dot.active span {
        background: #fff;
        width: 18px;
        border-radius: 9px;
    }
    
    /* Event Details Sidebar */
    .event-details-sidebar {
        padding: 25px;
        height: 100%;
        display: flex;
        align-items: center;
    }
    
    .event-details-content {
        width: 100%;
    }
    
    .event-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #152a70;
        margin-bottom: 8px;
        line-height: 1.2;
    }
    
    .event-subtitle {
        font-size: 1rem;
        color: #6c757d;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .event-info-list {
        margin-bottom: 20px;
    }
    
    .event-info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 12px;
        padding: 10px 12px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .event-info-item:hover {
        transform: translateX(3px);
        box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    }
    
    .event-info-item i {
        font-size: 1.1rem;
        color: #152a70;
        margin-right: 10px;
        margin-top: 3px;
        min-width: 20px;
    }
    
    .event-info-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .event-info-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 3px;
        font-weight: 600;
    }
    
    .event-info-value {
        font-size: 0.9rem;
        color: #2c3e50;
        font-weight: 600;
    }
    
    .event-info-value.price-value {
        color: #28a745;
        font-size: 1rem;
    }
    
    .book-now-wrapper {
        margin-top: 20px;
    }
    
    .book-now-btn {
        width: 100%;
        padding: 10px 20px;
        font-size: 0.95rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 25px;
        background: linear-gradient(135deg, #152a70 0%, #1e3a8a 100%);
        color: #fff;
        border: none;
        box-shadow: 0 4px 15px rgba(21, 42, 112, 0.3);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .book-now-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(21, 42, 112, 0.4);
        background: linear-gradient(135deg, #1e3a8a 0%, #152a70 100%);
    }
    
    .book-now-btn i {
        font-size: 1rem;
    }
    
    /* Gallery Styles - Reduced Size with Full Visibility */
    .gallery-sliding {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    .gallery-sliding h2 {
        margin-bottom: 20px;
    }
    
    .gallery-carousal {
        width: 100% !important;
        margin: 0 !important;
    }
    
    .gallery-carousal .owl-stage-outer {
        width: 100% !important;
        overflow: hidden;
    }
    
    .gallery-carousal .owl-stage {
        width: 100% !important;
        display: flex;
    }
    
    .gallery-carousal .owl-item {
        width: 100% !important;
        flex: 0 0 100% !important;
        height: auto !important;
    }
    
    .gallery-carousal .item {
        width: 100% !important;
        height: auto !important;
        min-height: 350px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 20px 20px 40px 20px;
        margin: 0;
        background: #f8f9fa;
    }
    
    .gallery-carousal .item img {
        max-width: 50% !important;
        max-height: 350px !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain;
        object-position: center;
        display: block;
        margin: -20px auto 0 auto;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .gallery-carousal .owl-dots {
        position: relative;
        margin-top: 20px;
        width: 100%;
        text-align: center;
        z-index: 10;
    }
    
    .gallery-carousal .owl-dot span {
        width: 12px;
        height: 12px;
        margin: 0 5px;
        background: rgba(0,0,0,0.3);
        display: block;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .gallery-carousal .owl-dot.active span {
        background: #152a70;
        width: 20px;
        border-radius: 10px;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
        .hero_single.event-slide {
            padding: 40px 0;
        }
        
        .banner-carousel-wrapper {
            max-width: 420px;
            margin-bottom: 20px;
        }
        
        .event-details-sidebar {
            padding: 20px 0;
        }
        
        .event-title {
            font-size: 1.6rem;
        }
        
        .event-subtitle {
            font-size: 0.95rem;
        }
        
        .event-info-item {
            padding: 9px 10px;
            margin-bottom: 10px;
        }
        
        .event-info-item i {
            font-size: 1rem;
            margin-right: 8px;
        }
        
        .event-info-label {
            font-size: 0.7rem;
        }
        
        .event-info-value {
            font-size: 0.85rem;
        }
        
        .event-info-value.price-value {
            font-size: 0.95rem;
        }
        
        .book-now-btn {
            padding: 9px 18px;
            font-size: 0.9rem;
        }
        
        .book-now-btn i {
            font-size: 0.95rem;
        }
        
        .gallery-carousal .item {
            min-height: 300px;
            padding: 15px;
        }
        
        .gallery-carousal .item img {
            max-width: 50% !important;
            max-height: 300px !important;
        }
    }
    
    @media (max-width: 768px) {
        .hero_single.event-slide {
            padding: 30px 0;
        }
        
        .banner-carousel-wrapper {
            max-width: 360px;
        }
        
        .event-details-sidebar {
            padding: 15px;
        }
        
        .event-title {
            font-size: 1.5rem;
        }
        
        .event-subtitle {
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .event-info-list {
            margin-bottom: 15px;
        }
        
        .event-info-item {
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        
        .event-info-item i {
            font-size: 0.95rem;
            margin-right: 8px;
        }
        
        .event-info-label {
            font-size: 0.65rem;
        }
        
        .event-info-value {
            font-size: 0.8rem;
        }
        
        .event-info-value.price-value {
            font-size: 0.9rem;
        }
        
        .book-now-wrapper {
            margin-top: 15px;
        }
        
        .book-now-btn {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        .book-now-btn i {
            font-size: 0.9rem;
        }
        
        .gallery-carousal .item {
            min-height: 300px;
            padding: 15px;
        }
        
        .gallery-carousal .item img {
            max-width: 55% !important;
            max-height: 280px !important;
        }
    }
    
    @media (max-width: 576px) {
        .banner-carousel-wrapper {
            max-width: 320px;
        }
        
        .banner-carousel .owl-dots {
            bottom: 10px;
        }
        
        .banner-carousel .owl-dot span {
            width: 8px;
            height: 8px;
            margin: 0 3px;
        }
        
        .banner-carousel .owl-dot.active span {
            width: 16px;
            border-radius: 8px;
        }
        
        .event-details-sidebar {
            padding: 12px;
        }
        
        .event-title {
            font-size: 1.3rem;
            margin-bottom: 6px;
        }
        
        .event-subtitle {
            font-size: 0.85rem;
            margin-bottom: 12px;
        }
        
        .event-info-list {
            margin-bottom: 12px;
        }
        
        .event-info-item {
            padding: 7px 8px;
            margin-bottom: 6px;
        }
        
        .event-info-item i {
            font-size: 0.9rem;
            margin-right: 6px;
        }
        
        .event-info-label {
            font-size: 0.6rem;
        }
        
        .event-info-value {
            font-size: 0.75rem;
        }
        
        .event-info-value.price-value {
            font-size: 0.85rem;
        }
        
        .book-now-wrapper {
            margin-top: 12px;
        }
        
        .book-now-btn {
            padding: 7px 14px;
            font-size: 0.8rem;
        }
        
        .book-now-btn i {
            font-size: 0.85rem;
        }
        
        .gallery-carousal .item {
            min-height: 220px;
            padding: 10px;
        }
        
        .gallery-carousal .item img {
            max-width: 60% !important;
            max-height: 250px !important;
        }
    }

    /* Modern Modal Styles */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .modal-content {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .modal-header {
        padding: 1.5rem;
        position: relative;
    }

    .modal-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    }

    .event-summary-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .event-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .price-breakdown {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
    }
    .form-control, .input-group-text {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .input-group .form-control {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .input-group .input-group-text {
        border-right: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .btn {
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
    }

    .btn-outline-secondary {
        border: 2px solid #6c757d;
        color: #6c757d;
    }

    .btn-outline-secondary:hover {
        background: #6c757d;
        border-color: #6c757d;
        transform: translateY(-1px);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    /* Animation for modal */
    .modal.fade .modal-dialog {
        transform: scale(0.8);
        transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
        transform: scale(1);
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .modal-dialog {
            margin: 1rem;
        }
        
        .modal-header {
            padding: 1rem;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .modal-footer {
            padding: 1rem;
        }
    }

    /* Custom scrollbar for textarea */
    textarea::-webkit-scrollbar {
        width: 6px;
    }

    textarea::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    textarea::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    textarea::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
        /* Login Popup Custom Styling */
    .login-popup-title {
        font-size: 1.5rem !important;
        color: white !important;
    }

    .login-popup-title .wave-icon {
        font-size: 1.8rem !important;
    }

    .login-popup-custom {
        background: linear-gradient(135deg, #152a70, #c51010, #f39c12) !important;
        color: white !important;
        border-radius: 15px !important;
        padding: 25px 20px !important;
    }

    .login-popup-btn {
        background-color: #1e0d60 !important;
        color: white !important;
        font-size: 1.2rem !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        border-radius: 50px !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    }

    .login-popup-btn:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;
    }
    
    .login-popup-close {
        color: white !important;
        opacity: 0.8 !important;
        font-size: 1.5rem !important;
    }
    
    .login-popup-close:hover {
        opacity: 1 !important;
    }
   </style>
@endsection
@section('content')

@php
    // Initialize $eventDetail for both professional and admin events
    if (!isset($eventDetail)) {
        $eventDetail = null;
    }
    
    // If this is an admin event, get the first EventDetail from the collection
    if (isset($event) && !isset($allEvent) && $event->eventDetails && $event->eventDetails->count() > 0) {
        $eventDetail = $event->eventDetails->first();
    }
    $programmeImageUrl = static function ($imagePath) {
        if (!filled($imagePath)) {
            return null;
        }

        $imagePath = trim(str_replace('\\/', '/', $imagePath));
        if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        $imagePath = ltrim($imagePath, '/');
        $imagePath = \Illuminate\Support\Str::startsWith($imagePath, 'storage/')
            ? \Illuminate\Support\Str::after($imagePath, 'storage/')
            : $imagePath;

        return asset('storage/' . $imagePath);
    };
@endphp

<main class="programme-detail">
  <nav class="programme-detail__breadcrumbs" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Home</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('programmes.index') }}">Programmes</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">{{ isset($allEvent) ? $allEvent->heading : ($eventDetail->heading ?? $event->heading ?? 'Programme') }}</span>
  </nav>
  <!-- Improved Banner Section with Left Banner and Right Details -->
  <div class="hero_single event-slide">
    <div class="container">
        <div class="row align-items-center">
            <!-- Left Side: Banner Images Carousel -->
            <div class="col-lg-6 col-md-6">
                <div class="banner-carousel-wrapper">
                    <div class="owl-carousel owl-theme banner-carousel">
                        @if(isset($allEvent))
                            {{-- Professional Event --}}
                            @if(isset($eventDetail) && $eventDetail && $eventDetail->banner_image)
                                {{-- Professional event with event details banner --}}
                                @php
                                    $bannerImages = json_decode($eventDetail->banner_image, true);
                                    if (!is_array($bannerImages)) {
                                        $bannerImages = [$eventDetail->banner_image];
                                    }
                                @endphp
                                @foreach($bannerImages as $banner)
                                    @php $bannerUrl = $programmeImageUrl($banner); @endphp
                                    <div class="item">
                                        @if ($bannerUrl)
                                            <img src="{{ $bannerUrl }}" alt="{{ $allEvent->heading }}" loading="lazy" decoding="async" data-programme-detail-image>
                                        @endif
                                        <span class="programme-detail__image-fallback" @if ($bannerUrl) hidden @endif aria-hidden="true">Programme artwork unavailable</span>
                                    </div>
                                @endforeach
                            @else
                                {{-- Professional event using card_image as fallback --}}
                                <div class="item">
                                    @php $bannerUrl = $programmeImageUrl($allEvent->card_image); @endphp
                                    @if ($bannerUrl)
                                        <img src="{{ $bannerUrl }}" alt="{{ $allEvent->heading }}" loading="lazy" decoding="async" data-programme-detail-image>
                                    @endif
                                    <span class="programme-detail__image-fallback" @if ($bannerUrl) hidden @endif aria-hidden="true">Programme artwork unavailable</span>
                                </div>
                            @endif
                        @else
                            {{-- Admin Event - use banner_image array --}}
                            @php
                                $eventDetail = $event->eventDetails && $event->eventDetails->count() > 0 ? $event->eventDetails->first() : null;
                                $banners = $eventDetail ? ($eventDetail->banner_image ?? '[]') : ($event->banner_image ?? '[]');
                                if (is_string($banners)) {
                                    $banners = json_decode($banners, true);
                                    if (is_string($banners)) {
                                        $banners = json_decode($banners, true);
                                    }
                                }
                                if (!is_array($banners)) {
                                    $banners = [];
                                }
                            @endphp
                            @if (is_array($banners) && count($banners))
                                @foreach ($banners as $banner)
                                    @php $bannerUrl = $programmeImageUrl($banner); @endphp
                                    <div class="item">
                                        @if ($bannerUrl)
                                            <img src="{{ $bannerUrl }}" alt="{{ $eventDetail->heading ?? $event->heading ?? 'Programme' }}" loading="lazy" decoding="async" data-programme-detail-image>
                                        @endif
                                        <span class="programme-detail__image-fallback" @if ($bannerUrl) hidden @endif aria-hidden="true">Programme artwork unavailable</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="item">
                                    <span class="programme-detail__image-fallback" aria-hidden="true">Programme artwork unavailable</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Event Details and Book Now Button -->
            <div class="col-lg-6 col-md-6">
                <div class="event-details-sidebar">
                    <div class="event-details-content">
                        @if(isset($allEvent))
                            <h2 class="event-title">{{ $allEvent->heading }}</h2>
                            <p class="event-subtitle">{{ isset($eventDetail) && $eventDetail ? ($eventDetail->event_type ?? $allEvent->mini_heading) : $allEvent->mini_heading }}</p>
                        @else
                            <h2 class="event-title">{{ $eventDetail->heading ?? $event->heading ?? 'Event' }}</h2>
                            <p class="event-subtitle">{{ $eventDetail->event_type ?? $event->event_type ?? 'Event' }}</p>
                        @endif
                        
                        <div class="event-info-list">
                            <!-- Date & Time -->
                            <div class="event-info-item">
                                <i class="fa-solid fa-calendar-days"></i>
                                <div class="event-info-content">
                                    <span class="event-info-label">Programme Date &amp; Time</span>
                                    <span class="event-info-value">
                                        @if(isset($allEvent))
                                            {{ \Carbon\Carbon::parse($allEvent->date)->format('d M, Y') }}
                                            @if($allEvent->time)
                                                {{ \Carbon\Carbon::parse($allEvent->time)->format('h:i A') }}
                                            @endif
                                        @else
                                            {{ \Carbon\Carbon::parse($eventDetail ? $eventDetail->starting_date : ($event->starting_date ?? $event->date))->format('d M, Y') }}
                                            @if($eventDetail && isset($eventDetail->time))
                                                {{ \Carbon\Carbon::parse($eventDetail->time)->format('h:i A') }}
                                            @endif
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Location -->
                            <div class="event-info-item">
                                <i class="fa-solid fa-location-dot"></i>
                                <div class="event-info-content">
                                    <span class="event-info-label">Location</span>
                                    <span class="event-info-value">
                                        @if(isset($allEvent))
                                            @if(isset($eventDetail) && $eventDetail && $eventDetail->event_mode)
                                                {{ ucfirst($eventDetail->event_mode) }}
                                                @if($eventDetail->city)
                                                    - {{ $eventDetail->city }}
                                                @endif
                                            @else
                                                To be announced
                                            @endif
                                        @else
                                            {{ $eventDetail ? $eventDetail->event_mode : ($event->event_mode ?? 'To be announced') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Price -->
                            <div class="event-info-item">
                                <i class="fa-solid fa-coins"></i>
                                <div class="event-info-content">
                                    <span class="event-info-label">Starting From</span>
                                    <span class="event-info-value price-value">
                                        @if(isset($allEvent))
                                            {{ formatPrice(isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) }}
                                        @else
                                            {{ formatPrice($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Book Now Button -->
                        <div class="book-now-wrapper">
                            <button class="btn unique-btn book-now-btn" id="bookNowBtn"
                                data-event-id="{{ isset($allEvent) ? $allEvent->id : ($event->event_id ?? $event->id) }}"
                                data-event-name="{{ isset($allEvent) ? $allEvent->heading : ($eventDetail->heading ?? $event->heading ?? 'Event') }}"
                                data-location="{{ isset($allEvent) ? (isset($eventDetail) && $eventDetail && $eventDetail->city ? $eventDetail->city : 'To be announced') : ($eventDetail ? $eventDetail->city : ($event->city ?? 'To be announced')) }}"
                                data-type="{{ isset($allEvent) ? (isset($eventDetail) && $eventDetail && $eventDetail->event_mode ? $eventDetail->event_mode : 'TBD') : ($eventDetail ? $eventDetail->event_mode : ($event->event_mode ?? 'offline')) }}"
                                data-event-date="{{ isset($allEvent) ? \Carbon\Carbon::parse($allEvent->date)->format('d-m-Y') : \Carbon\Carbon::parse($eventDetail ? $eventDetail->starting_date : ($event->starting_date ?? $event->date))->format('d-m-Y') }}"
                                data-amount="{{ isset($allEvent) ? convertCurrency(isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) : convertCurrency($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}"
                                data-original-amount="{{ isset($allEvent) ? (isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) : ($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}">
                                <i class="fa-solid fa-ticket"></i> Book Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 </div>

<style>
    .event-info-btn {
        border: 2px solid #152a70;
        border-radius: 30px;
        padding: 10px 20px;
        color: #152a70;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        background: transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        text-decoration: none;
    }
    .event-info-btn:hover {
        background: linear-gradient(135deg, #152a70, #1e3a8a);
        color: white;
        transform: translateY(-2px);
        border-color: transparent;
    }
    .event-info-btn i {
        font-size: 16px;
    }
    .event-info-btn .btn-label {
        font-weight: 500;
        font-size: 12px;
        opacity: 0.8;
    }
    .event-info-btn .btn-value {
        font-weight: 700;
    }
    .event-action-buttons {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    @media (max-width: 991px) {
        .event-action-buttons {
            justify-content: center;
            margin-top: 15px;
        }
    }
    @media (max-width: 576px) {
        .event-info-btn {
            padding: 8px 10px;
            font-size: 12px;
            flex-direction: row;
            white-space: nowrap;
            flex: 1 1 auto;
            min-width: 0;
        }
        .event-info-btn i {
            font-size: 14px;
            flex-shrink: 0;
        }
        .event-info-btn div {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 4px;
            margin: 0;
            min-width: 0;
        }
        .event-info-btn .btn-label {
            display: none;
        }
        .event-info-btn br {
            display: none;
        }
        .event-info-btn .btn-value {
            font-weight: 600;
            font-size: 10px;
            white-space: nowrap;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .event-action-buttons {
            gap: 4px;
            flex-wrap: wrap;
            overflow-x: visible;
        }
        .event-action-buttons .btn.unique-btn {
            margin-left: 110px;
            padding: 10px 8px !important;
            font-size: 11px !important;
            height: auto;
            line-height: 1.2;
        }
    }
</style>

<div class="event-information my-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <div class="details-event">
                    @if(isset($allEvent))
                        <h3 style="font-size: 21px;">{{ $allEvent->heading }}</h3>
                        @if(isset($eventDetail) && $eventDetail)
                            <p>{{ $eventDetail->event_type ?? $allEvent->mini_heading }}</p>
                        @else
                            <p>{{ $allEvent->mini_heading }}</p>
                        @endif
                    @else
                        <h3 style="    font-size: 21px;">{{ $eventDetail->heading ?? $event->heading ?? 'Event' }}</h3>
                        <p>{{ $eventDetail->event_type ?? $event->event_type ?? 'Event' }}</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-7">
                <div class="event-action-buttons">
                    <!-- Date & Time Button -->
                    <div class="event-info-btn">
                        <i class="fa-solid fa-calendar-days"></i>
                        <div>
                            <span class="btn-label">Programme Date &amp; Time</span><br>
                            <span class="btn-value">
                                @if(isset($allEvent))
                                    {{ \Carbon\Carbon::parse($allEvent->date)->format('d M, Y') }}
                                    @if($allEvent->time)
                                        {{ \Carbon\Carbon::parse($allEvent->time)->format('h:i A') }}
                                    @endif
                                @else
                                    {{ \Carbon\Carbon::parse($eventDetail ? $eventDetail->starting_date : ($event->starting_date ?? $event->date))->format('d M, Y') }}
                                    @if($eventDetail && isset($eventDetail->time))
                                        {{ \Carbon\Carbon::parse($eventDetail->time)->format('h:i A') }}
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <!-- Location Button -->
                    <div class="event-info-btn">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <span class="btn-label">Location</span><br>
                            <span class="btn-value">
                                @if(isset($allEvent))
                                    @if(isset($eventDetail) && $eventDetail && $eventDetail->event_mode)
                                        {{ ucfirst($eventDetail->event_mode) }}
                                        @if($eventDetail->city)
                                            - {{ $eventDetail->city }}
                                        @endif
                                    @else
                                        To be announced
                                    @endif
                                @else
                                    {{ $eventDetail ? $eventDetail->event_mode : ($event->event_mode ?? 'To be announced') }}
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <!-- Fees Button -->
                    <div class="event-info-btn">
                        <i class="fa-solid fa-coins"></i>
                        <div>
                            <span class="btn-label">Starting From</span><br>
                            <span class="btn-value">
                                @if(isset($allEvent))
                                    {{ formatPrice(isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) }}
                                @else
                                    {{ formatPrice($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <!-- Book Now Button -->
                    <button class="btn unique-btn" id="bookNowBtn"
                        data-event-id="{{ isset($allEvent) ? $allEvent->id : ($event->event_id ?? $event->id) }}"
                        data-event-name="{{ isset($allEvent) ? $allEvent->heading : ($eventDetail->heading ?? $event->heading ?? 'Event') }}"
                        data-location="{{ isset($allEvent) ? (isset($eventDetail) && $eventDetail && $eventDetail->city ? $eventDetail->city : 'To be announced') : ($eventDetail ? $eventDetail->city : ($event->city ?? 'To be announced')) }}"
                        data-type="{{ isset($allEvent) ? (isset($eventDetail) && $eventDetail && $eventDetail->event_mode ? $eventDetail->event_mode : 'TBD') : ($eventDetail ? $eventDetail->event_mode : ($event->event_mode ?? 'offline')) }}"
                        data-event-date="{{ isset($allEvent) ? \Carbon\Carbon::parse($allEvent->date)->format('d-m-Y') : \Carbon\Carbon::parse($eventDetail ? $eventDetail->starting_date : ($event->starting_date ?? $event->date))->format('d-m-Y') }}"
                        data-amount="{{ isset($allEvent) ? convertCurrency(isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) : convertCurrency($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}"
                        data-original-amount="{{ isset($allEvent) ? (isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) : ($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}">
                        Book Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<hr>

<div class="share-description-map my-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="share-content">
                    <h2>Share this Programme</h2>
                    <div class="share-button text-center">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(Request::url()) }}" 
                           target="_blank" 
                           class="share-link facebook">
                            <i class="fa-brands fa-square-facebook"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode(isset($allEvent) ? $allEvent->heading : ($eventDetail ? $eventDetail->heading : $event->heading)) }}&url={{ urlencode(Request::url()) }}" 
                           target="_blank" 
                           class="share-link twitter">
                            <i class="fa-brands fa-square-x-twitter"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode((isset($allEvent) ? $allEvent->heading : ($eventDetail ? $eventDetail->heading : $event->heading)) . ' - ' . Request::url()) }}" 
                           target="_blank" 
                           class="share-link whatsapp">
                            <i class="fa-brands fa-square-whatsapp"></i>
                        </a>
                        <button onclick="copyToClipboard()" class="share-link instagram">
                            <i class="fa-brands fa-square-instagram"></i>
                        </button>
                    </div>
                    <div id="copyMessage" class="copy-message" style="display: none;">
                        Programme details copied! You can now paste this into your Instagram story or post.
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
               <div class="third-portion my-3">
                   <div class="about-content">
                       <h3>Programme Details</h3>
                    @if(isset($allEvent))
                        @if(isset($eventDetail) && $eventDetail)
                            {{-- Professional event with event details --}}
                            <p>{{ $eventDetail->event_details }}</p>
                        @else
                            {{-- Professional event without additional details --}}
                            <p>{{ $allEvent->short_description }}</p>
                        @endif
                    @else
                        {{-- Admin event --}}
                        <p>{{ $eventDetail ? $eventDetail->event_details : ($event->event_details ?? 'Event details not available') }}</p>
                    @endif
                </div>
            </div>
            
            @if(!isset($allEvent) || (isset($eventDetail) && $eventDetail && $eventDetail->event_gallery))
            {{-- Gallery section for admin events or professional events with gallery --}}
            <div class="forth-portion my-3">
                <div class="gallery-sliding">
                    <h2>Gallery</h2>
                    <div class="owl-carousel gallery-carousal">
                        @if(isset($allEvent) && isset($eventDetail) && $eventDetail)
                            {{-- Professional event with event details gallery --}}
                            @php
                                $galleryImages = json_decode($eventDetail->event_gallery, true);
                                if (!is_array($galleryImages)) {
                                    $galleryImages = [];
                                }
                            @endphp
                            @if (count($galleryImages))
                                @foreach ($galleryImages as $galleryImage)
                                    <div class="item">
                                        <img src="{{ asset('storage/' . str_replace('\\/', '/', $galleryImage)) }}" alt="Gallery Image">
                                    </div>
                                @endforeach
                            @else
                                <div class="item">
                                    <p>No gallery images available.</p>
                                </div>
                            @endif
                        @else
                            {{-- Admin event gallery --}}
                            @php
                                $decoded = json_decode($eventDetail ? ($eventDetail->event_gallery ?? '[]') : ($event->event_gallery ?? '[]'), true);
                                $galleryImages = is_string($decoded) ? json_decode($decoded, true) : $decoded;
                            @endphp
                
                            @if (is_array($galleryImages) && count($galleryImages))
                                @foreach ($galleryImages as $galleryImage)
                                    <div class="item">
                                        <img src="{{ asset('storage/' . str_replace('\\/', '/', $galleryImage)) }}" alt="Gallery Image">
                                    </div>
                                @endforeach
                            @else
                                <div class="item">
                                    <p>No gallery images available.</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="fifth-portion">
                <div class="col-lg-12 col-md-12 mt-md-0 mt-sm-4 mt-4">
                    <div class="accordion d-flex flex-column gap-4" id="accordionExample" data-aos="fade-down">
                        @if(isset($allEvent))
                            {{-- Professional Event FAQs --}}
                            @if(isset($eventfaqs) && $eventfaqs->count() > 0)
                                @foreach($eventfaqs as $faq)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                  {{$faq->question1}}
                                  </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <p>{{$faq->answer1}}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            {{$faq->question2}}
                                  </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <p>{{$faq->answer2}}</p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="accordion-item">
                                    <div class="accordion-body">
                                        <p>No FAQs available for this programme.</p>
                                    </div>
                                </div>
                            @endif
                        @else
                            {{-- Admin Event FAQs --}}
                            @foreach($eventfaqs as $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                              {{$faq->question1}}
                              </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <p>{{$faq->answer1}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        {{$faq->question2}}
                              </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <p>{{$faq->answer2}}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            </div>
            <div class="col-lg-3 pl-0">
                <div class="share-option">
                    @php
                        // Determine event mode and location
                        $eventMode = '';
                        $eventCity = '';
                        $mapLink = null;
                        
                        if(isset($allEvent)) {
                            // Professional Event
                            $eventMode = isset($eventDetail) && $eventDetail && $eventDetail->event_mode 
                                        ? $eventDetail->event_mode 
                                        : 'offline';
                            $eventCity = isset($eventDetail) && $eventDetail && $eventDetail->city 
                                        ? $eventDetail->city 
                                        : '';
                            $mapLink = $allEvent->map_link ?? null;
                        } else {
                            // Admin Event
                            $eventMode = $eventDetail ? $eventDetail->event_mode : ($event->event_mode ?? 'offline');
                            $eventCity = $eventDetail ? $eventDetail->city : ($event->city ?? '');
                            $mapLink = $event->map_link ?? null;
                        }
                    @endphp

                    @if(strtolower($eventMode) === 'online')
                        {{-- Online Event --}}
                        <div style="text-align: center; padding: 2rem; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 15px; border: 2px solid #2196f3;">
                            <i class="fas fa-video" style="font-size: 3rem; color: #1976d2; margin-bottom: 1rem;"></i>
                            <h6 style="color: #1565c0; font-weight: 600; margin-bottom: 0.5rem;">Online Programme</h6>
                            <p style="color: #1976d2; margin-bottom: 1rem; font-size: 0.9rem;">Join from anywhere in the world!</p>
                            <div style="background: rgba(25, 118, 210, 0.1); padding: 1rem; border-radius: 10px; border-left: 4px solid #1976d2;">
                                <small style="color: #1565c0; font-weight: 500;">
                                    <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                                    Event link will be shared in your dashboard after successful booking
                                </small>
                            </div>
                        </div>
                    @else
                        {{-- Offline Event --}}
                        @if($eventCity)
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 0.5rem;">
                                <i class="fas fa-map-marker-alt" style="color: #e74c3c; margin-right: 0.5rem;"></i>
                                {{ $eventCity }}
                            </h6>
                        @else
                            <h6 style="color: #7f8c8d; font-weight: 600; margin-bottom: 0.5rem;">
                                <i class="fas fa-map-marker-alt" style="color: #95a5a6; margin-right: 0.5rem;"></i>
                                Venue details will be shared after booking
                            </h6>
                        @endif

                        @if($mapLink && $eventCity)
                            {{-- Show map only if we have both map link and city --}}
                            <div style="position: relative; width: 100%; max-width: 600px; height: 300px; overflow: hidden; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); margin-top: 1rem;">
                                <iframe 
                                    src="{{ $mapLink }}" 
                                    width="100%" 
                                    height="100%" 
                                    style="border:0; pointer-events: none;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>

                                <a href="{{ $mapLink }}" 
                                target="_blank" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; text-indent: -9999px;">
                                Open Map
                                </a>
                            </div>
                        @elseif($eventCity)
                            {{-- Show location info without map --}}
                            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 1.5rem; border-radius: 10px; border: 1px solid #dee2e6; margin-top: 1rem; text-align: center;">
                                <i class="fas fa-building" style="font-size: 2rem; color: #6c757d; margin-bottom: 0.5rem;"></i>
                                <p style="color: #495057; margin: 0; font-size: 0.9rem;">
                                    Exact venue address will be provided closer to the event date
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="bookingForm">
                @csrf
                <div class="modal-header bg-gradient-primary text-white border-0">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold">Book Your Programme</h5>
                            <small class="opacity-75">Complete your booking details</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <input type="hidden" id="event_id" name="event_id">
                    
                    <!-- Event Summary Card -->
                    <div class="event-summary-card mb-4 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-star text-warning me-2"></i>
                            <h6 class="mb-0 fw-bold text-primary">Programme Details</h6>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Programme Name</small>
                                <span class="fw-semibold" id="modalEventName">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Date</small>
                                <span class="fw-semibold" id="modalEventDate">-</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Location</small>
                                <span class="fw-semibold" id="modalEventLocation">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Price per Person</small>
                                <span class="fw-semibold text-success" id="modalEventPrice">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-group mb-4">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fas fa-phone text-primary me-2"></i>Phone Number
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-mobile-alt text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="phone" name="phone" 
                                   maxlength="10" placeholder="Enter 10-digit mobile number" required>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle text-info me-1"></i>
                            We'll send booking confirmation to this number
                        </div>
                    </div>

                    <!-- Number of Persons -->
                    <div class="form-group mb-4">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fas fa-users text-primary me-2"></i>Number of Persons
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-user-friends text-muted"></i>
                            </span>
                            <input type="number" class="form-control border-start-0" id="persons" name="persons" 
                                   min="1" placeholder="How many people?" required>
                            <button type="button" class="btn btn-outline-secondary" id="decreasePersons">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="increasePersons">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="price-breakdown bg-light rounded-3 p-3 mb-4">
                        <h6 class="fw-semibold text-dark mb-3">
                            <i class="fas fa-receipt text-primary me-2"></i>Price Breakdown
                        </h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Price per person:</span>
                            <span id="pricePerPerson">{{ getCurrencySymbol() }}0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Number of persons:</span>
                            <span id="personsCount">0</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Total Amount:</span>
                            <span class="fw-bold text-success fs-5" id="totalPrice">{{ getCurrencySymbol() }}0</span>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="form-group mb-4">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fas fa-comment text-primary me-2"></i>Additional Information (Optional)
                        </label>
                        <textarea class="form-control" id="additional_info" name="additional_info" 
                                  rows="3" placeholder="Any special requirements or notes..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 p-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</main>

@endsection
@section('script')
<!-- Add SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Owl Carousel for banner with autoplay
    $('.banner-carousel').owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        smartSpeed: 1000,
        dots: true,
        nav: false,
        animateOut: 'fadeOut',
        animateIn: 'fadeIn'
    });

    // Initialize Owl Carousel for gallery
    $('.gallery-carousal').owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        autoplayTimeout: 3000,
        autoplaySpeed: 1000,
        autoplayHoverPause: false,
        smartSpeed: 1000,
        dots: true,
        animateOut: 'fadeOut',
        animateIn: 'fadeIn'
    });

    const bookNowBtn = document.getElementById('bookNowBtn');
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    const eventIdInput = document.getElementById('event_id');
    const personsInput = document.getElementById('persons');
    const phoneInput = document.getElementById('phone');
    const totalPrice = document.getElementById('totalPrice');
    const bookingForm = document.getElementById('bookingForm');
    
    // New modal elements
    const modalEventName = document.getElementById('modalEventName');
    const modalEventDate = document.getElementById('modalEventDate');
    const modalEventLocation = document.getElementById('modalEventLocation');
    const modalEventPrice = document.getElementById('modalEventPrice');
    const pricePerPerson = document.getElementById('pricePerPerson');
    const personsCount = document.getElementById('personsCount');
    const decreasePersons = document.getElementById('decreasePersons');
    const increasePersons = document.getElementById('increasePersons');

    let eventDetails = {}; 

    // Function to save form data to localStorage
    function saveFormData(data) {
        localStorage.setItem('bookingFormData', JSON.stringify(data));
    }

    // Function to load form data from localStorage
    function loadFormData() {
        const savedData = localStorage.getItem('bookingFormData');
        if (savedData) {
            const data = JSON.parse(savedData);
            
            // Restore form fields
            eventIdInput.value = data.event_id || '';
            phoneInput.value = data.phone || '';
            personsInput.value = data.persons || '';
            
            // Update eventDetails
            eventDetails = {
                event_id: data.event_id,
                event_name: data.event_name,
                location: data.location,
                type: data.type,
                event_date: data.event_date,
                amount: parseFloat(data.amount) || 0
            };
            
            // Populate modal details
            populateModalDetails();
            
            // Update price breakdown
            updatePriceBreakdown();
            
            return true;
        }
        return false;
    }

    // Function to clear form data from localStorage
    function clearFormData() {
        localStorage.removeItem('bookingFormData');
    }

    // Check for saved form data on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check if user just returned from login
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('redirect') && loadFormData()) {
            // Show the modal with pre-filled data
            setTimeout(() => {
                bookingModal.show();
            }, 500); // Small delay to ensure everything is loaded
        }
    });

    bookNowBtn.addEventListener('click', function () {
        eventDetails = {
            event_id: this.getAttribute('data-event-id'),
            event_name: this.getAttribute('data-event-name') || 'Event',
            location: this.getAttribute('data-location'),
            type: this.getAttribute('data-type'),
            event_date: this.getAttribute('data-event-date'),
            amount: parseFloat(this.getAttribute('data-amount')) || 0,
            original_amount: parseFloat(this.getAttribute('data-original-amount')) || 0
        };

        // First check if user is logged in
        fetch("{{ route('check.login') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ check: true })
        })
        .then(res => {
            if (res.status === 401) {
                // User is not logged in, show SweetAlert
                Swal.fire({
                    title: '<span class="login-popup-title">Hey! You forgot to login</span>',
                    text: '',
                    showCloseButton: true,
                    showCancelButton: false,
                    confirmButtonText: 'Login',
                    customClass: {
                        popup: 'login-popup-custom',
                        confirmButton: 'login-popup-btn',
                        closeButton: 'login-popup-close'
                    },
                    confirmButtonColor: '#1e0d60'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const redirectUrl = encodeURIComponent(window.location.href);
                        window.location.href = "{{ route('login') }}" + '?redirect=' + redirectUrl;
                    } else {
                        // Clear saved data if user cancels
                        clearFormData();
                    }
                });
                throw new Error('Unauthorized');
            }
            if (!res.ok) {
                return res.json().then(errData => {
                    throw new Error(errData.message || `HTTP error! status: ${res.status}`);
                });
            }
            return res.json();
        })
        .then(data => {
            if (data.logged_in) {
                // User is logged in, show the modal
                bookingForm.reset();
                eventIdInput.value = eventDetails.event_id;
                
                // Populate modal with event details
                populateModalDetails();
                
                // Reset price breakdown
                updatePriceBreakdown();
                
                bookingModal.show();
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('Fetch error:', error);
                toastr.error(error.message || 'Request failed.');
            }
        });
    });

    // Get currency symbol from backend
    const currencySymbol = "{{ getCurrencySymbol() }}";
    
    // Function to update price breakdown
    function updatePriceBreakdown() {
        const persons = parseInt(personsInput.value) || 0;
        const amount = parseFloat(eventDetails.amount) || 0;
        const total = persons * amount;
        
        totalPrice.textContent = `${currencySymbol}${total.toFixed(2)}`;
        pricePerPerson.textContent = `${currencySymbol}${amount.toFixed(2)}`;
        personsCount.textContent = persons;
    }

    // Function to populate modal with event details
    function populateModalDetails() {
        modalEventName.textContent = eventDetails.event_name || 'Event';
        modalEventDate.textContent = eventDetails.event_date || 'TBD';
        modalEventLocation.textContent = eventDetails.location || 'Location TBD';
        modalEventPrice.textContent = `${currencySymbol}${(eventDetails.amount || 0).toFixed(2)}`;
        pricePerPerson.textContent = `${currencySymbol}${(eventDetails.amount || 0).toFixed(2)}`;
    }

    // Plus/Minus button functionality
    decreasePersons.addEventListener('click', function() {
        const currentValue = parseInt(personsInput.value) || 1;
        if (currentValue > 1) {
            personsInput.value = currentValue - 1;
            updatePriceBreakdown();
        }
    });

    increasePersons.addEventListener('click', function() {
        const currentValue = parseInt(personsInput.value) || 0;
        personsInput.value = currentValue + 1;
        updatePriceBreakdown();
    });

    // Update price when persons input changes
    personsInput.addEventListener('input', function () {
        updatePriceBreakdown();
    });

    bookingForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const phone = phoneInput.value.trim();
        const persons = parseInt(personsInput.value);

        if (!phone || phone.length !== 10) {
            toastr.error('Please enter a valid 10-digit phone number');
            return;
        }

        if (isNaN(persons) || persons < 1) {
            toastr.error('Please enter a valid number of persons');
            return;
        }

        const finalData = {
            event_id: eventDetails.event_id,
            event_name: eventDetails.event_name,
            location: eventDetails.location,
            type: eventDetails.type,
            event_date: eventDetails.event_date,
            amount: eventDetails.original_amount || eventDetails.amount, // Use original INR amount for backend
            phone: phone,
            persons: persons,
            total_price: persons * (eventDetails.original_amount || eventDetails.amount), // Calculate total in INR
            additional_info: document.getElementById('additional_info').value.trim()
        };

        // Save form data to localStorage before making the request
        saveFormData(finalData);

        fetch("{{ route('check.login') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(finalData)
        })
        .then(res => {
            if (res.status === 401) {
                // Show SweetAlert for login prompt
                Swal.fire({
                    title: '<span class="login-popup-title">Hey! You forgot to login</span>',
                    text: '',
                    showCloseButton: true,
                    showCancelButton: false,
                    confirmButtonText: 'Login',
                    customClass: {
                        popup: 'login-popup-custom',
                        confirmButton: 'login-popup-btn',
                        closeButton: 'login-popup-close'
                    },
                    confirmButtonColor: '#1e0d60'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const redirectUrl = encodeURIComponent(window.location.href);
                        window.location.href = "{{ route('login') }}" + '?redirect=' + redirectUrl;
                    } else {
                        // Clear saved data if user cancels
                        clearFormData();
                    }
                });
                throw new Error('Unauthorized');
            }
            if (!res.ok) {
                return res.json().then(errData => {
                    throw new Error(errData.message || `HTTP error! status: ${res.status}`);
                });
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Clear saved form data on successful booking
                clearFormData();
                toastr.success(data.message || 'Booking saved successfully!');
                bookingModal.hide();
                setTimeout(() => {
                    window.location.href = "{{ route('user.booking.summary') }}";
                }, 1500);
            } else if (data.status === 'failed') {
                // Handle payment failure with retry option
                Swal.fire({
                    title: 'Payment Failed',
                    text: data.message || 'Your payment was not successful. Would you like to retry?',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Retry Payment',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to retry booking
                        if (data.retry_url) {
                            window.location.href = data.retry_url;
                        } else {
                            // Fallback to booking summary page
                            window.location.href = "{{ route('user.booking.summary') }}";
                        }
                    } else {
                        // User cancelled, show option to go back to event
                        Swal.fire({
                            title: 'Booking Cancelled',
                            text: 'You can try booking again anytime.',
                            icon: 'info',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#6c757d'
                        });
                    }
                });
            } else {
                toastr.error(data.message || 'Something went wrong.');
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('Fetch error:', error);
                toastr.error(error.message || 'Request failed.');
            }
        });
    });
});

function copyToClipboard() {
    const eventTitle = `{{ isset($allEvent) ? addslashes($allEvent->heading) : (isset($event) && isset($eventDetail) && $eventDetail ? addslashes($eventDetail->heading) : (isset($event) ? $event->heading : 'Event')) }}`;
    const eventDate = `{{ isset($allEvent) ? ($allEvent->date ?? '') : ($eventDetail ? $eventDetail->starting_date : ($event->starting_date ?? '')) }}`;
    const eventLocation = `{{ isset($allEvent) ? ($allEvent->mini_heading ?? '') : ($eventDetail ? $eventDetail->event_mode : ($event->event_mode ?? '')) }}`;
    const eventPrice = `{{ isset($allEvent) ? formatPrice(isset($eventDetail) && $eventDetail ? $eventDetail->starting_fees : $allEvent->starting_fees) : formatPrice($eventDetail ? $eventDetail->starting_fees : ($event->starting_fees ?? 0)) }}`;

    const eventDetails = `Check out this event: ${eventTitle}\n\n` +
                        `Date: ${eventDate}\n` +
                        `Location: ${eventLocation}\n` +
                        `Price: ${eventPrice}\n\n` +
                        `More details: {{ Request::url() }}`;

    navigator.clipboard.writeText(eventDetails).then(() => {
        const copyMessage = document.getElementById('copyMessage');
        copyMessage.style.display = 'block';
        setTimeout(() => {
            copyMessage.style.display = 'none';
        }, 3000);
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
}
</script>

<style>
.share-button {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 15px;
}

.share-link {
    font-size: 24px;
    color: #666;
    transition: all 0.3s ease;
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.share-link:hover {
    transform: translateY(-3px);
}

.share-link.facebook:hover {
    color: #1877f2;
}

.share-link.twitter:hover {
    color: #1da1f2;
}

.share-link.whatsapp:hover {
    color: #25d366;
}

.share-link.instagram:hover {
    color: #e4405f;
}

.share-link.email:hover {
    color: #ea4335;
}

.share-content h2 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 15px;
}

.copy-message {
    margin-top: 10px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    color: #28a745;
    font-size: 0.9rem;
    text-align: center;
    animation: fadeOut 3s forwards;
}

@keyframes fadeOut {
    0% { opacity: 1; }
    70% { opacity: 1; }
    100% { opacity: 0; }
}
</style>

@endsection