<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#316D61">
    <title>Reset Password | YuktiOne</title>
    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite(['resources/css/tazen.css', 'resources/css/auth-theme.css', 'resources/css/yuktione-feedback.css'])
    <style>
        .password-strength { height: 4px; margin-top: .5rem; overflow: hidden; border-radius: 9999px; background: #e0e9f0; }
        .password-strength-meter { height: 100%; width: 0; transition: width .2s ease, background-color .2s ease; }
        .strength-weak { width: 33.333%; background: #dc2626; }
        .strength-medium { width: 66.666%; background: #d97706; }
        .strength-strong { width: 100%; background: #316D61; }
    </style>
</head>
<body class="yuktione-auth-page min-h-screen bg-background px-4 py-8 font-sans text-text-primary sm:px-6">
    <main class="mx-auto flex min-h-[75vh] w-full max-w-lg items-center justify-center">
        <section class="w-full rounded-panel border border-border bg-white p-6 shadow-card sm:p-9" aria-labelledby="reset-title">
            <a href="{{ route('home') }}" class="mb-7 flex justify-center" aria-label="YuktiOne home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" class="h-auto max-h-16 w-auto max-w-[210px] object-contain">
            </a>
            <div class="mb-7">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Secure your account</p>
                <h1 id="reset-title" class="text-2xl font-semibold text-brand-secondary">Reset password</h1>
                <p class="mt-2 text-sm leading-6 text-text-secondary">Choose a new password for your YuktiOne account.</p>
            </div>

            <form method="POST" action="{{ route('password.reset') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-text-primary">{{ __('E-Mail Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary placeholder:text-text-muted shadow-sm outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-100 @error('email') border-red-500 @enderror">
                    @error('email')
                        <span class="mt-2 block text-sm text-red-700" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-text-primary">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary placeholder:text-text-muted shadow-sm outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-100 @error('password') border-red-500 @enderror">
                    <div class="password-strength" aria-hidden="true"><div class="password-strength-meter"></div></div>
                    @error('password')
                        <span class="mt-2 block text-sm text-red-700" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <div>
                    <label for="password-confirm" class="mb-2 block text-sm font-medium text-text-primary">{{ __('Confirm Password') }}</label>
                    <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary placeholder:text-text-muted shadow-sm outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-100">
                </div>
                <button type="submit" class="w-full rounded-control bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200">
                    {{ __('Reset Password') }}
                </button>
                <a href="{{ route('login') }}" class="block text-center text-sm font-medium text-brand-primary transition hover:text-brand-800">Back to Login</a>
            </form>
        </section>
    </main>
    <x-yuktione.feedback />
    @vite('resources/js/yuktione-feedback.js')
    <script type="module">
        const error = @json($errors->first());
        const notify = () => {
            if (!window.YuktioneToast || !error) {
                return Boolean(window.YuktioneToast);
            }
            window.YuktioneToast.error(error, { title: 'Check your password' });
            return true;
        };
        if (!notify()) {
            window.addEventListener('load', notify, { once: true });
        }
    </script>
    <script>
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.querySelector('.password-strength-meter');

        passwordInput.addEventListener('input', function () {
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength += 1;
            if (/[a-z]+/.test(password)) strength += 1;
            if (/[A-Z]+/.test(password)) strength += 1;
            if (/[0-9]+/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]+/.test(password)) strength += 1;

            strengthMeter.className = 'password-strength-meter';
            strengthMeter.classList.add(strength <= 2 ? 'strength-weak' : strength <= 4 ? 'strength-medium' : 'strength-strong');
        });
    </script>
</body>
</html>
