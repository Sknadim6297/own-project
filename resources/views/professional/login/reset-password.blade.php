<!DOCTYPE html>
<html lang="en">
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
</head>
<body class="yuktione-auth-page min-h-screen bg-background px-4 py-8 font-sans text-text-primary sm:px-6">
    <main class="mx-auto flex min-h-[75vh] w-full max-w-lg items-center justify-center">
        <section class="w-full rounded-panel border border-border bg-white p-6 shadow-card sm:p-9">
            <a href="{{ route('home') }}" class="mb-7 flex justify-center" aria-label="YuktiOne home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" class="h-auto max-h-16 w-auto max-w-[210px] object-contain">
            </a>
            <div class="mb-7">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Secure your account</p>
                <h1 class="text-2xl font-semibold text-brand-secondary">Reset password</h1>
                <p class="mt-2 text-sm leading-6 text-text-secondary">Choose a new password for your professional account.</p>
            </div>

            <form method="POST" action="{{ route('professional.password.reset') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-text-primary">{{ __('E-Mail Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary shadow-sm outline-none transition placeholder:text-text-muted focus:border-brand-primary focus:ring-4 focus:ring-brand-100 @error('email') border-red-500 @enderror">
                    @error('email')
                        <span class="mt-2 block text-sm text-red-700" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-text-primary">{{ __('New Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary shadow-sm outline-none transition placeholder:text-text-muted focus:border-brand-primary focus:ring-4 focus:ring-brand-100 @error('password') border-red-500 @enderror">
                    @error('password')
                        <span class="mt-2 block text-sm text-red-700" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div>
                    <label for="password-confirm" class="mb-2 block text-sm font-medium text-text-primary">{{ __('Confirm Password') }}</label>
                    <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-control border border-border bg-white px-4 py-3 text-sm text-text-primary shadow-sm outline-none transition placeholder:text-text-muted focus:border-brand-primary focus:ring-4 focus:ring-brand-100">
                </div>

                <button type="submit" class="w-full rounded-control bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200">
                    {{ __('Reset Password') }}
                </button>
                <a href="{{ route('professional.login') }}" class="block text-center text-sm font-medium text-brand-primary transition hover:text-brand-800">Back to Login</a>
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
</body>
</html>

