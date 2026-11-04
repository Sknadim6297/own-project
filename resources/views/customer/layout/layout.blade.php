<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-type" content="user">
    <meta name="user-id" content="{{ Auth::guard('user')->id() }}">
    <title>@yield('title', 'Customer Panel | Yuktione')</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <meta name="theme-color" content="#36877F">
    @include('partials.dm-sans')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite('resources/css/yuktione-feedback.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    @vite('resources/css/tazen.css')
    @vite('resources/css/customer-panel.css')
    @yield('styles')
    @stack('styles')
</head>
<body data-yuktione-panel="customer" class="min-h-screen bg-background font-sans text-text-primary antialiased">
    <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-control bg-brand-primary px-4 py-3 font-semibold text-white focus:not-sr-only">
        Skip to main content
    </a>

    <div
        data-panel-overlay
        hidden
        class="fixed inset-0 z-40 bg-brand-950/40 lg:hidden"
    ></div>

    @include('customer.sections.sidebar')

    <div class="min-h-screen lg:pl-72">
        @include('customer.sections.header')
        <main id="main-content" class="px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
            @yield('content')
        </main>
    </div>

    <a
        href="https://wa.me/+919147421560?text=Hello%20Yuktione!%20I%20need%20assistance%20with%20your%20services."
        target="_blank"
        rel="noopener noreferrer"
        class="fixed bottom-5 right-5 z-30 inline-flex size-12 items-center justify-center rounded-full bg-success text-white shadow-popover hover:opacity-90"
        aria-label="Chat with us on WhatsApp"
    >
        <i class="fab fa-whatsapp text-xl" aria-hidden="true"></i>
    </a>

    <x-yuktione.feedback />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite('resources/js/tazen-v2/panel.js')
    @vite('resources/js/yuktione-feedback.js')

    <script>
        $(document).ready(function() {
            $('body').on('click', '.delete-item', function(e) {
                e.preventDefault();
                const deleteUrl = $(this).data('url');

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#026FD8",
                    cancelButtonColor: "#b42318",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'DELETE',
                            url: deleteUrl,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire('Deleted!', response.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error!', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Oops!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
