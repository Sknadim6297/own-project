@extends('professional.layout.layout')

@section('title', 'Profile Overview | YuktiOne')

@section('content')
<div class="profile-view-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="profile-shell mx-auto w-full space-y-5">
        <section class="profile-hero flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="profile-hero__meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li aria-current="page" class="text-text-secondary">Profile</li>
                    </ol>
                </nav>
                <span class="profile-hero__eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="ri-user-3-line" aria-hidden="true"></i>Profile Overview</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Your Professional Identity</h1>
                <p class="mt-1 text-sm text-text-secondary">Review your professional information, ensure key details are complete, and keep your portfolio updated.</p>
            </div>
            <div class="profile-hero__actions flex flex-wrap gap-2">
                <a href="{{ route('professional.dashboard') }}" class="btn-pill btn-pill--outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    Dashboard
                </a>
                @if($profiles->count())
                    <a href="{{ route('professional.profile.edit', ['profile' => $profiles->first()->id]) }}" class="btn-pill btn-pill--primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <i class="ri-edit-line" aria-hidden="true"></i>
                        Edit Profile
                    </a>
                @endif
            </div>
        </section>

        @if($profiles->count())
            <div class="profile-list">
                @foreach($profiles as $profile)
                    <article class="profile-card overflow-hidden rounded-panel border border-border bg-white shadow-card lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
                        <aside class="profile-card__sidebar flex flex-col items-center gap-5 bg-surface-muted p-6 text-center">
                            <div class="profile-card__photo h-32 w-32 overflow-hidden rounded-full border-4 border-white bg-brand-50 shadow-card">
                                @php
                                    $photoPath = $profile->photo ? asset('storage/'.$profile->photo) : asset('default.jpg');
                                    if ($profile->photo && !file_exists(public_path('storage/'.$profile->photo))) {
                                        $photoPath = asset('img/default-avatar.jpg');
                                    }
                                @endphp
                                <img src="{{ $photoPath }}" alt="{{ $profile->professional?->name ?? $profile->name ?? 'Profile photo' }}" class="h-full w-full object-cover">
                            </div>
                            <div class="profile-card__sidebar-block space-y-1.5">
                                <div class="profile-card__status inline-flex items-center gap-2 rounded-full bg-success-surface px-2.5 py-1 text-xs font-semibold text-success">
                                    <span class="profile-card__status-dot size-1.5 rounded-full bg-success" aria-hidden="true"></span>
                                    Verified Professional
                                </div>
                                <div class="profile-card__updated text-xs text-text-muted">
                                    Last updated {{ $profile->updated_at->diffForHumans() }}
                                </div>
                                @if($profile->specialization)
                                    <p class="text-sm font-medium text-brand-950">{{ $profile->specialization }}</p>
                                @endif
                            </div>
                            <div class="profile-card__actions flex w-full flex-col gap-2">
                                <a href="{{ route('professional.profile.edit', ['profile' => $profile->id]) }}" class="btn-pill btn-pill--primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                                    <i class="ri-edit-line" aria-hidden="true"></i>
                                    Edit Profile
                                </a>
                                <a href="mailto:{{ $profile->email }}" class="btn-pill btn-pill--ghost inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border px-4 py-2 text-sm font-semibold text-text-secondary transition hover:bg-brand-50 hover:text-brand-800">
                                    <i class="ri-mail-send-line" aria-hidden="true"></i>
                                    Contact
                                </a>
                            </div>
                        </aside>

                        <div class="profile-card__content space-y-5 p-5 sm:p-7">
                            <section class="profile-section rounded-panel border border-border bg-white p-5 shadow-sm">
                                <div class="profile-section__header mb-4 border-b border-divider pb-3">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="ri-information-line text-brand-primary" aria-hidden="true"></i>Personal Overview</h3>
                                </div>
                                <div class="detail-grid grid gap-3 sm:grid-cols-2">
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Full Name</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->name ?? ($profile->professional?->name ?? 'Not provided') }}</span>
                                        @if(!$profile->name && !$profile->professional?->name)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Required</span>
                                        @endif
                                    </div>
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Email</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->email ?? 'Not provided' }}</span>
                                        @if(!$profile->email)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Required</span>
                                        @endif
                                    </div>
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Phone</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->phone ?? 'Not provided' }}</span>
                                        @if(!$profile->phone)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add phone number</span>
                                        @endif
                                    </div>
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Address</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->address ?? 'Not provided' }}</span>
                                        @if(!$profile->address)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add address</span>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section rounded-panel border border-border bg-white p-5 shadow-sm">
                                <div class="profile-section__header mb-4 border-b border-divider pb-3">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="ri-briefcase-4-line text-brand-primary" aria-hidden="true"></i>Professional Details</h3>
                                </div>
                                <div class="detail-grid grid gap-3 sm:grid-cols-2">
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Specialization</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->specialization ?? 'Not provided' }}</span>
                                        @if(!$profile->specialization)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add specialization</span>
                                        @endif
                                    </div>
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Experience (Years)</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->experience ?? 'Not provided' }}</span>
                                        @if(!$profile->experience)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add experience</span>
                                        @endif
                                    </div>
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Starting Price</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">
                                            @if($profile->starting_price)
                                                ₹{{ $profile->starting_price }} per session
                                            @else
                                                Not provided
                                            @endif
                                        </span>
                                        @if(!$profile->starting_price)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add pricing</span>
                                        @endif
                                    </div>
                                    <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Education</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->education ?? 'Not provided' }}</span>
                                        @if(!$profile->education)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add education</span>
                                        @endif
                                    </div>
                                </div>
                                @php
                                    $serviceRequestMargin = $profile->professional?->service_request_margin ?? 10.00;
                                    $negotiationOffset = $profile->professional?->service_request_offset ?? 20.00;
                                    $mainMargin = $profile->professional?->margin ?? 20.00;
                                @endphp
                                <div class="commission-grid mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="commission-pill flex flex-col gap-1 rounded-control border border-brand-100 bg-brand-50 p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Service Request Margin</span>
                                        <span class="detail-tile__value break-words text-lg font-semibold text-brand-950">{{ number_format($serviceRequestMargin, 2) }}%</span>
                                        <span class="detail-tile__note text-[11px] text-text-muted">Commission for additional service requests</span>
                                    </div>
                                    <div class="commission-pill is-warning flex flex-col gap-1 rounded-control border border-warning/20 bg-warning-surface p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Negotiation Offset</span>
                                        <span class="detail-tile__value break-words text-lg font-semibold text-brand-950">{{ number_format($negotiationOffset, 2) }}%</span>
                                        <span class="detail-tile__note text-[11px] text-text-muted">Maximum negotiation allowance</span>
                                    </div>
                                    <div class="commission-pill is-success flex flex-col gap-1 rounded-control border border-success/20 bg-success-surface p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Platform Margin</span>
                                        <span class="detail-tile__value break-words text-lg font-semibold text-brand-950">{{ number_format($mainMargin, 2) }}%</span>
                                        <span class="detail-tile__note text-[11px] text-text-muted">Platform commission on confirmed bookings</span>
                                    </div>
                                </div>
                                <div class="detail-grid grid gap-3 sm:grid-cols-2">
                                    @if($profile->gst_number)
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">GST Number</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->gst_number }}</span>
                                        </div>
                                    @endif
                                    @if($profile->state_code)
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">State Code</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->state_code }} {{ $profile->state_name ? '- '.$profile->state_name : '' }}</span>
                                        </div>
                                    @endif
                                    @if($profile->gst_address)
                                        <div class="detail-tile detail-tile--full flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">GST Address</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->gst_address }}</span>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="profile-section rounded-panel border border-border bg-white p-5 shadow-sm">
                                <div class="profile-section__header mb-4 border-b border-divider pb-3">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="ri-user-voice-line text-brand-primary" aria-hidden="true"></i>About</h3>
                                </div>
                                <div class="detail-grid grid gap-3 sm:grid-cols-2">
                                    <div class="detail-tile detail-tile--full flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                        <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Biography</span>
                                        <span class="detail-tile__value break-words font-medium text-text-primary">
                                            {{ $profile->bio ?? 'No bio provided yet.' }}
                                        </span>
                                        @if(!$profile->bio)
                                            <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Add a short bio</span>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section class="profile-section rounded-panel border border-border bg-white p-5 shadow-sm">
                                <div class="profile-section__header mb-4 border-b border-divider pb-3">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="ri-shield-check-line text-brand-primary" aria-hidden="true"></i>Verification Documents</h3>
                                </div>
                                <div class="documents-list space-y-2">
                                    <div class="document-row flex items-start gap-3 rounded-control border border-divider bg-surface-muted p-3">
                                        <span class="document-row__icon flex size-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-primary"><i class="ri-contacts-book-line" aria-hidden="true"></i></span>
                                        <div class="document-row__content min-w-0 space-y-1">
                                            <span class="document-row__title block text-sm font-medium text-text-primary">Qualification Document</span>
                                            @if($profile->qualification_document)
                                                <a href="{{ asset('storage/'.$profile->qualification_document) }}" target="_blank" rel="noopener noreferrer" class="document-link inline-flex items-center gap-1 text-sm font-semibold text-brand-primary hover:text-brand-800">
                                                    <i class="ri-external-link-line" aria-hidden="true"></i>View document
                                                </a>
                                            @else
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line" aria-hidden="true"></i>Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="document-row flex items-start gap-3 rounded-control border border-divider bg-surface-muted p-3">
                                        <span class="document-row__icon flex size-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-primary"><i class="ri-id-card-line" aria-hidden="true"></i></span>
                                        <div class="document-row__content min-w-0 space-y-1">
                                            <span class="document-row__title block text-sm font-medium text-text-primary">ID Proof (Aadhaar / PAN)</span>
                                            @if($profile->id_proof_document)
                                                <a href="{{ asset('storage/'.$profile->id_proof_document) }}" target="_blank" rel="noopener noreferrer" class="document-link inline-flex items-center gap-1 text-sm font-semibold text-brand-primary hover:text-brand-800">
                                                    <i class="ri-external-link-line" aria-hidden="true"></i>View document
                                                </a>
                                            @else
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line" aria-hidden="true"></i>Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($profile->gst_number)
                                        <div class="document-row flex items-start gap-3 rounded-control border border-divider bg-surface-muted p-3">
                                            <span class="document-row__icon flex size-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-primary"><i class="ri-file-paper-line" aria-hidden="true"></i></span>
                                            <div class="document-row__content min-w-0 space-y-1">
                                                <span class="document-row__title block text-sm font-medium text-text-primary">GST Certificate</span>
                                                @if($profile->gst_certificate)
                                                    <a href="{{ asset('storage/'.$profile->gst_certificate) }}" target="_blank" rel="noopener noreferrer" class="document-link inline-flex items-center gap-1 text-sm font-semibold text-brand-primary hover:text-brand-800">
                                                        <i class="ri-external-link-line" aria-hidden="true"></i>View document
                                                    </a>
                                                @else
                                                    <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line" aria-hidden="true"></i>Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="profile-section rounded-panel border border-border bg-white p-5 shadow-sm">
                                <div class="profile-section__header mb-4 border-b border-divider pb-3">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="ri-bank-card-line text-brand-primary" aria-hidden="true"></i>Bank Details</h3>
                                </div>
                                <div class="bank-list">
                                    <div class="detail-grid grid gap-3 sm:grid-cols-2">
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Account Holder</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->account_holder_name ?? 'Not provided' }}</span>
                                            @if(!$profile->account_holder_name)
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Required</span>
                                            @endif
                                        </div>
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Bank Name</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->bank_name ?? 'Not provided' }}</span>
                                            @if(!$profile->bank_name)
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Required</span>
                                            @endif
                                        </div>
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Account Number</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">
                                                @if($profile->account_number)
                                                    {{ str_repeat('*', max(strlen($profile->account_number) - 4, 0)) . substr($profile->account_number, -4) }}
                                                @else
                                                    Not provided
                                                @endif
                                            </span>
                                            @if(!$profile->account_number)
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Required</span>
                                            @endif
                                        </div>
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">IFSC Code</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->ifsc_code ?? 'Not provided' }}</span>
                                            @if(!$profile->ifsc_code)
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line"></i>Required</span>
                                            @endif
                                        </div>
                                        <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                            <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Account Type</span>
                                            <span class="detail-tile__value break-words font-medium text-text-primary">{{ ucfirst($profile->account_type ?? 'Not specified') }}</span>
                                        </div>
                                        @if($profile->bank_branch)
                                            <div class="detail-tile flex flex-col gap-1 rounded-control border border-divider bg-surface-muted p-4">
                                                <span class="detail-tile__label text-xs font-semibold uppercase tracking-wide text-text-muted">Branch</span>
                                                <span class="detail-tile__value break-words font-medium text-text-primary">{{ $profile->bank_branch }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="document-row mt-3 flex items-start gap-3 rounded-control border border-divider bg-surface-muted p-3">
                                        <span class="document-row__icon flex size-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-primary"><i class="ri-file-list-3-line" aria-hidden="true"></i></span>
                                        <div class="document-row__content min-w-0 space-y-1">
                                            <span class="document-row__title block text-sm font-medium text-text-primary">Bank Account Proof</span>
                                            @if($profile->bank_document)
                                                <a href="{{ asset('storage/'.$profile->bank_document) }}" target="_blank" rel="noopener noreferrer" class="document-link inline-flex items-center gap-1 text-sm font-semibold text-brand-primary hover:text-brand-800">
                                                    <i class="ri-external-link-line" aria-hidden="true"></i>View document
                                                </a>
                                            @else
                                                <span class="badge-warning inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium text-warning"><i class="ri-alert-line" aria-hidden="true"></i>Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="profile-gallery mt-6 rounded-panel border border-border bg-white p-5 shadow-sm">
                                <div class="profile-gallery__header mb-4 border-b border-divider pb-3">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-brand-950"><i class="ri-gallery-line text-brand-primary" aria-hidden="true"></i>Portfolio Gallery</h3>
                                </div>
                                @php
                                    $gallery = is_array($profile->gallery) ? $profile->gallery : json_decode($profile->gallery, true);
                                    $gallery = is_array($gallery) ? $gallery : [];
                                @endphp
                                @if(!empty($gallery))
                                    <div class="profile-gallery__grid grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                        @foreach($gallery as $img)
                                            @php
                                                if (str_starts_with($img, 'gallery/')) {
                                                    $imagePath = asset('storage/'.$img);
                                                } elseif (str_starts_with($img, 'uploads/')) {
                                                    $imagePath = file_exists(storage_path('app/public/'.$img))
                                                        ? asset('storage/'.$img)
                                                        : asset($img);
                                                } else {
                                                    $imagePath = asset('uploads/professionals/gallery/'.$img);
                                                }
                                            @endphp
                                            <div class="profile-gallery__item relative aspect-square overflow-hidden rounded-control border border-border bg-surface-muted">
                                                <img src="{{ $imagePath }}" alt="Gallery image" class="h-full w-full object-cover">
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="gallery-empty rounded-control border border-dashed border-border bg-surface-muted px-4 py-8 text-center">
                                        <i class="ri-image-line text-2xl text-text-muted" aria-hidden="true"></i>
                                        <p class="mt-2 text-sm text-text-muted">No gallery images available.</p>
                                    </div>
                                @endif
                            </section>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="profile-empty-state rounded-panel border border-border bg-white px-5 py-12 text-center shadow-card sm:px-8">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-primary" aria-hidden="true">
                    <i class="ri-user-smile-line text-2xl"></i>
                </span>
                <h3 class="mt-4 text-lg font-semibold text-brand-950">Profile information is unavailable</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-text-muted">Your professional profile has not been set up yet. Please contact support so it can be linked to your account.</p>
            </div>
        @endif
    </div>
</div>
@endsection
