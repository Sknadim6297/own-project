@extends('customer.layout.layout')

@section('title', 'My Profile | Yuktione')

@section('content')
<div class="mx-auto grid max-w-5xl gap-6">
    <x-tazen-v2.panel.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('user.dashboard')],
        ['label' => 'My Profile'],
    ]" />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Account</p>
            <h1 class="mt-1 font-display text-page-title font-semibold text-brand-secondary">My Profile</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary sm:text-base">View and manage your personal information.</p>
        </div>
        @if($profiles->isNotEmpty())
            <a href="{{ route('user.profile.edit', ['profile' => $profiles->first()->id]) }}" class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-control border border-brand-primary bg-brand-primary px-4 text-sm font-semibold text-white transition-colors hover:border-brand-800 hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring sm:self-auto">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.86 4.49 2.65 2.65m-1.48-3.83a1.875 1.875 0 0 1 2.65 2.65L8.25 18.39 4.5 19.5l1.11-3.75L18.03 3.31ZM13.5 6l4.5 4.5" />
                </svg>
                Edit Profile
            </a>
        @endif
    </header>

    @forelse($profiles as $profile)
        @php
            $customerProfile = $profile->customerProfile;
            $displayName = $profile->name ?: ($customerProfile?->name ?: 'Customer');
            $displayEmail = $profile->email ?: ($customerProfile?->email ?: 'Not provided');
            $profileImage = $customerProfile?->profile_image;
            $hasProfileImage = $profileImage && file_exists(public_path($profileImage));
            $gallery = $customerProfile && $customerProfile->gallery
                ? (is_array($customerProfile->gallery) ? $customerProfile->gallery : json_decode($customerProfile->gallery, true))
                : [];
        @endphp

        <section class="overflow-hidden rounded-panel border border-border bg-surface shadow-card" aria-labelledby="profile-overview-heading">
            <div class="h-2 bg-linear-to-r from-brand-primary via-brand-accent to-brand-300"></div>
            <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-7">
                <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-brand-50 bg-brand-100 text-2xl font-semibold text-brand-secondary ring-1 ring-brand-200">
                    @if($hasProfileImage)
                        <img src="{{ asset($profileImage) }}" alt="{{ $displayName }}'s profile photo" class="size-full object-cover">
                    @else
                        <span aria-label="Profile photo unavailable">{{ Str::upper(Str::substr($displayName, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-brand-primary">Customer account</p>
                    <h2 id="profile-overview-heading" class="mt-1 break-words font-display text-xl font-semibold text-brand-secondary sm:text-2xl">{{ $displayName }}</h2>
                    <p class="mt-1 break-all text-sm text-text-secondary">{{ $displayEmail }}</p>
                    <span class="mt-3 inline-flex rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-secondary">Customer</span>
                </div>
            </div>
        </section>

        <section class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6" aria-labelledby="personal-information-heading">
            <div class="mb-5 border-b border-divider pb-4">
                <h2 id="personal-information-heading" class="font-display text-card-title font-semibold text-brand-secondary">Personal Information</h2>
                <p class="mt-1 text-sm text-text-secondary">Your contact information and account details.</p>
            </div>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Full Name</dt>
                    <dd class="mt-1 break-words text-sm font-medium text-text-primary">{{ $displayName ?: 'Not provided' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Email</dt>
                    <dd class="mt-1 break-all text-sm font-medium text-text-primary">{{ $displayEmail }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Phone</dt>
                    <dd class="mt-1 break-words text-sm font-medium text-text-primary">{{ $customerProfile?->phone ?: 'Not provided' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Account Role</dt>
                    <dd class="mt-1 text-sm font-medium text-text-primary">Customer</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6" aria-labelledby="address-information-heading">
            <div class="mb-5 border-b border-divider pb-4">
                <h2 id="address-information-heading" class="font-display text-card-title font-semibold text-brand-secondary">Address &amp; Additional Details</h2>
                <p class="mt-1 text-sm text-text-secondary">Your saved address and any additional notes.</p>
            </div>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                <div class="min-w-0 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Address</dt>
                    <dd class="mt-1 break-words text-sm leading-6 text-text-primary">{{ $customerProfile?->address ?: 'Not provided' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">City</dt>
                    <dd class="mt-1 break-words text-sm font-medium text-text-primary">{{ $customerProfile?->city ?: 'Not provided' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">State</dt>
                    <dd class="mt-1 break-words text-sm font-medium text-text-primary">{{ $customerProfile?->state ?: 'Not provided' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Zip Code</dt>
                    <dd class="mt-1 break-words text-sm font-medium text-text-primary">{{ $customerProfile?->zip_code ?: 'Not provided' }}</dd>
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-text-muted">Notes</dt>
                    <dd class="mt-1 break-words whitespace-pre-line text-sm leading-6 text-text-primary">{{ $customerProfile?->notes ?: 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        @if(is_array($gallery) && count($gallery))
            @php
                $availableGallery = collect($gallery)->filter(fn ($image) => is_string($image) && file_exists(public_path($image)));
            @endphp
            @if($availableGallery->isNotEmpty())
                <section class="rounded-panel border border-border bg-surface p-5 shadow-card sm:p-6" aria-labelledby="profile-gallery-heading">
                    <div class="mb-5">
                        <h2 id="profile-gallery-heading" class="font-display text-card-title font-semibold text-brand-secondary">Profile Gallery</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($availableGallery as $image)
                            <img src="{{ asset($image) }}" alt="Profile gallery photo" loading="lazy" class="aspect-square w-full rounded-card border border-border object-cover">
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    @empty
        <x-tazen-v2.panel.empty-state
            icon="user"
            title="Profile details unavailable"
            description="We could not find profile details for this account."
        />
    @endforelse
</div>
@endsection
