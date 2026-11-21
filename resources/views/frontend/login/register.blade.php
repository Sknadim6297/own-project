<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Create your YuktiOne account — join as a customer for guidance, or as a professional to grow your practice.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#8A0F4D">
    <title>Create your YuktiOne account | YuktiOne</title>

    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite([
        'resources/css/tazen.css',
        'resources/css/yuktione-feedback.css',
        'resources/css/auth-theme.css',
    ])
    <style>
        /* Same plum brand as the professional register page */
        body.yuktione-auth-page.customer-registration-page {
            --yk-primary: #8a0f4d;
            --yk-primary-hover: #c3196b;
            --yk-primary-light: #f8eaf1;
            --yk-heading: #1a1a22;
            --yk-body: #6b6b7a;
            --yk-muted: #6b6b7a;
            --yk-background: #fff8fb;
            --color-brand-50: #fff8fb;
            --color-brand-100: #f8eaf1;
            --color-brand-200: #f0d4e2;
            --color-brand-300: #e0b0c4;
            --color-brand-400: #c3196b;
            --color-brand-500: #8a0f4d;
            --color-brand-600: #c3196b;
            --color-brand-700: #6e0b3c;
            --color-brand-800: #5a0932;
            --color-brand-900: #1f1f1f;
            --color-brand-950: #141414;
            --color-brand-primary: #8a0f4d;
            --color-brand-secondary: #1f1f1f;
            --color-brand-accent: #e0b0c4;
            --color-background: #fff8fb;
            --color-surface: #ffffff;
            --color-surface-muted: #fff1f6;
            --color-text-primary: #1a1a22;
            --color-text-secondary: #6b6b7a;
            --color-text-muted: #6b6b7a;
            --color-border: #d9d9de;
            --color-divider: #efe7ec;
            --color-info: #8a0f4d;
            --color-info-surface: #f8eaf1;
            --color-focus-ring: #8a0f4d;
            accent-color: #8a0f4d;
        }

        body.yuktione-auth-page.customer-registration-page button:disabled {
            border-color: #e2b7cb;
            background-color: #e2b7cb;
            color: #ffffff;
        }

        /* Keep register on plum — override customer-panel teal tokens on this page only */
        body.yuktione-auth-page.customer-registration-page[data-yuktione-panel="customer"],
        .yk-signup[data-yuktione-panel="customer"],
        .yk-signup {
            --yk-primary: #8a0f4d;
            --yk-primary-hover: #c3196b;
            --yk-primary-light: #f8eaf1;
            --yk-heading: #1a1a22;
            --yk-body: #6b6b7a;
            --yk-muted: #6b6b7a;
            --yk-mint: #fff8fb;
            --yk-eucalyptus: #f8eaf1;
            --color-brand-50: #fff8fb;
            --color-brand-100: #f8eaf1;
            --color-brand-200: #f0d4e2;
            --color-brand-300: #e0b0c4;
            --color-brand-400: #c3196b;
            --color-brand-500: #8a0f4d;
            --color-brand-600: #c3196b;
            --color-brand-700: #6e0b3c;
            --color-brand-800: #5a0932;
            --color-brand-900: #1f1f1f;
            --color-brand-950: #141414;
            --color-brand-primary: #8a0f4d;
            --color-brand-secondary: #1f1f1f;
            --color-brand-accent: #e0b0c4;
            --color-info: #8a0f4d;
            --color-info-surface: #f8eaf1;
            --color-focus-ring: #8a0f4d;
            --color-text-primary: #1a1a22;
            --plum: #8a0f4d;
            --magenta: #c3196b;
            --ink: #1a1a22;
            --muted: #6b6b7a;
            --line: #e5e7eb;
            --soft: #f8eaf1;
            --soft-2: #fff1f6;
            --ease: 200ms ease;
        }

        .yk-signup {
            min-height: 100dvh;
            background: #fff;
            font-family: "DM Sans", system-ui, sans-serif;
            color: var(--ink);
        }

        .yk-signup__shell {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 100dvh;
            background: transparent;
            overflow: visible;
        }

        .yk-signup__brand {
            --brand-photo: url('/frontend/assets/img/register-hero.png');
            position: relative;
            display: block;
            overflow: hidden;
            color: #fff;
            flex: 0 0 auto;
            background:
                var(--brand-photo) center / cover no-repeat,
                linear-gradient(145deg, #7a0d44 0%, #8a0f4d 42%, #a0145c 100%);
        }

        .yk-signup__brand-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(
                    180deg,
                    rgba(90, 8, 48, 0.78) 0%,
                    rgba(122, 12, 68, 0.62) 42%,
                    rgba(138, 15, 77, 0.42) 72%,
                    rgba(90, 8, 48, 0.55) 100%
                );
            pointer-events: none;
        }

        .yk-signup__brand-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
            padding: 1.35rem 1.25rem 3.25rem;
            gap: 1.1rem;
        }

        .yk-signup__brand-mark {
            display: none;
        }

        .yk-signup__brand-mark img {
            height: 2.5rem;
            width: auto;
            max-width: 11rem;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .yk-signup__brand-copy {
            max-width: 22rem;
            margin: 0;
        }

        .yk-signup__brand-copy h1 {
            margin: 0;
            font-size: clamp(1.4rem, 5.4vw, 1.7rem);
            font-weight: 750;
            line-height: 1.22;
            letter-spacing: -0.03em;
            color: #fff;
        }

        .yk-signup__brand-copy h1 em {
            font-style: normal;
            position: relative;
            display: inline-block;
        }

        .yk-signup__brand-copy h1 em::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -0.16em;
            height: 0.28em;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 12' fill='none'%3E%3Cpath d='M2 8c20-6 40 4 58-2s38-2 58 2' stroke='%23f4a0c6' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E") center / 100% 100% no-repeat;
        }

        .yk-signup__brand-copy > p {
            margin: 0.7rem 0 0;
            font-size: 0.84rem;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.92);
        }

        .yk-signup__points {
            margin: 0.15rem 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.7rem;
        }

        .yk-signup__points li {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: #fff;
        }

        .yk-signup__points i {
            display: grid;
            place-items: center;
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.16);
            color: #fff;
            flex: 0 0 auto;
        }

        .yk-signup__points svg {
            width: 0.78rem;
            height: 0.78rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .yk-signup__panel {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin-top: -2rem;
            padding: 0 1rem 1.75rem;
            background: transparent;
        }

        .yk-signup__card {
            width: 100%;
            max-width: 420px;
            padding: 1.45rem 1.2rem 1.35rem;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 16px 40px rgba(30, 20, 40, 0.14);
        }

        .yk-signup__logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.15rem;
        }

        .yk-signup__logo img {
            height: 2.35rem;
            width: auto;
            max-width: 10rem;
            object-fit: contain;
        }

        .yk-signup__title {
            margin: 0;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 750;
            letter-spacing: -0.03em;
            color: var(--ink);
        }

        .yk-signup__lead {
            margin: 0.45rem 0 0;
            text-align: center;
            font-size: 0.88rem;
            line-height: 1.45;
            color: var(--muted);
        }

        .yk-signup__roles {
            display: grid;
            gap: 0.75rem;
            margin-top: 1.35rem;
        }

        .yk-signup__role {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 0.8rem;
            align-items: center;
            width: 100%;
            padding: 0.95rem 1rem;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: border-color var(--ease), background var(--ease), box-shadow var(--ease);
        }

        .yk-signup__role:hover {
            border-color: #d8b4c6;
        }

        .yk-signup__role:focus-visible {
            outline: 2px solid var(--plum);
            outline-offset: 2px;
        }

        .yk-signup__role.is-selected {
            border-color: rgba(138, 15, 77, 0.28);
            background: var(--soft);
            box-shadow: none;
        }

        .yk-signup__role-icon {
            display: grid;
            place-items: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: #fff;
            color: var(--plum);
            box-shadow: 0 1px 2px rgba(31, 31, 31, 0.06);
        }

        .yk-signup__role:not(.is-selected) .yk-signup__role-icon {
            background: #f5f5f7;
            color: #4b4b57;
        }

        .yk-signup__role-icon svg {
            width: 1.1rem;
            height: 1.1rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .yk-signup__role-copy strong {
            display: block;
            font-size: 0.92rem;
            font-weight: 750;
            color: var(--ink);
            letter-spacing: -0.01em;
        }

        .yk-signup__role-copy span {
            display: block;
            margin-top: 0.18rem;
            font-size: 0.76rem;
            line-height: 1.4;
            color: var(--muted);
        }

        .yk-signup__role-chevron {
            color: #c4c4cc;
        }

        .yk-signup__role.is-selected .yk-signup__role-chevron {
            color: var(--plum);
        }

        .yk-signup__role-chevron svg {
            width: 1.05rem;
            height: 1.05rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.1;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .yk-signup__continue {
            margin-top: 1.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            width: 100%;
            min-height: 2.85rem;
            border: 0;
            border-radius: 12px;
            background: var(--plum);
            color: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            transition: background var(--ease), opacity var(--ease);
        }

        .yk-signup__continue svg {
            width: 1.05rem;
            height: 1.05rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.1;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .yk-signup__continue:hover:not(:disabled) {
            background: var(--magenta);
        }

        .yk-signup__continue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #c48aa8;
        }

        .yk-signup__foot {
            margin-top: 1.15rem;
            text-align: center;
            font-size: 0.84rem;
            color: var(--muted);
        }

        .yk-signup__foot a {
            font-weight: 700;
            color: var(--plum);
            text-decoration: none;
        }

        .yk-signup__foot a:hover {
            text-decoration: underline;
            text-underline-offset: 0.14em;
        }

        .yk-signup__back {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.75rem;
            border: 0;
            padding: 0;
            background: transparent;
            color: var(--plum);
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .yk-signup__back svg {
            width: 0.9rem;
            height: 0.9rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .yk-signup__form-head {
            margin-bottom: 1rem;
            text-align: center;
        }

        .yk-signup__form-head .yk-signup__title {
            font-size: 1.35rem;
        }

        .yk-signup__badge {
            margin: 0 0 0.35rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--plum);
        }

        @media (min-width: 900px) {
            .yk-signup {
                display: grid;
                place-items: center;
                padding: 1.25rem;
                background: #eef0f3;
            }

            .yk-signup__shell {
                display: grid;
                grid-template-columns: 1.12fr 0.88fr;
                width: min(1040px, 100%);
                min-height: min(660px, calc(100dvh - 2.5rem));
                max-height: calc(100dvh - 2.5rem);
                border-radius: 24px;
                box-shadow: 0 22px 54px rgba(20, 20, 30, 0.14);
                background: #fff;
                overflow: hidden;
            }

            .yk-signup__brand {
                display: block;
            }

            .yk-signup__brand-overlay {
                background:
                    linear-gradient(
                        105deg,
                        rgba(90, 8, 48, 0.72) 0%,
                        rgba(122, 12, 68, 0.52) 38%,
                        rgba(138, 15, 77, 0.28) 68%,
                        rgba(40, 10, 28, 0.12) 100%
                    );
            }

            .yk-signup__brand-inner {
                justify-content: space-between;
                padding: 1.9rem 1.8rem 1.95rem;
                gap: 1.35rem;
            }

            .yk-signup__brand-mark {
                display: inline-flex;
                align-items: center;
            }

            .yk-signup__brand-copy {
                max-width: 21rem;
                margin-top: auto;
                margin-bottom: auto;
            }

            .yk-signup__brand-copy h1 {
                font-size: clamp(1.55rem, 2.2vw, 1.95rem);
            }

            .yk-signup__brand-copy > p {
                margin: 0.85rem 0 0;
                font-size: 0.875rem;
                line-height: 1.55;
            }

            .yk-signup__points {
                margin: 0;
                gap: 0.75rem;
            }

            .yk-signup__points li {
                font-size: 0.875rem;
            }

            .yk-signup__panel {
                margin-top: 0;
                align-items: center;
                padding: 2.25rem 2.4rem;
                background: #fff;
            }

            .yk-signup__card {
                width: min(380px, 100%);
                max-width: none;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .yk-signup__role,
            .yk-signup__continue {
                transition: none !important;
            }
        }

        /* views are toggled with the hidden attribute */
        #accountTypeStep[hidden],
        #customerRegisterStep[hidden] {
            display: none !important;
        }
    </style>
</head>
<body class="yuktione-auth-page customer-registration-page min-h-screen bg-background font-sans text-text-primary antialiased">
    @php
        $accountAs = strtolower((string) request()->query('as', ''));
        $startAsCustomer = $accountAs === 'customer';
        $professionalRegisterUrl = route('professional.register');
    @endphp

    <div id="accountTypeStep" class="yk-signup" @if ($startAsCustomer) hidden @endif>
        <main class="yk-signup__shell">
        <aside class="yk-signup__brand" aria-label="YuktiOne introduction">
            {{-- BG image later: set --brand-photo on .yk-signup__brand --}}
            <div class="yk-signup__brand-overlay" aria-hidden="true"></div>
            <div class="yk-signup__brand-inner">
                <div class="yk-signup__brand-mark">
                    <a href="{{ route('home') }}" aria-label="YuktiOne home" style="display:inline-block;line-height:0;">
                        <img
                            src="{{ asset('frontend/assets/Yuktione_logo.png') }}"
                            alt="YuktiOne"
                            width="160"
                            height="52"
                        >
                    </a>
                </div>

                <div class="yk-signup__brand-copy">
                    <h1>Find the Right Expert or Grow Your Professional <em>Journey</em></h1>
                    <p>A trusted marketplace to discover reliable services, connect with verified professionals, and get things done easily.</p>
                </div>

                <ul class="yk-signup__points">
                    <li>
                        <i aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.4 2.9 8.4 7 9.5 4.1-1.1 7-5.1 7-9.5V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </i>
                        Verified Professionals
                    </li>
                    <li>
                        <i aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6Z"/></svg>
                        </i>
                        Easy &amp; Secure Booking
                    </li>
                    <li>
                        <i aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/><path d="M8 21v-1a5 5 0 0 1 5-5h6"/><path d="M6 11a3.2 3.2 0 1 0-3.2-3.2A3.2 3.2 0 0 0 6 11Z"/><path d="M2 21v-.7A3.8 3.8 0 0 1 5.8 16.5H8"/></svg>
                        </i>
                        Trusted by Thousands
                    </li>
                </ul>
            </div>
        </aside>

        <section class="yk-signup__panel">
            <div class="yk-signup__card">
                    <a href="{{ route('home') }}" class="yk-signup__logo" aria-label="YuktiOne home">
                        <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="160" height="52">
                    </a>

                    <h1 id="registration-title" class="yk-signup__title">Create Your Account</h1>
                    <p class="yk-signup__lead">Join YuktiOne and get started in just a few steps.</p>

                    <div class="yk-signup__roles" role="radiogroup" aria-label="Choose account type">
                        <button
                            type="button"
                            class="yk-signup__role is-selected"
                            role="radio"
                            aria-checked="true"
                            data-account-type="customer"
                            id="roleCustomer"
                        >
                            <span class="yk-signup__role-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/><path d="M8 21v-1a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v1"/><path d="M6 11a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 6 11Z"/><path d="M2 21v-.8A4.2 4.2 0 0 1 6.2 16H8"/></svg>
                            </span>
                            <span class="yk-signup__role-copy">
                                <strong>Register as a Customer</strong>
                                <span>Discover services, book experts, and manage your needs easily.</span>
                            </span>
                            <span class="yk-signup__role-chevron" aria-hidden="true">
                                <svg viewBox="0 0 20 20"><path d="m7.5 4.5 5 5.5-5 5.5"/></svg>
                            </span>
                        </button>

                        <button
                            type="button"
                            class="yk-signup__role"
                            role="radio"
                            aria-checked="false"
                            data-account-type="professional"
                            id="roleProfessional"
                        >
                            <span class="yk-signup__role-icon yk-signup__role-icon--pro" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M8 7V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7"/><path d="M5 7h14v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2Z"/><path d="M10 12h4"/></svg>
                            </span>
                            <span class="yk-signup__role-copy">
                                <strong>Register as a Professional</strong>
                                <span>Showcase your skills, get more clients, and grow your business.</span>
                            </span>
                            <span class="yk-signup__role-chevron" aria-hidden="true">
                                <svg viewBox="0 0 20 20"><path d="m7.5 4.5 5 5.5-5 5.5"/></svg>
                            </span>
                        </button>
                    </div>

                    <button
                        type="button"
                        id="accountTypeContinue"
                        class="yk-signup__continue"
                        data-professional-register-url="{{ route('professional.register') }}"
                    >
                        Continue
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M11 5l5 5-5 5"/></svg>
                    </button>

                    <p class="yk-signup__foot">
                        Already have an account?
                        <a href="{{ route('login') }}">Sign In</a>
                    </p>
            </div>
        </section>
        </main>
    </div>

    <main id="customerRegisterStep" class="flex min-h-screen items-center justify-center bg-linear-to-br from-brand-50 via-background to-white px-3 py-5 sm:px-5" @unless ($startAsCustomer) hidden @endunless>
        <section class="w-full max-w-xl rounded-panel border border-divider bg-surface p-4 shadow-card sm:p-6 md:p-8">
                <header class="mb-5 sm:mb-7">
                    <div class="mb-4 flex items-center justify-between gap-3 sm:mb-6">
                        <a href="{{ route('home') }}" aria-label="YuktiOne home" class="inline-flex items-center">
                            <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" class="h-8 w-auto max-w-36 object-contain sm:h-9">
                        </a>
                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[9px] font-semibold tracking-wide text-brand-800 sm:px-3 sm:text-[10px]">CUSTOMER SIGNUP</span>
                    </div>

                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <h1 id="customer-registration-title" class="text-xl font-semibold leading-tight tracking-tight text-brand-950 sm:text-2xl md:text-3xl">Create your account</h1>
                        <span class="shrink-0 text-xs font-medium text-text-muted" data-registration-step-label>Step 1 of 2</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-6 text-text-muted">Join YuktiOne to connect with trusted professionals.</p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-brand-100" role="progressbar" aria-label="Registration progress" aria-valuemin="1" aria-valuemax="2" aria-valuenow="1">
                        <div class="h-full w-1/2 rounded-full bg-brand-primary transition-[width] duration-300" data-registration-progress></div>
                    </div>
                    <button type="button" id="backToAccountType" class="mt-3 inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-brand-primary underline-offset-2 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-focus-ring">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                        Change account type
                    </button>
                </header>

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
                                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" placeholder="First name" required aria-describedby="first_name-error" aria-invalid="false" class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error">
                                    <p id="first_name-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                                </div>
                                <div class="min-w-0">
                                    <label for="last_name" class="mb-1 block text-xs font-semibold text-text-primary">Last name</label>
                                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name" placeholder="Last name" required aria-describedby="last_name-error" aria-invalid="false" class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error">
                                    <p id="last_name-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                                </div>
                                <div class="min-w-0">
                                    <label for="phone" class="mb-1 block text-xs font-semibold text-text-primary">Phone number</label>
                                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" minlength="10" maxlength="15" placeholder="Phone number" required aria-describedby="phone-error" aria-invalid="false" class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error">
                                    <p id="phone-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                                </div>
                                <div class="min-w-0">
                                    <label for="email" class="mb-1 block text-xs font-semibold text-text-primary">Email address</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="you@example.com" required aria-describedby="email-error" aria-invalid="false" class="min-h-11 w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error">
                                    <p id="email-error" class="mt-1 hidden text-xs text-error" role="alert"></p>
                                </div>
                            </div>

                            <button type="button" id="continueBtn" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-wait disabled:opacity-60">
                                Continue
                            </button>
                        </section>

                        <section id="verifyPasswordSection" hidden aria-labelledby="verification-title">
                            <div class="mb-4 flex items-start gap-2 rounded-control border border-brand-100 bg-brand-50 px-3 py-2.5 text-sm text-brand-900">
                                <svg class="size-4 shrink-0 text-brand-primary" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M3.333 5.833A1.667 1.667 0 0 1 5 4.167h10a1.667 1.667 0 0 1 1.667 1.666v8.334A1.667 1.667 0 0 1 15 15.833H5a1.667 1.667 0 0 1-1.667-1.666V5.833Z" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="m4.167 5 5.833 4.583L15.833 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="min-w-0 break-words">Code sent to <strong id="verifiedEmail" class="font-semibold"></strong></span>
                            </div>

                            <div id="otpVerificationSection" class="rounded-card border border-divider bg-surface-muted p-3.5 sm:p-4">
                                <div class="mb-3 text-center">
                                    <h2 id="verification-title" class="text-sm font-semibold text-brand-950">Verify your email</h2>
                                    <p class="mt-1 text-xs leading-5 text-text-secondary">Enter the 6-digit code we sent to your inbox.</p>
                                </div>
                                <div class="flex justify-center gap-2" role="group" aria-label="6-digit email verification code">
                                    @for ($digit = 1; $digit <= 6; $digit++)
                                        <input type="text" inputmode="numeric" autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}" maxlength="1" aria-label="Digit {{ $digit }} of 6" class="size-10 min-w-0 rounded-control border border-border bg-white text-center text-base font-semibold text-brand-950 focus-visible:border-brand-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 aria-[invalid=true]:border-error sm:size-11" data-otp-digit>
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

                                <div id="termsAcceptBox" class="flex cursor-pointer items-start gap-2.5 rounded-card border border-border bg-surface-muted px-3 py-2.5">
                                    <input id="terms_accepted" name="terms_accepted" type="checkbox" value="1" aria-describedby="terms-help terms-error" class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-border accent-brand-primary focus-visible:outline-2 focus-visible:outline-focus-ring">
                                    <div class="min-w-0 text-xs leading-5 text-text-secondary">
                                        <label for="terms_accepted" class="cursor-pointer">I have read and accept the</label>
                                        <button type="button" id="openTerms" class="ml-0.5 font-semibold text-brand-primary underline underline-offset-2 hover:text-brand-800">Terms and Conditions</button>
                                        <p id="terms-help" class="mt-1 text-xs text-text-muted">Click the checkbox to open and review the terms.</p>
                                        <p id="terms-error" class="mt-1 hidden text-xs font-medium text-error" role="alert">Please read and accept the terms to continue.</p>
                                    </div>
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

            <p class="mt-4 text-center text-[11px] text-text-muted sm:mt-5">© {{ date('Y') }} YuktiOne</p>
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
        <div id="termsContent" tabindex="0" class="max-h-[60dvh] overflow-y-auto px-4 py-4 text-sm leading-6 text-text-secondary sm:px-6">
            @include('frontend.partials.terms_agreement')
        </div>
        <div class="flex flex-col gap-2 border-t border-divider px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p id="termsScrollStatus" class="text-xs text-text-muted" aria-live="polite">Scroll to the end of the terms to enable acceptance.</p>
            <div class="flex justify-end gap-2">
                <button type="button" data-close-terms class="inline-flex min-h-9 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm font-semibold text-text-primary hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-focus-ring">Close</button>
                <button type="button" id="agreeTerms" disabled class="inline-flex min-h-9 items-center justify-center rounded-control border border-brand-primary bg-brand-primary px-3 text-sm font-semibold text-white hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-50">I Agree</button>
            </div>
        </div>
    </dialog>

    <x-yuktione.feedback />
    @vite([
        'resources/js/yuktione-feedback.js',
        'resources/js/customer-registration.js',
    ])
    <script>
        (() => {
            const typeStep = document.getElementById('accountTypeStep');
            const customerStep = document.getElementById('customerRegisterStep');
            const continueBtn = document.getElementById('accountTypeContinue');
            const backBtn = document.getElementById('backToAccountType');
            const roles = [...document.querySelectorAll('[data-account-type]')];
            const professionalUrl = continueBtn?.dataset.professionalRegisterUrl || @json($professionalRegisterUrl);
            let selected = 'customer';

            const setSelected = (type) => {
                selected = type;
                roles.forEach((role) => {
                    const active = role.dataset.accountType === type;
                    role.classList.toggle('is-selected', active);
                    role.setAttribute('aria-checked', active ? 'true' : 'false');
                });
            };

            roles.forEach((role) => {
                role.addEventListener('click', () => setSelected(role.dataset.accountType));
            });

            continueBtn?.addEventListener('click', () => {
                if (!selected) {
                    return;
                }
                if (selected === 'professional') {
                    window.location.assign(professionalUrl);
                    return;
                }
                if (selected === 'customer' && typeStep && customerStep) {
                    typeStep.hidden = true;
                    customerStep.hidden = false;
                    document.getElementById('first_name')?.focus();
                }
            });

            backBtn?.addEventListener('click', () => {
                if (!typeStep || !customerStep) {
                    return;
                }
                customerStep.hidden = true;
                typeStep.hidden = false;
                setSelected('customer');
            });

            setSelected('customer');
        })();
    </script>
</body>
</html>
