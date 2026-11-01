@if (session('recovery_unregistered'))
    <div class="mb-5 rounded-card border border-[#f0d3cc] bg-[#fbf6f3] px-4 py-4" role="alert">
        <p class="text-sm font-semibold text-brand-secondary">{{ \App\Services\AccountRecovery::UNREGISTERED_TITLE }}</p>
        <p class="mt-1 text-sm leading-6 text-text-secondary">{{ \App\Services\AccountRecovery::UNREGISTERED_MESSAGE }}</p>
        <a href="{{ $registerUrl }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200">
            Register Now
        </a>
    </div>
@endif
