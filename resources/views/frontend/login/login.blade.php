@extends('layouts.layout')

@php
    $initialRole = request()->is('professional/login') || request('role') === 'professional'
        ? 'professional'
        : 'customer';
@endphp

@push('head')
    <style>
        .yuktione-auth-page #signInButton:disabled {
            border-color: #316D61;
            background-color: #316D61;
            color: #ffffff;
            opacity: 0.72;
            cursor: wait;
        }
    </style>
@endpush

@section('title', 'Sign In | YuktiOne')
@section('meta_description', 'Sign in to your YuktiOne account to manage consultations and bookings.')
@section('body_class', 'yuktione-auth-page')

@section('content')
<main id="loginPanel" data-yuktione-auth class="yuktione-auth-page flex min-h-dvh w-full items-center justify-center bg-background px-4 py-8 sm:px-6">
    <section class="w-full max-w-md rounded-panel border border-border bg-surface p-5 shadow-popover sm:p-8" aria-labelledby="login-heading">
        <div class="mx-auto w-full max-w-md">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus-ring" aria-label="YuktiOne home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="155" height="50" class="h-10 w-auto object-contain object-left">
            </a>

            <div class="mt-4">
                <p id="roleEyebrow" class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                    {{ $initialRole === 'customer' ? 'Customer account' : 'Professional account' }}
                </p>
                <h1 id="login-heading" class="mt-1 font-display text-3xl font-semibold tracking-tight text-brand-secondary">Welcome back</h1>
                <p class="mt-1.5 text-sm leading-5 text-text-secondary">Sign in to continue to your YuktiOne account.</p>
            </div>

            <div class="mt-4 grid grid-cols-2 rounded-control border border-border bg-surface-muted p-1" role="tablist" aria-label="Choose account type">
                <button type="button" id="customerTab" role="tab" aria-controls="loginForm" aria-selected="{{ $initialRole === 'customer' ? 'true' : 'false' }}" tabindex="{{ $initialRole === 'customer' ? '0' : '-1' }}" data-role="customer" class="min-h-10 rounded-[0.25rem] px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-focus-ring {{ $initialRole === 'customer' ? 'bg-surface text-brand-secondary shadow-card' : 'text-text-secondary hover:text-brand-primary' }}">
                    I’m a Customer
                </button>
                <button type="button" id="professionalTab" role="tab" aria-controls="loginForm" aria-selected="{{ $initialRole === 'professional' ? 'true' : 'false' }}" tabindex="{{ $initialRole === 'professional' ? '0' : '-1' }}" data-role="professional" class="min-h-10 rounded-[0.25rem] px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-focus-ring {{ $initialRole === 'professional' ? 'bg-surface text-brand-secondary shadow-card' : 'text-text-secondary hover:text-brand-primary' }}">
                    I’m a Professional
                </button>
            </div>

            <form id="loginForm" action="{{ $initialRole === 'professional' ? route('professional.store') : route('login.submit') }}" method="POST" class="mt-3 grid gap-3" novalidate>
                @csrf

                <div class="grid gap-1">
                    <label for="email" class="text-sm font-semibold text-text-primary">Email address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        autocomplete="email"
                        inputmode="email"
                        required
                        aria-describedby="email-error"
                        class="min-h-11 w-full rounded-control border border-border bg-surface px-3.5 py-2.5 text-base text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary sm:text-sm"
                    >
                    <p id="email-error" class="hidden text-xs font-medium text-error" role="alert"></p>
                </div>

                <div class="grid gap-1">
                    <label for="password" class="text-sm font-semibold text-text-primary">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            class="min-h-11 w-full rounded-control border border-border bg-surface py-2.5 pl-3.5 pr-12 text-base text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary sm:text-sm"
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 inline-flex min-w-11 items-center justify-center rounded-r-control text-text-secondary transition-colors hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-[-3px] focus-visible:outline-focus-ring"
                        >
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="2.75" />
                            </svg>
                        </button>
                    </div>
                    <p id="password-error" class="hidden text-xs font-medium text-error" role="alert"></p>
                </div>

                <div class="flex min-h-9 flex-wrap items-center justify-between gap-x-4 gap-y-1">
                    <label for="remember" class="inline-flex min-h-9 cursor-pointer items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" name="remember" id="remember" class="size-4 rounded border-border accent-brand-primary focus-visible:outline-focus-ring">
                        Remember me
                    </label>
                    <a id="forgotLink" href="{{ $initialRole === 'professional' ? route('professional.forgot.form') : route('forgot.form') }}" class="inline-flex min-h-9 items-center text-sm font-semibold text-brand-primary underline-offset-4 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-focus-ring">
                        Forgot password?
                    </a>
                </div>

                <div class="flex items-start gap-2.5 rounded-card border border-border bg-surface-muted px-3 py-2.5">
                    <input type="checkbox" name="terms_accepted" id="terms_accepted" aria-describedby="terms-help terms-error" disabled class="mt-0.5 size-4 shrink-0 rounded border-border accent-brand-primary disabled:cursor-not-allowed">
                    <div class="min-w-0 text-xs leading-5 text-text-secondary">
                        <label for="terms_accepted" class="cursor-pointer">I have read and accept the</label>
                        <button type="button" id="openTerms" class="ml-0.5 font-semibold text-brand-primary underline underline-offset-2 hover:text-brand-800">Terms and Conditions</button>
                        <p id="terms-error" class="mt-1 hidden text-xs font-medium text-error" role="alert">Please read and accept the terms to continue.</p>
                    </div>
                </div>

                <button
                    type="submit"
                    id="signInButton"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-control border border-brand-primary bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white shadow-card transition duration-200 hover:-translate-y-0.5 hover:border-brand-800 hover:bg-brand-800 hover:shadow-popover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-70"
                >
                    <span id="signInLabel">Sign In</span>
                    <svg id="signInSpinner" class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                </button>

                <p class="text-center text-sm text-text-secondary">
                    <span id="registerPrompt">{{ $initialRole === 'professional' ? 'Join YuktiOne as a professional?' : 'New to YuktiOne?' }}</span>
                    <a id="registerLink" href="{{ $initialRole === 'professional' ? route('professional.register') : route('register') }}" class="font-semibold text-brand-primary underline-offset-4 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-focus-ring">
                        {{ $initialRole === 'professional' ? 'Start onboarding' : 'Create an account' }}
                    </a>
                </p>
            </form>
        </div>
    </section>
</main>

<dialog id="termsDialog" @if($initialRole === 'customer') data-yuktione-panel="customer" @endif aria-labelledby="termsDialogTitle" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-panel border border-border bg-surface p-0 text-text-primary shadow-popover backdrop:bg-brand-950/50">
    <div class="flex items-center justify-between gap-4 border-b border-divider px-4 py-3 sm:px-6">
        <h2 id="termsDialogTitle" class="font-display text-lg font-semibold text-brand-secondary">Terms and Conditions</h2>
        <button type="button" id="closeTerms" class="inline-flex size-9 shrink-0 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted hover:text-text-primary focus-visible:outline-2 focus-visible:outline-focus-ring" aria-label="Close terms and conditions">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </div>
    <div id="termsContent" tabindex="0" class="max-h-[60dvh] overflow-y-auto px-4 py-4 text-sm leading-6 text-text-secondary sm:px-6">
        <div id="customerTerms" @if($initialRole !== 'customer') hidden @endif>
            @include('frontend.partials.terms_agreement')
        </div>
        <div id="professionalTerms" @if($initialRole !== 'professional') hidden @endif>
            @include('professional.terms_agreement')
        </div>
    </div>
    <div class="flex flex-col gap-2 border-t border-divider px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p id="termsScrollStatus" class="text-xs text-text-muted" aria-live="polite">Scroll to the end of the terms to enable acceptance.</p>
        <div class="flex justify-end gap-2">
            <button type="button" id="cancelTerms" class="inline-flex min-h-9 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-focus-ring">Close</button>
            <button type="button" id="agreeTerms" disabled class="inline-flex min-h-9 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-50">I Agree</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('loginForm');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const termsAccepted = document.getElementById('terms_accepted');
    const termsDialog = document.getElementById('termsDialog');
    const loginPanel = document.getElementById('loginPanel');
    const termsContent = document.getElementById('termsContent');
    const agreeTerms = document.getElementById('agreeTerms');
    const termsError = document.getElementById('terms-error');
    const signInButton = document.getElementById('signInButton');
    const signInLabel = document.getElementById('signInLabel');
    const signInSpinner = document.getElementById('signInSpinner');
    const roleTabs = [...document.querySelectorAll('[role="tab"][data-role]')];
    const roleLinks = {
        customer: {
            action: @json(route('login.submit')),
            forgot: @json(route('forgot.form')),
            register: @json(route('register')),
            label: 'Create an account',
            prompt: 'New to YuktiOne?',
            eyebrow: 'Customer account',
        },
        professional: {
            action: @json(route('professional.store')),
            forgot: @json(route('professional.forgot.form')),
            register: @json(route('professional.register')),
            label: 'Start onboarding',
            prompt: 'Join YuktiOne as a professional?',
            eyebrow: 'Professional account',
        },
    };
    let role = @json($initialRole);
    let loginRequestInProgress = false;

    const closeTerms = () => termsDialog.close();
    const setFieldError = (field, message) => {
        const error = document.getElementById(`${field.id}-error`);
        if (!error) return;
        error.textContent = message || '';
        error.classList.toggle('hidden', !message);
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
    };
    const showLoginError = (title, message, type = 'error') => {
        const notify = window.YuktioneToast?.[type];
        if (typeof notify === 'function') {
            notify(message, { title });
            return;
        }
        termsError.textContent = message;
        termsError.classList.remove('hidden');
    };
    const setLoading = (loading) => {
        signInButton.disabled = loading;
        signInLabel.textContent = loading ? 'Signing in…' : 'Sign In';
        signInSpinner.classList.toggle('hidden', !loading);
    };
    const setRole = (nextRole, focusTab = false) => {
        if (!roleLinks[nextRole] || role === nextRole) {
            if (focusTab) document.querySelector(`[data-role="${role}"]`).focus();
            return;
        }
        role = nextRole;
        [loginPanel, termsDialog].forEach((element) => {
            if (role === 'customer') element.dataset.yuktionePanel = 'customer';
            else delete element.dataset.yuktionePanel;
        });
        const config = roleLinks[role];
        form.action = config.action;
        document.getElementById('forgotLink').href = config.forgot;
        document.getElementById('registerLink').href = config.register;
        document.getElementById('registerLink').textContent = config.label;
        document.getElementById('registerPrompt').textContent = config.prompt;
        document.getElementById('roleEyebrow').textContent = config.eyebrow;
        document.getElementById('customerTerms').hidden = role !== 'customer';
        document.getElementById('professionalTerms').hidden = role !== 'professional';
        termsAccepted.checked = false;
        termsAccepted.disabled = true;
        termsError.classList.add('hidden');
        agreeTerms.disabled = true;
        roleTabs.forEach((tab) => {
            const selected = tab.dataset.role === role;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
            tab.className = `min-h-10 rounded-[0.25rem] px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-focus-ring ${selected ? 'bg-surface text-brand-secondary shadow-card' : 'text-text-secondary hover:text-brand-primary'}`;
        });
        if (focusTab) document.querySelector(`[data-role="${role}"]`).focus();
    };

    roleTabs.forEach((tab) => {
        tab.addEventListener('click', () => setRole(tab.dataset.role));
        tab.addEventListener('keydown', (event) => {
            const currentIndex = roleTabs.indexOf(tab);
            let nextIndex = currentIndex;
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') nextIndex = (currentIndex + 1) % roleTabs.length;
            else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') nextIndex = (currentIndex - 1 + roleTabs.length) % roleTabs.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = roleTabs.length - 1;
            else return;
            event.preventDefault();
            setRole(roleTabs[nextIndex].dataset.role, true);
        });
    });
    setRole(role);

    togglePassword.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        togglePassword.setAttribute('aria-pressed', String(visible));
        togglePassword.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    });

    document.getElementById('openTerms').addEventListener('click', () => {
        agreeTerms.disabled = true;
        termsContent.scrollTop = 0;
        document.getElementById('termsScrollStatus').textContent = 'Scroll to the end of the terms to enable acceptance.';
        termsDialog.showModal();
        requestAnimationFrame(() => {
            if (termsContent.scrollHeight <= termsContent.clientHeight + 10) {
                agreeTerms.disabled = false;
                document.getElementById('termsScrollStatus').textContent = 'You have reached the end of the terms.';
            }
        });
    });
    termsContent.addEventListener('scroll', () => {
        if (termsContent.scrollTop + termsContent.clientHeight >= termsContent.scrollHeight - 10) {
            agreeTerms.disabled = false;
            document.getElementById('termsScrollStatus').textContent = 'You have reached the end of the terms.';
        }
    });
    ['closeTerms', 'cancelTerms'].forEach((id) => document.getElementById(id).addEventListener('click', closeTerms));
    agreeTerms.addEventListener('click', () => {
        termsAccepted.disabled = false;
        termsAccepted.checked = true;
        termsError.classList.add('hidden');
        closeTerms();
    });
    termsAccepted.addEventListener('change', () => termsError.classList.add('hidden'));
    email.addEventListener('input', () => setFieldError(email, ''));
    password.addEventListener('input', () => setFieldError(password, ''));

    const submitLogin = async (forceLogin = false) => {
        if (loginRequestInProgress) {
            return;
        }

        setFieldError(email, '');
        setFieldError(password, '');
        if (!email.value.trim()) {
            setFieldError(email, 'Please enter your email address.');
            email.focus();
            return;
        }
        if (!email.validity.valid) {
            setFieldError(email, 'Please enter a valid email address.');
            email.focus();
            return;
        }
        if (!password.value) {
            setFieldError(password, 'Please enter your password.');
            password.focus();
            return;
        }
        if (!termsAccepted.checked) {
            termsError.textContent = 'Please read and accept the terms to continue.';
            termsError.classList.remove('hidden');
            showLoginError('Terms required', 'Please read and accept the Terms and Conditions to continue.', 'warning');
            document.getElementById('openTerms').focus();
            return;
        }

        const formData = new FormData(form);
        if (forceLogin && role === 'customer') formData.set('force_login', '1');
        const url = forceLogin && role === 'customer' ? @json(route('force.login')) : form.action;

        loginRequestInProgress = true;
        setLoading(true);
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            let payload = {};
            try {
                payload = await response.json();
            } catch (error) {
                payload = {};
            }

            if (response.status === 429) {
                showLoginError('Too many attempts', 'Please wait a moment before trying again.', 'warning');
                return;
            }

            if (response.status === 419) {
                showLoginError('Session expired', 'Your session has expired. Refresh the page and try again.', 'warning');
                return;
            }

            if (response.ok && payload.status === 'confirm_logout' && role === 'customer' && !forceLogin) {
                setLoading(false);
                signInButton.focus();
                const confirmed = await window.YuktioneDialog.confirm({
                    title: "You're signed in elsewhere",
                    message: 'Another active session was detected. Would you like to sign out of your other sessions and continue on this device?',
                    cancelText: 'Cancel',
                    confirmText: 'Continue Here',
                    variant: 'danger',
                });
                if (confirmed) {
                    loginRequestInProgress = false;
                    await submitLogin(true);
                }
                return;
            }
            if (payload.status === 'confirm_logout' && forceLogin) {
                showLoginError('Something went wrong', "We couldn't complete your request right now. Please try again later.");
                return;
            }
            if (payload.redirect_url && (response.ok || payload.status === 'rejected')) {
                const rejected = payload.status === 'rejected';
                showLoginError(
                    rejected ? 'Profile needs attention' : 'Signed in',
                    payload.message || (rejected
                        ? 'Your profile has been rejected. Please review and resubmit your details.'
                        : 'You are successfully logged in!'),
                    rejected ? 'warning' : 'success'
                );
                window.setTimeout(() => window.location.assign(payload.redirect_url), 700);
                return;
            }
            if (response.status === 422 && payload.errors && typeof payload.errors === 'object') {
                Object.entries(payload.errors).forEach(([field, messages]) => {
                    if (field === 'email') setFieldError(email, messages[0]);
                    else if (field === 'password') setFieldError(password, messages[0]);
                });
                return;
            }

            if (response.status === 401) {
                showLoginError('Unable to sign in', payload.message || 'Incorrect email or password. Please check your details and try again.');
                return;
            }

            if (response.status === 403 && payload.status === 'deactivated') {
                showLoginError('Account unavailable', 'This professional account is currently unavailable. Please contact support.');
                return;
            }

            if (response.status >= 500 || !response.ok) {
                showLoginError('Something went wrong', "We couldn't complete your request right now. Please try again later.");
                return;
            }

            showLoginError('Something went wrong', "We couldn't complete your request right now. Please try again later.");
        } catch (error) {
            showLoginError('Something went wrong', "We couldn't complete your request right now. Please try again later.");
        } finally {
            loginRequestInProgress = false;
            setLoading(false);
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitLogin();
    });

})();
</script>
@endpush
