<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#316D61">
    <title>Forgot Password | YuktiOne</title>
    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite(['resources/css/tazen.css', 'resources/css/auth-theme.css', 'resources/css/yuktione-feedback.css'])
</head>
<body class="yuktione-auth-page min-h-screen bg-background px-4 py-8 font-sans text-text-primary sm:px-6">
    <main class="mx-auto flex min-h-[75vh] w-full max-w-lg items-center justify-center">
        <section class="w-full rounded-panel border border-border bg-white p-6 shadow-card sm:p-9" aria-labelledby="recovery-title">
            <a href="{{ route('home') }}" class="mb-7 flex justify-center" aria-label="YuktiOne home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" class="h-auto max-h-16 w-auto max-w-[210px] object-contain">
            </a>
            <div class="mb-7">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Account recovery</p>
                <h1 id="recovery-title" class="text-2xl font-semibold text-brand-secondary">Forgot password?</h1>
                <p class="mt-2 text-sm leading-6 text-text-secondary">Enter your registered email address and we'll send you a password reset link.</p>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-card border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form id="forgotPasswordForm" method="POST" action="{{ route('forgot.send') }}" class="space-y-5" novalidate>
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-text-primary">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="Enter your registered email address"
                        aria-describedby="email-error"
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary placeholder:text-text-muted shadow-sm outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-100 @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <span id="email-error" class="mt-2 block text-sm text-red-700" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <button
                    type="submit"
                    id="forgotSubmit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-control bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200 disabled:cursor-wait disabled:opacity-70"
                >
                    <span id="forgotSubmitLabel">Send Password Reset Link</span>
                    <svg id="forgotSubmitSpinner" class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                </button>
                <a href="{{ route('login') }}" class="block text-center text-sm font-medium text-brand-primary transition hover:text-brand-800">Back to Login</a>
            </form>
        </section>
    </main>
    <x-yuktione.feedback />
    @vite('resources/js/yuktione-feedback.js')
    <script type="module">
        const status = @json(session('status'));
        const error = @json($errors->first('email'));
        const form = document.getElementById('forgotPasswordForm');
        const submitButton = document.getElementById('forgotSubmit');
        const submitLabel = document.getElementById('forgotSubmitLabel');
        const submitSpinner = document.getElementById('forgotSubmitSpinner');

        const notify = () => {
            if (!window.YuktioneToast) {
                return false;
            }
            if (status) {
                window.YuktioneToast.success(status, { title: 'Check your email' });
            }
            if (error) {
                window.YuktioneToast.error(error, { title: 'Unable to send reset link' });
            }
            return true;
        };

        if (!notify()) {
            window.addEventListener('load', notify, { once: true });
        }

        form?.addEventListener('submit', () => {
            if (!submitButton) {
                return;
            }
            submitButton.disabled = true;
            submitLabel.textContent = 'Sending…';
            submitSpinner?.classList.remove('hidden');
        });
    </script>
</body>
</html>
