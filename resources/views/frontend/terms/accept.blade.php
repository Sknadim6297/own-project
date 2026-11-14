@extends('layouts.layout')

@section('title', 'Terms & Conditions | YuktiOne')
@section('meta_description', 'Please review and accept the YuktiOne Terms & Conditions to continue.')
@section('body_class', 'yuktione-auth-page')

@push('head')
    <style>
        .yuktione-terms-accept #agreeContinue:disabled {
            border-color: #BBDBD4;
            background-color: #BBDBD4;
            color: #ffffff;
            opacity: 1;
            cursor: not-allowed;
        }

        .yuktione-terms-accept #agreeContinue.is-loading {
            opacity: 0.72;
            cursor: wait;
        }

        .yuktione-terms-accept #termsScrollRegion:focus-visible {
            outline: 2px solid #316D61;
            outline-offset: 2px;
        }
    </style>
@endpush

@section('content')
<main class="yuktione-auth-page yuktione-terms-accept flex min-h-dvh w-full items-center justify-center bg-background px-4 py-8 sm:px-6" data-yuktione-panel="customer">
    <section
        class="w-full max-w-3xl overflow-hidden rounded-panel border border-border bg-surface shadow-popover"
        aria-labelledby="terms-heading"
        aria-describedby="terms-lead"
    >
        <header class="border-b border-divider px-5 py-5 sm:px-8 sm:py-6">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus-ring" aria-label="YuktiOne home">
                <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="YuktiOne" width="155" height="50" class="h-10 w-auto object-contain object-left">
            </a>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">Legal agreement</p>
            <h1 id="terms-heading" class="mt-2 font-display text-2xl font-semibold text-brand-secondary sm:text-3xl">
                Terms &amp; Conditions
            </h1>
            <p id="terms-lead" class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary">
                Before you continue to your account, please review and accept YuktiOne’s Terms &amp; Conditions.
                This is required once for your account and applies across all devices and browsers.
            </p>
            <p class="mt-2 text-xs text-text-muted">Version {{ $termsVersion }}</p>
        </header>

        <div
            id="termsScrollRegion"
            tabindex="0"
            role="region"
            aria-label="Terms and Conditions content"
            class="max-h-[min(52dvh,28rem)] overflow-y-auto px-5 py-5 text-sm leading-6 text-text-secondary sm:px-8"
        >
            @include('frontend.partials.terms_agreement')
        </div>

        <footer class="border-t border-divider bg-surface-muted/60 px-5 py-5 sm:px-8">
            <div
                id="termsFeedback"
                class="mb-4 hidden rounded-card border px-3 py-2.5 text-sm"
                role="alert"
                aria-live="polite"
            ></div>

            <form id="termsAcceptForm" method="POST" action="{{ route('terms.accept') }}" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <label class="flex cursor-pointer items-start gap-3 rounded-card border border-border bg-surface px-3 py-3">
                    <input
                        type="checkbox"
                        name="accepted"
                        id="termsAccepted"
                        value="1"
                        required
                        aria-describedby="terms-checkbox-help"
                        class="mt-0.5 size-4 shrink-0 rounded border-border accent-brand-primary focus-visible:outline-focus-ring"
                    >
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-text-primary">
                            I have read and agree to the Terms &amp; Conditions
                        </span>
                        <span id="terms-checkbox-help" class="mt-1 block text-xs leading-5 text-text-muted">
                            Enable this checkbox to continue. Acceptance is saved to your account.
                        </span>
                    </span>
                </label>

                @error('accepted')
                    <p class="text-sm font-medium text-error" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="submit"
                        form="termsDeclineForm"
                        id="declineButton"
                        class="inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-surface px-5 py-2.5 text-sm font-semibold text-text-primary transition hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                    >
                        Decline &amp; Sign Out
                    </button>

                    <button
                        type="submit"
                        id="agreeContinue"
                        disabled
                        aria-disabled="true"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-brand-primary bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white shadow-card transition hover:border-brand-800 hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                    >
                        <span id="agreeContinueLabel">I Agree &amp; Continue</span>
                        <svg id="agreeContinueSpinner" class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <form id="termsDeclineForm" method="POST" action="{{ route('terms.decline') }}" class="hidden">
                @csrf
            </form>
        </footer>
    </section>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('termsAcceptForm');
    const declineForm = document.getElementById('termsDeclineForm');
    const checkbox = document.getElementById('termsAccepted');
    const agreeButton = document.getElementById('agreeContinue');
    const agreeLabel = document.getElementById('agreeContinueLabel');
    const agreeSpinner = document.getElementById('agreeContinueSpinner');
    const declineButton = document.getElementById('declineButton');
    const feedback = document.getElementById('termsFeedback');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!form || !checkbox || !agreeButton) {
        return;
    }

    const setFeedback = (message, type = 'error') => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.remove('hidden', 'border-error/30', 'bg-error/10', 'text-error', 'border-brand-200', 'bg-info-surface', 'text-brand-primary');

        if (type === 'success') {
            feedback.classList.add('border-brand-200', 'bg-info-surface', 'text-brand-primary');
        } else {
            feedback.classList.add('border-error/30', 'bg-error/10', 'text-error');
        }
    };

    const clearFeedback = () => {
        if (!feedback) {
            return;
        }

        feedback.textContent = '';
        feedback.classList.add('hidden');
    };

    const syncAgreeState = () => {
        const enabled = checkbox.checked;
        agreeButton.disabled = !enabled;
        agreeButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        if (enabled) {
            clearFeedback();
        }
    };

    const setLoading = (isLoading) => {
        agreeButton.classList.toggle('is-loading', isLoading);
        agreeButton.disabled = isLoading || !checkbox.checked;
        declineButton.disabled = isLoading;
        checkbox.disabled = isLoading;
        agreeSpinner?.classList.toggle('hidden', !isLoading);
        if (agreeLabel) {
            agreeLabel.textContent = isLoading ? 'Saving…' : 'I Agree & Continue';
        }
    };

    checkbox.addEventListener('change', syncAgreeState);
    syncAgreeState();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFeedback();

        if (!checkbox.checked) {
            setFeedback('Please confirm that you have read and agree to the Terms & Conditions.');
            checkbox.focus();
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: new FormData(form),
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message
                    || payload.errors?.accepted?.[0]
                    || 'Unable to save your acceptance. Please try again.';
                setFeedback(message);
                setLoading(false);
                return;
            }

            setFeedback(payload.message || 'Terms accepted. Redirecting…', 'success');
            window.location.assign(payload.redirect_url || @json(route('home')));
        } catch (error) {
            setFeedback('Something went wrong. Please try again.');
            setLoading(false);
        }
    });

    declineForm?.addEventListener('submit', () => {
        declineButton.disabled = true;
        agreeButton.disabled = true;
    });
})();
</script>
@endpush
