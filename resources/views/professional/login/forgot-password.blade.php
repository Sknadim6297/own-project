<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#36877F">
    <title>Forgot Password | YuktiOne</title>
    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite(['resources/css/tazen.css', 'resources/css/auth-theme.css', 'resources/css/yuktione-feedback.css'])
</head>
<body class="yuktione-auth-page min-h-screen bg-background px-4 py-8 font-sans text-text-primary sm:px-6">
    <main class="mx-auto flex min-h-[75vh] w-full max-w-lg items-center justify-center">
        <section class="w-full rounded-panel border border-border bg-white p-6 shadow-card sm:p-9">
            <a href="{{ route('home') }}" class="mb-7 flex justify-center" aria-label="Yuktione home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" class="h-auto max-h-16 w-auto max-w-[210px] object-contain">
            </a>
            <div class="mb-7">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Account recovery</p>
                <h1 class="text-2xl font-semibold text-brand-secondary">Forgot password?</h1>
                <p class="mt-2 text-sm leading-6 text-text-secondary">Enter the mobile number on your professional account. If it is registered, we’ll send password reset instructions to the email address on that account.</p>
            </div>

            @include('frontend.login.partials.recovery-not-registered', ['registerUrl' => route('professional.register')])

            @if (session('status'))
                <div class="mb-5 rounded-card border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('professional.forgot.send') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-text-primary">Mobile number</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" minlength="10" maxlength="20" autofocus placeholder="10-digit mobile number"
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary placeholder:text-text-muted shadow-sm outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-100 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <span class="mt-2 block text-sm text-red-700" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-control bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200">
                    {{ __('Send Password Reset Link') }}
                </button>
                <a href="{{ route('professional.login') }}" class="block text-center text-sm font-medium text-brand-primary transition hover:text-brand-800">Back to Login</a>
            </form>
        </section>
    </main>
    <x-yuktione.feedback />
    @vite('resources/js/yuktione-feedback.js')
    <script type="module">
        const unregistered = @json((bool) session('recovery_unregistered'));
        const status = @json(session('status'));
        const error = @json($errors->first('phone'));
        const notify = () => {
            if (!window.YuktioneToast) {
                return false;
            }
            if (status && !unregistered) {
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
    </script>
</body>
</html>
