<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-type" content="user">
    <meta name="user-id" content="{{ Auth::guard('user')->id() }}">
    <title>@yield('title', config('yuktione.name', 'YuktiOne') . ' | Booking')</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <meta name="theme-color" content="#36877F">
    @include('partials.dm-sans')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    {{-- Bootstrap retained only for existing MCQ modal APIs on success --}}
    <link href="{{ asset('frontend/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    @vite('resources/css/yuktione-feedback.css')
    @vite('resources/css/tazen.css')
    @vite('resources/css/customer-panel.css')
    <link href="{{ asset('frontend/assets/css/yuktione-booking.css') }}?v={{ @filemtime(public_path('frontend/assets/css/yuktione-booking.css')) ?: time() }}" rel="stylesheet">
    <link href="{{ asset('frontend/assets/css/yuktione-customer-booking.css') }}?v={{ @filemtime(public_path('frontend/assets/css/yuktione-customer-booking.css')) ?: time() }}" rel="stylesheet">
    @yield('styles')
    @stack('styles')
</head>
<body data-yuktione-panel="customer" data-yuktione-booking="1" class="min-h-screen bg-background font-sans text-text-primary antialiased">
    <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-control bg-brand-primary px-4 py-3 font-semibold text-white focus:not-sr-only">
        Skip to main content
    </a>

    <div data-panel-overlay hidden class="fixed inset-0 z-40 bg-brand-950/40 lg:hidden"></div>

    @include('customer.sections.sidebar')

    <div class="min-h-screen lg:pl-72">
        @include('customer.sections.header')
        <main id="main-content" class="px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
            @yield('content')
        </main>
    </div>

    <x-yuktione.feedback />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite('resources/js/tazen-v2/panel.js')
    @vite('resources/js/yuktione-feedback.js')

    {{-- Booking blades historically use @section('script'); panel pages use scripts --}}
    @yield('script')
    @yield('scripts')
    @stack('scripts')
</body>
</html>
