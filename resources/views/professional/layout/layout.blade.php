<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-type" content="professional">
    <meta name="user-id" content="{{ Auth::guard('professional')->id() }}">
    <meta name="theme-color" content="#36877F">
    <title>@yield('title', 'Professional workspace | YuktiOne')</title>

    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    @vite('resources/css/tazen.css')
    @vite('resources/css/yuktione-feedback.css')
    @yield('styles')
    @stack('styles')
    @vite('resources/css/professional-panel.css')
</head>
<body data-yuktione-panel="professional" class="min-h-screen bg-background font-sans text-text-primary antialiased">
    <a
        href="#main-content"
        class="sr-only fixed left-4 top-4 z-[60] rounded-control bg-brand-primary px-4 py-3 font-semibold text-white focus:not-sr-only"
    >
        Skip to main content
    </a>

    <div
        data-panel-overlay
        hidden
        class="fixed inset-0 z-40 bg-brand-950/45 lg:hidden"
    ></div>

    @include('professional.sections.sidebar')

    <div class="min-h-screen lg:pl-72">
        @include('professional.sections.header')

        <main id="main-content" class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
            @yield('content')
        </main>
    </div>

    <a
        href="https://wa.me/+919147421560?text=Hello%20Yuktione!%20I%20need%20assistance%20with%20your%20services."
        target="_blank"
        rel="noopener noreferrer"
        class="fixed bottom-5 right-5 z-30 inline-flex size-12 items-center justify-center rounded-full bg-success text-white shadow-popover transition hover:scale-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
        aria-label="Chat with Yuktione support on WhatsApp"
    >
        <i class="fab fa-whatsapp text-xl" aria-hidden="true"></i>
    </a>

    <x-yuktione.feedback />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @vite('resources/js/tazen-v2/panel.js')
    @yield('scripts')
    @stack('scripts')
    @vite('resources/js/yuktione-feedback.js')

    <script>
        document.addEventListener('click', async (event) => {
            const trigger = event.target.closest('.delete-item');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            const deleteUrl = trigger.dataset.url;
            if (!deleteUrl) {
                console.error('Professional delete action is missing its URL.');
                return;
            }

            const confirmed = await window.YuktioneDialog.confirm({
                title: 'Delete this item?',
                message: "You won't be able to undo this action.",
                confirmText: 'Delete',
                cancelText: 'Cancel',
                variant: 'danger',
            });
            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') {
                    window.YuktioneToast.error(result.message || 'Unable to delete this item.');
                    return;
                }

                window.YuktioneToast.success(result.message || 'Item deleted.');
                window.setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                console.error('Professional delete request failed.', error);
                window.YuktioneToast.error('Something went wrong. Please try again.');
            }
        });
    </script>
</body>
</html>
