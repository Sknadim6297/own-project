<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#36877F">
    <title>Application Under Review | YuktiOne</title>
    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite(['resources/css/tazen.css', 'resources/css/yuktione-feedback.css', 'resources/css/auth-theme.css'])
</head>
<body class="yuktione-auth-page flex min-h-screen items-center justify-center bg-background px-3 py-5 font-sans text-text-primary sm:px-5">
    <main class="w-full max-w-[430px]">
        @php
            $applicationStatus = $professional->status ?? 'pending';
            $statusIsPending = $applicationStatus === 'pending';
            $statusIsAccepted = $applicationStatus === 'accepted';
            $statusIsRejected = $applicationStatus === 'rejected';
        @endphp
        <section class="rounded-panel border border-divider bg-white p-5 text-center shadow-card sm:p-7" aria-labelledby="application-status-title">
            <a href="{{ route('home') }}" class="mb-5 inline-flex items-center justify-center" aria-label="Yuktione home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" class="h-9 w-auto max-w-36 object-contain">
            </a>

            @if($statusIsPending)
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-warning-surface text-warning" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="inline-flex rounded-full bg-warning-surface px-3 py-1 text-[11px] font-semibold text-warning">Application under review</span>
                <h1 id="application-status-title" class="mt-3 text-xl font-semibold leading-tight text-brand-950 sm:text-2xl">Application Under Review</h1>
                <p class="mt-2 text-[13px] leading-5 text-text-secondary">
                    @if(filled($professional->name ?? null))
                        Hi {{ $professional->name }}, your professional profile is currently being reviewed by the Yuktione team.
                    @else
                        Your professional profile is currently being reviewed by the Yuktione team.
                    @endif
                </p>
                <div class="mt-4 rounded-control border border-divider bg-surface-muted px-3.5 py-3 text-left">
                    <p class="text-xs font-semibold text-brand-950">What happens next?</p>
                    <p class="mt-1 text-xs leading-5 text-text-muted">We’ll update your application status once the review is complete.</p>
                </div>
                <a href="{{ route('professional.logout') }}" class="mt-5 inline-flex min-h-10 w-full items-center justify-center rounded-control border border-border bg-white px-4 py-2 text-sm font-medium text-text-secondary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Sign out
                </a>
            @elseif($statusIsAccepted)
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success-surface text-success" aria-hidden="true">
                    <span class="text-2xl font-semibold">✓</span>
                </div>
                <span class="inline-flex rounded-full bg-success-surface px-3 py-1 text-[11px] font-semibold text-success">Approved</span>
                <h1 id="application-status-title" class="mt-3 text-xl font-semibold leading-tight text-brand-950 sm:text-2xl">Application Approved</h1>
                <p class="mt-2 text-[13px] leading-5 text-text-secondary">Your professional application has been approved. You can continue to your workspace.</p>
                <a href="{{ route('professional.dashboard') }}" class="mt-5 inline-flex min-h-10 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Go to Dashboard
                </a>
            @elseif($statusIsRejected)
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-error-surface text-error" aria-hidden="true">
                    <span class="text-2xl font-semibold">!</span>
                </div>
                <span class="inline-flex rounded-full bg-error-surface px-3 py-1 text-[11px] font-semibold text-error">Updates required</span>
                <h1 id="application-status-title" class="mt-3 text-xl font-semibold leading-tight text-brand-950 sm:text-2xl">Application Needs Updates</h1>
                <p class="mt-2 text-[13px] leading-5 text-text-secondary">Please review the feedback on your application and update the requested details.</p>
                <a href="{{ route('professional.rejected.view') }}" class="mt-5 inline-flex min-h-10 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Review and Update
                </a>
            @else
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-surface-muted text-text-secondary" aria-hidden="true">?</div>
                <span class="inline-flex rounded-full bg-surface-muted px-3 py-1 text-[11px] font-semibold text-text-secondary">Status unavailable</span>
                <h1 id="application-status-title" class="mt-3 text-xl font-semibold leading-tight text-brand-950 sm:text-2xl">We Can’t Display Your Status</h1>
                <p class="mt-2 text-[13px] leading-5 text-text-secondary">Please contact Yuktione support for help with your professional account.</p>
            @endif
        </section>
    </main>

    <x-yuktione.feedback />
    @vite('resources/js/yuktione-feedback.js')
</body>
</html>
