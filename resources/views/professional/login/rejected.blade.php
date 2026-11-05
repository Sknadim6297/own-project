<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#36877F">
    <meta name="description" content="Update your Yuktione professional application">
    <title>Update Your Application | YuktiOne</title>
    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite(['resources/css/tazen.css', 'resources/css/yuktione-feedback.css', 'resources/css/auth-theme.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .form-step { display: none; }
        .form-step.active { display: block; }
        .invalid-feedback { display: none; color: #C85B65; font-size: .75rem; margin-top: .35rem; }
        .invalid-feedback.show { display: block; }
        .is-invalid { border-color: #C85B65 !important; }
        #rejectionModal { display: none; }
        #rejectionModal.is-open { display: flex; }
    </style>
</head>
<body class="yuktione-auth-page professional-rejected-page min-h-screen bg-background font-sans text-text-primary antialiased">
@php
    $professional = $RejectedUser->professional ?? Auth::guard('professional')->user();
    $profile = $RejectedUser->profile ?? null;
    $doc = $profile;
    $existingGstNumber = old('gst_number', $profile->gst_number ?? $professional->gst_number ?? '');
    $existingGstCertificate = $profile->gst_certificate ?? $professional->gst_certificate ?? '';
    $hasGst = !empty($existingGstNumber) || !empty($existingGstCertificate);
    $selectedStateCode = old('state_code', $profile->state_code ?? $professional->state_code ?? '');
    $selectedSpecialization = old('specialization', $profile->specialization ?? '');
    $selectedExperience = old('experience', $profile->experience ?? '');
    $selectedAddress = old('address', $profile->address ?? '');
    $rejectionReason = filled($reason ?? null) ? $reason : 'No rejection reason was provided. Please review your details and documents, then resubmit.';
@endphp

<main class="flex min-h-screen items-center justify-center bg-linear-to-br from-brand-50 via-background to-white px-3 py-5 sm:px-5">
    <section class="w-full max-w-xl rounded-panel border border-divider bg-surface p-4 shadow-card sm:p-6 md:p-8" aria-labelledby="rejected-title">
        <header class="mb-5 sm:mb-6">
            <div class="mb-4 flex items-center justify-between gap-3 sm:mb-5">
                <a href="{{ route('home') }}" aria-label="Yuktione home" class="inline-flex items-center">
                    <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" class="h-8 w-auto max-w-36 object-contain sm:h-9">
                </a>
                <span class="rounded-full bg-error-surface px-2.5 py-1 text-[9px] font-semibold tracking-wide text-error sm:px-3 sm:text-[10px]">UPDATES REQUIRED</span>
            </div>

            <div class="mb-1.5 flex items-center justify-between gap-3">
                <h1 id="rejected-title" class="text-xl font-semibold leading-tight tracking-tight text-brand-950 sm:text-2xl">Update your application</h1>
                <span class="shrink-0 text-xs font-medium text-text-muted" data-resubmit-step-label>Step 1 of 3</span>
            </div>
            <p class="mt-1.5 text-sm leading-6 text-text-muted">
                @if(filled($professional->name ?? null))
                    Hi {{ $professional->name }}, update only the details mentioned in the rejection feedback.
                @else
                    Update only the details mentioned in the rejection feedback. Other fields are optional.
                @endif
            </p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-brand-100" role="progressbar" aria-label="Resubmission progress" aria-valuemin="1" aria-valuemax="3" aria-valuenow="1" data-resubmit-progressbar>
                <div class="h-full w-1/3 rounded-full bg-brand-primary transition-[width] duration-300" data-resubmit-progress></div>
            </div>
        </header>

        <aside class="mb-5 rounded-control border border-error/20 bg-error-surface/60 p-3.5 sm:p-4" aria-labelledby="rejection-reason-title">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-white text-error shadow-sm" aria-hidden="true">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="12" cy="16" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p id="rejection-reason-title" class="text-xs font-semibold uppercase tracking-wide text-error">Rejection feedback</p>
                    <p class="mt-1.5 whitespace-pre-wrap break-words text-sm leading-6 text-brand-950">{{ $rejectionReason }}</p>
                    <p class="mt-2 text-[11px] leading-4 text-text-muted">You only need to correct the fields or documents called out above. Everything else can stay as is.</p>
                </div>
            </div>
        </aside>

        <form id="registerForm" enctype="multipart/form-data" novalidate>
            @csrf

            {{-- Step 1 - Basic Info --}}
            <div class="form-step step-1 active space-y-3.5 sm:space-y-4">
                <div>
                    <h2 class="text-[17px] font-semibold text-brand-950">Basic information</h2>
                    <p class="mt-1 text-[13px] leading-5 text-text-muted">Update only the fields that need correction.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-medium text-text-primary">Name</label>
                    <input
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        type="text"
                        id="name"
                        name="name"
                        autocomplete="name"
                        placeholder="Enter your name"
                        value="{{ old('name', $professional->name ?? '') }}"
                        aria-describedby="name-error"
                    >
                    <p id="name-error" class="hidden text-xs text-error" data-error-for="name"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-medium text-text-primary">Email</label>
                    <input
                        class="form-control w-full rounded-control border border-border bg-surface-muted px-3 py-2.5 text-sm text-text-secondary"
                        type="email"
                        id="email"
                        name="email"
                        value="{{ $professional->email ?? '' }}"
                        readonly
                        aria-readonly="true"
                    >
                    <p class="text-[11px] text-text-muted">Email address cannot be changed.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="phone" class="block text-xs font-medium text-text-primary">Phone number</label>
                    <input
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        type="text"
                        id="phone"
                        name="phone"
                        inputmode="numeric"
                        autocomplete="tel"
                        placeholder="Enter your phone number"
                        value="{{ old('phone', $professional->phone ?? '') }}"
                        aria-describedby="phone-error"
                    >
                    <p id="phone-error" class="hidden text-xs text-error" data-error-for="phone"></p>
                </div>

                <button type="button" class="next-btn inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Continue
                </button>
            </div>

            {{-- Step 2 - Professional Info --}}
            <div class="form-step step-2 space-y-3.5 sm:space-y-4">
                <div>
                    <h2 class="text-[17px] font-semibold text-brand-950">Professional details</h2>
                    <p class="mt-1 text-[13px] leading-5 text-text-muted">Update only the fields that need correction.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="specialization" class="block text-xs font-medium text-text-primary">Service you offer</label>
                    <select
                        id="specialization"
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        name="specialization"
                        aria-describedby="specialization-error"
                    >
                        <option value="">Select service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->name }}" {{ $selectedSpecialization == $service->name ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <p id="specialization-error" class="hidden text-xs text-error" data-error-for="specialization"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="experience" class="block text-xs font-medium text-text-primary">Professional experience</label>
                    <select
                        id="experience"
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        name="experience"
                        aria-describedby="experience-error"
                    >
                        <option value="">Select experience</option>
                        @foreach(($experiences ?? ['0-2', '2-4', '4-6', '6-8', '8-10', '10+']) as $exp)
                            <option value="{{ $exp }}" {{ $selectedExperience == $exp ? 'selected' : '' }}>{{ $exp }} years</option>
                        @endforeach
                    </select>
                    <p id="experience-error" class="hidden text-xs text-error" data-error-for="experience"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="starting_price" class="block text-xs font-medium text-text-primary">Starting price</label>
                    <input
                        id="starting_price"
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        type="text"
                        name="starting_price"
                        placeholder="e.g. 1000 or 1000-2000"
                        value="{{ old('starting_price', $profile->starting_price ?? '') }}"
                        aria-describedby="starting_price-hint starting_price-error"
                    >
                    <p id="starting_price-hint" class="text-[11px] text-text-muted">Enter a fixed price or a price range per session.</p>
                    <p id="starting_price-error" class="hidden text-xs text-error" data-error-for="starting_price"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="address" class="block text-xs font-medium text-text-primary">Location</label>
                    <select
                        id="address"
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        name="address"
                        aria-describedby="address-error"
                    >
                        <option value="">Select location</option>
                        @foreach(($cities ?? ['Mumbai', 'Kolkata', 'Delhi', 'Bangalore', 'Chennai', 'Hyderabad', 'Pune', 'Ahmedabad', 'Surat', 'Jaipur']) as $city)
                            <option value="{{ $city }}" {{ $selectedAddress == $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                    <p id="address-error" class="hidden text-xs text-error" data-error-for="address"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="education" class="block text-xs font-medium text-text-primary">Education or qualification</label>
                    <input
                        id="education"
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        type="text"
                        name="education"
                        placeholder="Education"
                        value="{{ old('education', $profile->education ?? '') }}"
                        aria-describedby="education-error"
                    >
                    <p id="education-error" class="hidden text-xs text-error" data-error-for="education"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="education2" class="block text-xs font-medium text-text-primary">Additional education <span class="font-normal text-text-muted">(optional)</span></label>
                    <input
                        id="education2"
                        class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                        type="text"
                        name="education2"
                        placeholder="Additional education"
                        value="{{ old('education2', $profile->education2 ?? '') }}"
                        aria-describedby="education2-error"
                    >
                    <p id="education2-error" class="hidden text-xs text-error" data-error-for="education2"></p>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button type="button" class="prev-btn inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-white px-3 text-sm font-medium text-text-primary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Back</button>
                    <button type="button" class="next-btn inline-flex min-h-11 items-center justify-center rounded-control bg-brand-primary px-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Continue</button>
                </div>
            </div>

            {{-- Step 3 - Document Uploads --}}
            <div class="form-step step-3 space-y-3.5 sm:space-y-4">
                <div>
                    <h2 class="text-[17px] font-semibold text-brand-950">Documents</h2>
                    <p class="mt-1 text-[13px] leading-5 text-text-muted">Upload new documents only if the existing ones were rejected.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="profile_photo" class="block text-xs font-medium text-text-primary">Profile photo <span class="font-normal text-text-muted">(optional)</span></label>
                    @if($doc && !empty($doc->photo))
                        <div class="mb-2 flex items-center gap-3 rounded-control border border-divider bg-surface-muted px-3 py-2.5">
                            <img src="{{ asset('storage/'.$doc->photo) }}" alt="Current profile photo" class="size-12 rounded-full object-cover">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-brand-950">Current photo on file</p>
                                <a href="{{ asset('storage/'.$doc->photo) }}" target="_blank" rel="noopener noreferrer" class="text-[11px] font-medium text-brand-primary hover:underline">View current photo</a>
                            </div>
                        </div>
                    @else
                        <p class="mb-2 text-[11px] text-text-muted">No photo uploaded yet.</p>
                    @endif
                    <input class="peer sr-only file-upload-input form-control" type="file" name="profile_photo" id="profile_photo" accept=".jpg,.jpeg,.png,.bmp,.gif,.webp" aria-describedby="profile_photo-error">
                    <label for="profile_photo" class="file-upload-label flex min-h-16 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-brand-50/70 px-3 py-2 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                        <span class="file-upload-text text-xs font-medium text-brand-900">Choose profile photo</span>
                        <span class="mt-1 text-[10px] text-text-muted">Image · Max 5 MB</span>
                    </label>
                    <div class="file-preview mt-2 hidden space-y-1" id="profile_photo_preview"></div>
                    <p id="profile_photo-error" class="hidden text-xs text-error" data-error-for="profile_photo"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="qualification_document" class="block text-xs font-medium text-text-primary">Qualification document</label>
                    @if($doc && !empty($doc->qualification_document))
                        <a href="{{ asset('storage/'.$doc->qualification_document) }}" target="_blank" rel="noopener noreferrer" class="mb-2 inline-flex min-h-9 items-center rounded-control border border-border bg-white px-3 text-xs font-medium text-brand-800 transition hover:bg-brand-50">View current qualification document</a>
                    @else
                        <p class="mb-2 text-[11px] text-text-muted">No qualification document uploaded.</p>
                    @endif
                    <input class="peer sr-only file-upload-input form-control" type="file" name="qualification_document" id="qualification_document" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.bmp,.gif,.tiff,.webp" aria-describedby="qualification_document-error">
                    <label for="qualification_document" class="file-upload-label flex min-h-20 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-brand-50/70 px-3 py-3 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                        <span class="file-upload-text text-xs font-medium text-brand-900">Choose qualification document</span>
                        <span class="mt-1 text-[10px] text-text-muted">Document or image · Max 10 MB</span>
                    </label>
                    <div class="file-preview mt-2 hidden space-y-1" id="qualification_document_preview"></div>
                    <p id="qualification_document-error" class="hidden text-xs text-error" data-error-for="qualification_document"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="id_proof_document" class="block text-xs font-medium text-text-primary">Identity proof</label>
                    <p class="text-[11px] text-text-muted">Upload either Aadhaar Card or PAN Card.</p>
                    @php
                        $hasIdProof = $doc && !empty($doc->id_proof_document);
                        $currentIdProof = $doc->id_proof_document ?? null;
                    @endphp
                    @if($hasIdProof && $currentIdProof)
                        <a href="{{ asset('storage/'.$currentIdProof) }}" target="_blank" rel="noopener noreferrer" class="mb-2 inline-flex min-h-9 items-center rounded-control border border-border bg-white px-3 text-xs font-medium text-brand-800 transition hover:bg-brand-50">View current ID proof</a>
                    @else
                        <p class="mb-2 text-[11px] text-text-muted">No ID proof uploaded.</p>
                    @endif
                    <input class="peer sr-only file-upload-input form-control" type="file" name="id_proof_document" id="id_proof_document" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.bmp,.gif,.tiff,.webp" aria-describedby="id_proof_document-error">
                    <label for="id_proof_document" class="file-upload-label flex min-h-20 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-brand-50/70 px-3 py-3 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                        <span class="file-upload-text text-xs font-medium text-brand-900">Choose Aadhaar or PAN document</span>
                        <span class="mt-1 text-[10px] text-text-muted">Document or image · Max 10 MB</span>
                    </label>
                    <div class="file-preview mt-2 hidden space-y-1" id="id_proof_document_preview"></div>
                    <p id="id_proof_document-error" class="hidden text-xs text-error" data-error-for="id_proof_document"></p>
                </div>

                <div class="rounded-control border border-divider p-3">
                    <label for="has_gst" class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" class="form-check-input mt-0.5 size-4 rounded border-border text-brand-primary focus:ring-brand-primary" id="has_gst" name="has_gst" {{ $hasGst ? 'checked' : '' }}>
                        <span>
                            <span class="block text-xs font-medium text-text-primary">I have GST registration</span>
                            <span class="mt-1 block text-[11px] leading-4 text-text-muted">Optional. Select this to add or update your GST details.</span>
                        </span>
                    </label>
                </div>

                <div id="gst-fields-container" class="{{ $hasGst ? '' : 'hidden' }} space-y-3 rounded-control border border-divider bg-surface-muted p-3">
                    <div class="space-y-1.5">
                        <label for="gst_number" class="block text-xs font-medium text-text-primary">GST number</label>
                        <input
                            class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                            type="text"
                            name="gst_number"
                            id="gst_number"
                            maxlength="15"
                            placeholder="15-digit GST number"
                            value="{{ $existingGstNumber }}"
                            aria-describedby="gst_number-error"
                        >
                        <p class="text-[11px] text-text-muted">Please enter your GST number (10–15 characters).</p>
                        <p id="gst_number-error" class="hidden text-xs text-error" data-error-for="gst_number"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="state_code" class="block text-xs font-medium text-text-primary">GST state</label>
                        <select
                            class="form-control w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                            name="state_code"
                            id="state_code"
                            aria-describedby="state_code-error"
                        >
                            <option value="">Select GST state code</option>
                            <option value="01" data-state="Jammu and Kashmir" {{ $selectedStateCode == '01' ? 'selected' : '' }}>01 - Jammu and Kashmir</option>
                            <option value="02" data-state="Himachal Pradesh" {{ $selectedStateCode == '02' ? 'selected' : '' }}>02 - Himachal Pradesh</option>
                            <option value="03" data-state="Punjab" {{ $selectedStateCode == '03' ? 'selected' : '' }}>03 - Punjab</option>
                            <option value="04" data-state="Chandigarh" {{ $selectedStateCode == '04' ? 'selected' : '' }}>04 - Chandigarh</option>
                            <option value="05" data-state="Uttarakhand" {{ $selectedStateCode == '05' ? 'selected' : '' }}>05 - Uttarakhand</option>
                            <option value="06" data-state="Haryana" {{ $selectedStateCode == '06' ? 'selected' : '' }}>06 - Haryana</option>
                            <option value="07" data-state="Delhi" {{ $selectedStateCode == '07' ? 'selected' : '' }}>07 - Delhi</option>
                            <option value="08" data-state="Rajasthan" {{ $selectedStateCode == '08' ? 'selected' : '' }}>08 - Rajasthan</option>
                            <option value="09" data-state="Uttar Pradesh" {{ $selectedStateCode == '09' ? 'selected' : '' }}>09 - Uttar Pradesh</option>
                            <option value="10" data-state="Bihar" {{ $selectedStateCode == '10' ? 'selected' : '' }}>10 - Bihar</option>
                            <option value="11" data-state="Sikkim" {{ $selectedStateCode == '11' ? 'selected' : '' }}>11 - Sikkim</option>
                            <option value="12" data-state="Arunachal Pradesh" {{ $selectedStateCode == '12' ? 'selected' : '' }}>12 - Arunachal Pradesh</option>
                            <option value="13" data-state="Nagaland" {{ $selectedStateCode == '13' ? 'selected' : '' }}>13 - Nagaland</option>
                            <option value="14" data-state="Manipur" {{ $selectedStateCode == '14' ? 'selected' : '' }}>14 - Manipur</option>
                            <option value="15" data-state="Mizoram" {{ $selectedStateCode == '15' ? 'selected' : '' }}>15 - Mizoram</option>
                            <option value="16" data-state="Tripura" {{ $selectedStateCode == '16' ? 'selected' : '' }}>16 - Tripura</option>
                            <option value="17" data-state="Meghalaya" {{ $selectedStateCode == '17' ? 'selected' : '' }}>17 - Meghalaya</option>
                            <option value="18" data-state="Assam" {{ $selectedStateCode == '18' ? 'selected' : '' }}>18 - Assam</option>
                            <option value="19" data-state="West Bengal" {{ $selectedStateCode == '19' ? 'selected' : '' }}>19 - West Bengal</option>
                            <option value="20" data-state="Jharkhand" {{ $selectedStateCode == '20' ? 'selected' : '' }}>20 - Jharkhand</option>
                            <option value="21" data-state="Odisha" {{ $selectedStateCode == '21' ? 'selected' : '' }}>21 - Odisha</option>
                            <option value="22" data-state="Chhattisgarh" {{ $selectedStateCode == '22' ? 'selected' : '' }}>22 - Chhattisgarh</option>
                            <option value="23" data-state="Madhya Pradesh" {{ $selectedStateCode == '23' ? 'selected' : '' }}>23 - Madhya Pradesh</option>
                            <option value="24" data-state="Gujarat" {{ $selectedStateCode == '24' ? 'selected' : '' }}>24 - Gujarat</option>
                            <option value="25" data-state="Daman and Diu" {{ $selectedStateCode == '25' ? 'selected' : '' }}>25 - Daman and Diu</option>
                            <option value="26" data-state="Dadra and Nagar Haveli" {{ $selectedStateCode == '26' ? 'selected' : '' }}>26 - Dadra and Nagar Haveli</option>
                            <option value="27" data-state="Maharashtra" {{ $selectedStateCode == '27' ? 'selected' : '' }}>27 - Maharashtra</option>
                            <option value="28" data-state="Andhra Pradesh" {{ $selectedStateCode == '28' ? 'selected' : '' }}>28 - Andhra Pradesh</option>
                            <option value="29" data-state="Karnataka" {{ $selectedStateCode == '29' ? 'selected' : '' }}>29 - Karnataka</option>
                            <option value="30" data-state="Goa" {{ $selectedStateCode == '30' ? 'selected' : '' }}>30 - Goa</option>
                            <option value="31" data-state="Lakshadweep" {{ $selectedStateCode == '31' ? 'selected' : '' }}>31 - Lakshadweep</option>
                            <option value="32" data-state="Kerala" {{ $selectedStateCode == '32' ? 'selected' : '' }}>32 - Kerala</option>
                            <option value="33" data-state="Tamil Nadu" {{ $selectedStateCode == '33' ? 'selected' : '' }}>33 - Tamil Nadu</option>
                            <option value="34" data-state="Puducherry" {{ $selectedStateCode == '34' ? 'selected' : '' }}>34 - Puducherry</option>
                            <option value="35" data-state="Andaman and Nicobar Islands" {{ $selectedStateCode == '35' ? 'selected' : '' }}>35 - Andaman and Nicobar Islands</option>
                            <option value="36" data-state="Telangana" {{ $selectedStateCode == '36' ? 'selected' : '' }}>36 - Telangana</option>
                            <option value="37" data-state="Andhra Pradesh (New)" {{ $selectedStateCode == '37' ? 'selected' : '' }}>37 - Andhra Pradesh (New)</option>
                            <option value="38" data-state="Ladakh" {{ $selectedStateCode == '38' ? 'selected' : '' }}>38 - Ladakh</option>
                        </select>
                        <p id="state_code-error" class="hidden text-xs text-error" data-error-for="state_code"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="gst_address" class="block text-xs font-medium text-text-primary">GST registered address</label>
                        <textarea
                            class="form-control w-full resize-y rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                            name="gst_address"
                            id="gst_address"
                            placeholder="Enter your GST registered address"
                            rows="3"
                            aria-describedby="gst_address-error"
                        >{{ old('gst_address', $profile->gst_address ?? $professional->gst_address ?? '') }}</textarea>
                        <p id="gst_address-error" class="hidden text-xs text-error" data-error-for="gst_address"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="gst_certificate" class="block text-xs font-medium text-text-primary">GST certificate</label>
                        @if(!empty($existingGstCertificate))
                            <a href="{{ asset('storage/'.$existingGstCertificate) }}" target="_blank" rel="noopener noreferrer" class="mb-2 inline-flex min-h-9 items-center rounded-control border border-border bg-white px-3 text-xs font-medium text-brand-800 transition hover:bg-brand-50">View current GST certificate</a>
                        @else
                            <p class="mb-2 text-[11px] text-text-muted">No GST certificate uploaded.</p>
                        @endif
                        <input class="peer sr-only file-upload-input form-control" type="file" name="gst_certificate" id="gst_certificate" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.bmp,.gif,.tiff,.webp" aria-describedby="gst_certificate-error">
                        <label for="gst_certificate" class="file-upload-label flex min-h-16 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-white px-3 py-2 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                            <span class="file-upload-text text-xs font-medium text-brand-900">Choose GST certificate</span>
                            <span class="mt-1 text-[10px] text-text-muted">Max 10 MB</span>
                        </label>
                        <div class="file-preview mt-2 hidden space-y-1" id="gst_certificate_preview"></div>
                        <p id="gst_certificate-error" class="hidden text-xs text-error" data-error-for="gst_certificate"></p>
                    </div>
                </div>

                <p class="rounded-control border border-info/20 bg-info-surface px-3 py-2.5 text-[11px] leading-4 text-info">
                    Profile photo and gallery images can also be managed from your professional profile after approval.
                </p>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button type="button" class="prev-btn inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-white px-3 text-sm font-medium text-text-primary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Back</button>
                    <button type="submit" id="resubmit-submit" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-primary px-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-60">
                        Re-submit application
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-divider pt-4">
            <a href="{{ route('professional.logout') }}" class="inline-flex min-h-9 items-center text-xs font-medium text-text-secondary underline-offset-4 hover:text-brand-primary hover:underline">Sign out</a>
            <p class="text-[11px] text-text-muted">© {{ date('Y') }} Yuktione</p>
        </div>
    </section>
</main>

{{-- Rejection modal --}}
<div id="rejectionModal" class="custom-modal fixed inset-0 z-[60] items-center justify-center overflow-y-auto bg-brand-950/55 p-4" role="dialog" aria-modal="true" aria-labelledby="rejection-modal-title">
    <div class="custom-modal-content w-full max-w-md rounded-panel border border-divider bg-white p-5 text-center shadow-popover sm:p-7">
        <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" class="mx-auto mb-4 h-9 w-auto max-w-36 object-contain">
        <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-error-surface text-error" aria-hidden="true">
            <svg class="size-5" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="12" cy="16" r="1" fill="currentColor"/>
            </svg>
        </div>
        <h2 id="rejection-modal-title" class="text-xl font-semibold text-brand-950">Application needs updates</h2>
        <div class="rejection-reason mt-3 rounded-control border border-divider bg-surface-muted px-3.5 py-3 text-left text-sm leading-6 text-text-secondary whitespace-pre-wrap break-words">{{ $rejectionReason }}</div>
        <p class="resubmit-hint mt-3 text-[13px] leading-5 text-text-muted">Review the feedback, update the requested details, then resubmit your application.</p>
        <button type="button" class="resubmit-btn mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring" onclick="resubmitDetails()">
            Continue to update form
        </button>
    </div>
</div>

<div id="loading-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-brand-950/40 p-4" role="status" aria-live="polite" aria-label="Submitting updates">
    <div class="w-full max-w-xs rounded-panel bg-white p-6 text-center shadow-popover">
        <span class="mx-auto mb-3 block size-8 animate-spin rounded-full border-4 border-brand-100 border-t-brand-primary" aria-hidden="true"></span>
        <p class="text-sm font-semibold text-brand-950">Updating your application</p>
        <p class="mt-1 text-xs text-text-muted">Please wait while your details are saved.</p>
    </div>
</div>

<x-yuktione.feedback />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@vite('resources/js/yuktione-feedback.js')

<script>
    function refreshCSRFToken() {
        $.get('/csrf-token', function (data) {
            $('meta[name="csrf-token"]').attr('content', data.csrf_token);
            $('input[name="_token"]').val(data.csrf_token);
        }).fail(function () {
            console.warn('Failed to refresh CSRF token');
        });
    }

    function notifyError(message) {
        if (window.YuktioneToast?.error) {
            window.YuktioneToast.error(message);
        } else {
            toastr.error(message, 'Error', { timeOut: 5000, closeButton: true });
        }
    }

    function notifySuccess(message) {
        if (window.YuktioneToast?.success) {
            window.YuktioneToast.success(message);
        } else {
            toastr.success(message, 'Success', { timeOut: 3000, closeButton: true });
        }
    }

    function notifyInfo(message) {
        if (window.YuktioneToast?.info) {
            window.YuktioneToast.info(message);
        } else {
            toastr.info(message, 'Please Wait', { timeOut: 10000, closeButton: false });
        }
    }

    function showFieldError(fieldName, errorMessage) {
        const field = $(`[name="${fieldName}"]`).first();
        field.addClass('is-invalid').attr('aria-invalid', 'true');

        const errorEl = $(`[data-error-for="${fieldName}"]`);
        if (errorEl.length) {
            errorEl.text(errorMessage).removeClass('hidden');
        } else {
            field.siblings('.invalid-feedback').remove();
            field.after(`<div class="invalid-feedback show">${errorMessage}</div>`);
        }
    }

    function clearFieldErrors() {
        $('.form-control, input, select, textarea').removeClass('is-invalid').removeAttr('aria-invalid');
        $('[data-error-for]').addClass('hidden').text('');
        $('.invalid-feedback').remove();
    }

    function updateStepChrome(step) {
        const label = document.querySelector('[data-resubmit-step-label]');
        const progress = document.querySelector('[data-resubmit-progress]');
        const bar = document.querySelector('[data-resubmit-progressbar]');
        if (label) label.textContent = `Step ${step} of 3`;
        if (progress) progress.style.width = `${(step / 3) * 100}%`;
        if (bar) bar.setAttribute('aria-valuenow', String(step));
    }

    function goToStep(step) {
        $('.form-step').removeClass('active');
        $(`.step-${step}`).addClass('active');
        updateStepChrome(step);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        let isValid = true;

        if (step === 1) {
            const name = $('[name="name"]').val().trim();
            const phone = $('[name="phone"]').val().trim();

            if (name && name.length < 2) {
                showFieldError('name', 'Name must be at least 2 characters');
                isValid = false;
            }

            if (phone && (phone.length < 6 || phone.length > 15 || !/^\d+$/.test(phone))) {
                showFieldError('phone', 'Please enter a valid phone number (6-15 digits)');
                isValid = false;
            }
        } else if (step === 2) {
            const startingPrice = $('[name="starting_price"]').val().trim();
            if (startingPrice) {
                const pricePattern = /^(\d+(\.\d{1,2})?(-\d+(\.\d{1,2})?)?)$/;
                if (!pricePattern.test(startingPrice)) {
                    showFieldError('starting_price', 'Please enter a valid price (e.g., 1000 or 1000-2000)');
                    isValid = false;
                }
            }
        } else if (step === 3) {
            if ($('#has_gst').is(':checked')) {
                const gstNumber = $('[name="gst_number"]').val().trim();
                const stateCode = $('[name="state_code"]').val().trim();
                const gstAddress = $('[name="gst_address"]').val().trim();

                if (gstNumber && (gstNumber.length < 10 || gstNumber.length > 15)) {
                    showFieldError('gst_number', 'Please enter a valid GST number (10-15 characters)');
                    isValid = false;
                }

                if (!gstNumber) {
                    showFieldError('gst_number', 'GST number is required when providing GST details');
                    isValid = false;
                }

                if (!stateCode) {
                    showFieldError('state_code', 'GST state is required when providing GST details');
                    isValid = false;
                }

                if (!gstAddress) {
                    showFieldError('gst_address', 'GST address is required when providing GST details');
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    function setLoading(isLoading) {
        const overlay = document.getElementById('loading-overlay');
        const submitBtn = document.getElementById('resubmit-submit');
        if (overlay) {
            overlay.classList.toggle('hidden', !isLoading);
            overlay.classList.toggle('flex', isLoading);
        }
        if (submitBtn) submitBtn.disabled = isLoading;
    }

    function defaultUploadLabel(inputId) {
        if (inputId === 'qualification_document') return 'Choose qualification document';
        if (inputId === 'id_proof_document') return 'Choose Aadhaar or PAN document';
        if (inputId === 'gst_certificate') return 'Choose GST certificate';
        return 'Choose profile photo';
    }

    function bindFileUploads() {
        document.querySelectorAll('.file-upload-input').forEach((input) => {
            input.addEventListener('change', () => {
                const preview = document.getElementById(`${input.id}_preview`);
                const label = document.querySelector(`label[for="${input.id}"].file-upload-label`);
                const labelText = label?.querySelector('.file-upload-text');
                const errorEl = document.querySelector(`[data-error-for="${input.id}"]`);

                if (preview) {
                    preview.replaceChildren();
                    preview.classList.add('hidden');
                }
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.classList.add('hidden');
                }
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');

                const file = input.files?.[0];
                if (!file) {
                    if (labelText) labelText.textContent = defaultUploadLabel(input.id);
                    return;
                }

                const maxSize = input.id === 'profile_photo' ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    input.value = '';
                    const message = `This file is too large. Maximum size is ${input.id === 'profile_photo' ? '5' : '10'} MB.`;
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    }
                    input.classList.add('is-invalid');
                    return;
                }

                if (labelText) labelText.textContent = file.name;
                if (!preview) return;

                const item = document.createElement('div');
                item.className = 'flex items-center justify-between gap-2 rounded-control border border-divider bg-surface-muted px-3 py-2.5 text-xs text-text-muted';
                const name = document.createElement('span');
                name.className = 'min-w-0 truncate';
                name.textContent = `${file.name} · ${(file.size / (1024 * 1024)).toFixed(1)} MB`;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'shrink-0 rounded-control px-3 py-1 text-xs font-medium text-error hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
                remove.textContent = 'Remove';
                remove.setAttribute('aria-label', `Remove ${file.name}`);
                remove.addEventListener('click', () => {
                    input.value = '';
                    preview.replaceChildren();
                    preview.classList.add('hidden');
                    if (labelText) labelText.textContent = defaultUploadLabel(input.id);
                });
                item.append(name, remove);
                preview.append(item);
                preview.classList.remove('hidden');
            });
        });
    }

    function ensureStateName() {
        const selectedOption = $('#state_code').find('option:selected');
        const stateName = selectedOption.data('state');
        if (!stateName) return;

        if (!$('#state_name').length) {
            $('<input>').attr({
                type: 'hidden',
                id: 'state_name',
                name: 'state_name',
                value: stateName
            }).appendTo('#registerForm');
        } else {
            $('#state_name').val(stateName);
        }
    }

    function resubmitDetails() {
        const modal = document.getElementById('rejectionModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.style.display = 'none';
        }
    }

    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let currentStep = 1;
        updateStepChrome(currentStep);
        bindFileUploads();

        const modal = document.getElementById('rejectionModal');
        if (modal) {
            modal.classList.add('is-open');
            modal.style.display = 'flex';
        }

        $('.next-btn').click(function () {
            clearFieldErrors();
            if (!validateStep(currentStep)) {
                notifyError('Please fix the validation errors before proceeding to the next step.');
                const $firstError = $('.is-invalid').first();
                if ($firstError.length) {
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 400);
                }
                return;
            }

            if (currentStep < 3) {
                currentStep++;
                goToStep(currentStep);
            }
        });

        $('.prev-btn').click(function () {
            if (currentStep > 1) {
                currentStep--;
                goToStep(currentStep);
            }
        });

        $('#has_gst').on('change', function () {
            const isChecked = $(this).is(':checked');
            const $gstContainer = $('#gst-fields-container');

            if (isChecked) {
                $gstContainer.removeClass('hidden');
            } else {
                $gstContainer.addClass('hidden');
                $('#gst_number').val('');
                $('#state_code').val('');
                $('#gst_address').val('');
                $('#gst_certificate').val('');
                $('#state_name').val('');
                $('#gst_number, #state_code, #gst_address, #gst_certificate').removeClass('is-invalid').removeAttr('aria-invalid');
                $('[data-error-for="gst_number"], [data-error-for="state_code"], [data-error-for="gst_address"], [data-error-for="gst_certificate"]').addClass('hidden').text('');
            }
        });

        $('#state_code').on('change', ensureStateName);
        ensureStateName();

        $('#registerForm').submit(function (e) {
            e.preventDefault();
            clearFieldErrors();

            let isFormValid = true;
            for (let step = 1; step <= 3; step++) {
                if (!validateStep(step)) {
                    isFormValid = false;
                }
            }

            if (!isFormValid) {
                notifyError('Please fix the validation errors before submitting your updates.');
                const $firstError = $('.is-invalid').first();
                if ($firstError.length) {
                    let errorStep = 1;
                    if ($firstError.closest('.step-2').length) errorStep = 2;
                    else if ($firstError.closest('.step-3').length) errorStep = 3;
                    currentStep = errorStep;
                    goToStep(errorStep);
                    setTimeout(() => {
                        $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 400);
                    }, 200);
                }
                return;
            }

            ensureStateName();
            notifyInfo('Updating your profile...');
            setLoading(true);

            const formData = new FormData(this);
            refreshCSRFToken();

            $.ajax({
                url: "{{ route('professional.register.re-submit') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    setLoading(false);
                    if (response.status === 'success') {
                        notifySuccess(response.message);
                        setTimeout(() => {
                            window.location.href = "{{ route('professional.login') }}";
                        }, 2000);
                    } else {
                        notifyError(response.message || 'Update failed.');
                    }
                },
                error: function (xhr) {
                    setLoading(false);
                    if (xhr.status === 419) {
                        refreshCSRFToken();
                        notifyError('Your session has expired. Please refresh the page and try again.');
                    } else if (xhr.responseJSON?.errors) {
                        let errorCount = 0;
                        let firstErrorStep = 1;

                        Object.keys(xhr.responseJSON.errors).forEach((field) => {
                            const errorMessages = xhr.responseJSON.errors[field];
                            errorMessages.forEach((errorMessage) => {
                                errorCount++;
                                showFieldError(field, errorMessage);
                                const $field = $(`[name="${field}"]`);
                                if ($field.closest('.step-2').length && firstErrorStep === 1) firstErrorStep = 2;
                                else if ($field.closest('.step-3').length && firstErrorStep < 3) firstErrorStep = 3;
                            });
                        });

                        currentStep = firstErrorStep;
                        goToStep(firstErrorStep);
                        notifyError(`Please fix ${errorCount} validation error${errorCount > 1 ? 's' : ''} and try again.`);
                        setTimeout(() => {
                            const $firstError = $('.is-invalid').first();
                            if ($firstError.length) {
                                $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 400);
                            }
                        }, 200);
                    } else if (xhr.responseJSON?.message) {
                        notifyError(xhr.responseJSON.message);
                    } else {
                        notifyError('Something went wrong. Please check your internet connection and try again.');
                    }
                }
            });
        });

        @if (session('success'))
            notifySuccess(@json(session('success')));
        @endif
        @if (session('error'))
            notifyError(@json(session('error')));
        @endif
        @if (session('warning'))
            toastr.warning(@json(session('warning')));
        @endif
    });
</script>
</body>
</html>
