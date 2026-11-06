<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Create your YuktiOne customer account and connect with trusted professionals.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#316D61">
    <title>Create a Customer Account | YuktiOne</title>

    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite([
        'resources/css/tazen.css',
        'resources/css/yuktione-feedback.css',
        'resources/css/customer-panel.css',
        'resources/css/auth-theme.css',
    ])
</head>
<body data-yuktione-panel="customer" class="yuktione-auth-page customer-registration-page min-h-dvh bg-background font-sans text-text-primary antialiased">
    <main class="relative isolate flex min-h-dvh w-full items-center justify-center px-4 py-6 sm:px-6 sm:py-8">
        <section class="relative w-full max-w-[440px] rounded-panel border border-divider bg-white p-5 shadow-popover sm:p-7" aria-labelledby="registration-title">
            <header class="mb-6 text-center">
                <a href="{{ route('home') }}" aria-label="YuktiOne home" class="mb-4 inline-flex items-center justify-center rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus-ring">
                    <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="180" height="58" class="h-10 w-auto max-w-44 object-contain">
                </a>
                <h1 id="registration-title" class="font-display text-2xl font-semibold leading-tight tracking-tight text-brand-950 sm:text-[1.75rem]">
                    Create your account
                </h1>
                <p class="mt-2 text-sm leading-5 text-text-secondary">
                    Join YuktiOne to connect with trusted professionals.
                </p>
            </header>

            <ol class="relative mb-6 grid grid-cols-2" aria-label="Registration progress">
                <span id="stepConnector" aria-hidden="true" class="absolute left-1/4 right-1/4 top-3.5 h-0.5 -translate-y-1/2 bg-brand-100"></span>
                <li id="stepInfo" class="relative z-10 flex flex-col items-center gap-1.5 text-center" aria-current="step">
                    <span class="step-indicator grid size-7 place-items-center rounded-full border-2 border-brand-primary bg-brand-primary text-xs font-semibold text-white">1</span>
                    <span class="step-label text-xs font-semibold text-brand-primary">Personal Info</span>
                </li>
                <li id="stepVerifyPassword" class="relative z-10 flex flex-col items-center gap-1.5 text-center text-text-muted" aria-current="false">
                    <span class="step-indicator grid size-7 place-items-center rounded-full border-2 border-border bg-white text-xs font-semibold text-text-muted">2</span>
                    <span class="step-label text-xs font-medium">Verify &amp; Password</span>
                </li>
            </ol>

            <form
                id="registerForm"
                novalidate
                data-save-lead-url="{{ route('register.save-lead') }}"
                data-send-otp-url="{{ route('register.send-otp') }}"
                data-verify-otp-url="{{ route('register.verify-otp') }}"
                data-create-incomplete-url="{{ route('register.create-incomplete') }}"
                data-complete-url="{{ route('register.complete') }}"
            >
                @csrf

                <section id="personalInfoSection" aria-labelledby="personal-info-title">
                    <h2 id="personal-info-title" class="sr-only">Personal information</h2>
                    <div class="grid grid-cols-1 gap-x-3 gap-y-3 sm:grid-cols-2">
                        <div class="min-w-0">
                            <label for="first_name" class="mb-1 block text-xs font-semibold text-text-primary">First name</label>
                            <input
                                id="first_name"
                                name="first_name"
                                type="text"
                                value="{{ old('first_name') }}"
                                autocomplete="given-name"
                                placeholder="First name"
                                required
                                aria-describedby="first_name-error"
                                aria-invalid="false"
                                class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error"
                            >
                            <p id="first_name-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                        </div>
                        <div class="min-w-0">
                            <label for="last_name" class="mb-1 block text-xs font-semibold text-text-primary">Last name</label>
                            <input
                                id="last_name"
                                name="last_name"
                                type="text"
                                value="{{ old('last_name') }}"
                                autocomplete="family-name"
                                placeholder="Last name"
                                required
                                aria-describedby="last_name-error"
                                aria-invalid="false"
                                class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error"
                            >
                            <p id="last_name-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                        </div>
                        <div class="min-w-0">
                            <label for="phone" class="mb-1 block text-xs font-semibold text-text-primary">Phone number</label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                autocomplete="tel"
                                inputmode="tel"
                                minlength="10"
                                maxlength="15"
                                placeholder="Phone number"
                                required
                                aria-describedby="phone-error"
                                aria-invalid="false"
                                class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error"
                            >
                            <p id="phone-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                        </div>
                        <div class="min-w-0">
                            <label for="email" class="mb-1 block text-xs font-semibold text-text-primary">Email address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                inputmode="email"
                                placeholder="you@example.com"
                                required
                                aria-describedby="email-error"
                                aria-invalid="false"
                                class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error"
                            >
                            <p id="email-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                        </div>
                    </div>

                    <button type="button" id="continueBtn" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-60">
                        Continue
                    </button>
                </section>

                <section id="verifyPasswordSection" hidden aria-labelledby="verification-title">
                    <div class="mb-4 flex items-center gap-2 rounded-control border border-brand-100 bg-brand-50 px-3 py-2.5 text-sm text-brand-900">
                        <svg class="size-4 shrink-0 text-brand-primary" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M3.333 5.833A1.667 1.667 0 0 1 5 4.167h10a1.667 1.667 0 0 1 1.667 1.666v8.334A1.667 1.667 0 0 1 15 15.833H5a1.667 1.667 0 0 1-1.667-1.666V5.833Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="m4.167 5 5.833 4.583L15.833 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="min-w-0 truncate">Code sent to <strong id="verifiedEmail" class="font-semibold"></strong></span>
                    </div>

                    <div id="otpVerificationSection" class="rounded-card border border-divider bg-surface-muted p-3.5 sm:p-4">
                        <div class="mb-3 text-center">
                            <h2 id="verification-title" class="text-sm font-semibold text-brand-950">Verify your email</h2>
                            <p class="mt-1 text-xs leading-5 text-text-secondary">Enter the 6-digit code we sent to your inbox.</p>
                        </div>
                        <div class="flex justify-center gap-2" role="group" aria-label="6-digit email verification code">
                            @for ($digit = 1; $digit <= 6; $digit++)
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}"
                                    maxlength="1"
                                    aria-label="Digit {{ $digit }} of 6"
                                    class="size-10 min-w-0 rounded-control border border-border bg-white text-center text-base font-semibold text-brand-950 focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error sm:size-11"
                                    data-otp-digit
                                >
                            @endfor
                        </div>
                        <input type="hidden" id="fullOtp" name="otp">
                        <p id="otp-error" class="mt-2 hidden text-center text-xs text-error" role="alert"></p>
                        <p id="otpTimer" class="mt-3 text-center text-xs text-text-muted" aria-live="polite">Code expires in 10:00</p>
                        <div class="mt-3 flex items-center justify-center gap-1 text-xs text-text-secondary">
                            <span>Didn't receive it?</span>
                            <button type="button" id="resendOtp" class="inline-flex min-h-9 items-center px-1 font-semibold text-brand-primary underline-offset-2 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-60">Resend code</button>
                        </div>
                        <button type="button" id="verifyOtpBtn" class="mt-2 inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-60">
                            Verify email
                        </button>
                    </div>

                    <div id="passwordInputSection" hidden class="space-y-3">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-950">Create your password</h2>
                            <p class="mt-1 text-xs leading-5 text-text-secondary">Use at least 6 characters to secure your account.</p>
                        </div>
                        <div>
                            <label for="password1" class="mb-1 block text-xs font-semibold text-text-primary">Password</label>
                            <div class="relative">
                                <input id="password1" name="password" type="password" autocomplete="new-password" placeholder="Create a password" aria-describedby="password-error" aria-invalid="false" class="min-h-11 w-full rounded-control border border-border bg-white py-2.5 pl-3 pr-12 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error">
                                <button type="button" data-toggle-password="password" aria-label="Show password" aria-pressed="false" class="absolute inset-y-0 right-0 inline-flex min-w-11 items-center justify-center rounded-r-control text-text-muted hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-[-3px] focus-visible:outline-focus-ring">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z"/>
                                        <circle cx="12" cy="12" r="2.75"/>
                                    </svg>
                                </button>
                            </div>
                            <p id="password-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                        </div>
                        <div>
                            <label for="password2" class="mb-1 block text-xs font-semibold text-text-primary">Confirm password</label>
                            <div class="relative">
                                <input id="password2" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter your password" aria-describedby="password_confirmation-error" aria-invalid="false" class="min-h-11 w-full rounded-control border border-border bg-white py-2.5 pl-3 pr-12 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error">
                                <button type="button" data-toggle-password="password_confirmation" aria-label="Show password" aria-pressed="false" class="absolute inset-y-0 right-0 inline-flex min-w-11 items-center justify-center rounded-r-control text-text-muted hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-[-3px] focus-visible:outline-focus-ring">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z"/>
                                        <circle cx="12" cy="12" r="2.75"/>
                                    </svg>
                                </button>
                            </div>
                            <p id="password_confirmation-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                        </div>

                        <div class="rounded-control border border-divider bg-surface-muted px-3 py-2.5">
                            <div class="flex items-start gap-2.5 text-xs leading-5 text-text-secondary">
                                <input id="terms_accepted" name="terms_accepted" type="checkbox" value="1" class="mt-0.5 size-4 shrink-0 rounded border-border accent-brand-primary focus-visible:outline-2 focus-visible:outline-focus-ring">
                                <p class="m-0">
                                    <label for="terms_accepted" class="cursor-pointer">I agree to YuktiOne's</label>
                                    <button type="button" id="openTerms" class="font-semibold text-brand-primary underline underline-offset-2 hover:text-brand-800">Terms and Conditions</button>.
                                </p>
                            </div>
                            <p id="terms-error" class="mt-1 hidden pl-6 text-xs text-error" role="alert">Please accept the Terms and Conditions to continue.</p>
                        </div>

                        <button type="submit" id="registerButton" class="inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-60">
                            Create account
                        </button>
                    </div>

                    <button type="button" id="backToInfoBtn" class="mt-3 inline-flex min-h-10 w-full items-center justify-center rounded-control border border-brand-primary bg-white px-4 text-sm font-medium text-brand-primary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                        Back to personal information
                    </button>
                </section>

                <p class="mt-5 text-center text-sm text-text-secondary">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-brand-primary underline-offset-2 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-focus-ring">Sign in</a>
                </p>
            </form>

            <p class="mt-5 text-center text-xs text-text-muted">© {{ date('Y') }} YuktiOne</p>
        </section>
    </main>

    <dialog id="termsDialog" aria-labelledby="termsDialogTitle" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-panel border border-border bg-white p-0 text-text-primary shadow-popover backdrop:bg-brand-950/50">
        <div class="flex items-center justify-between gap-4 border-b border-divider px-4 py-3 sm:px-6">
            <h2 id="termsDialogTitle" class="font-display text-lg font-semibold text-brand-950">Terms and Conditions</h2>
            <button type="button" data-close-terms class="inline-flex size-9 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-focus-ring" aria-label="Close terms">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>
        <div tabindex="0" class="max-h-[65dvh] overflow-y-auto px-4 py-4 text-sm leading-6 text-text-secondary sm:px-6">
            @include('frontend.partials.terms_agreement')
        </div>
        <div class="flex justify-end border-t border-divider px-4 py-3 sm:px-6">
            <button type="button" data-close-terms class="inline-flex min-h-10 items-center justify-center rounded-control bg-brand-primary px-4 text-sm font-semibold text-white hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-focus-ring">Close</button>
        </div>
    </dialog>

    <x-yuktione.feedback />
    @vite([
        'resources/js/yuktione-feedback.js',
        'resources/js/customer-registration.js',
    ])
</body>
</html>
