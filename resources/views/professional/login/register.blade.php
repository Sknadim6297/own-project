<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Create your Yuktione professional account">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#316D61">
    <title>Professional Registration | YuktiOne</title>
    @include('partials.dm-sans')
    <link rel="icon" type="image/png" href="{{ asset('frontend/assets/Yuktione_logo.png') }}">
    @vite(['resources/css/tazen.css', 'resources/css/yuktione-feedback.css', 'resources/css/auth-theme.css'])
</head>
<body class="yuktione-auth-page min-h-screen bg-background font-sans text-text-primary antialiased">
    <main class="flex min-h-screen items-center justify-center bg-linear-to-br from-brand-50 via-background to-white px-3 py-5 sm:px-5">
        <section class="w-full max-w-xl rounded-panel border border-divider bg-surface p-4 shadow-card sm:p-6 md:p-8" aria-labelledby="registration-title">
            <header class="mb-5 sm:mb-7">
                <div class="mb-4 flex items-center justify-between gap-3 sm:mb-6">
                    <a href="{{ route('home') }}" aria-label="Yuktione home" class="inline-flex items-center">
                        <img src="{{ asset('frontend/assets/Yuktione_logo.png') }}" alt="Yuktione" class="h-8 w-auto max-w-36 object-contain sm:h-9">
                    </a>
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[9px] font-semibold tracking-wide text-brand-800 sm:px-3 sm:text-[10px]">PROFESSIONAL ONBOARDING</span>
                </div>

                <div class="mb-1.5 flex items-center justify-between gap-3">
                    <h1 id="registration-title" class="text-xl font-semibold leading-tight tracking-tight text-brand-950 sm:text-2xl md:text-3xl">Create your account</h1>
                    <span class="shrink-0 text-xs font-medium text-text-muted" data-registration-step-label>Step 1 of 3</span>
                </div>
                <p class="mt-1.5 text-sm leading-6 text-text-muted">Join Yuktione and grow your professional practice.</p>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-brand-100" role="progressbar" aria-label="Registration progress" aria-valuemin="1" aria-valuemax="3" aria-valuenow="1">
                    <div class="h-full w-1/3 rounded-full bg-brand-primary transition-[width] duration-300" data-registration-progress></div>
                </div>
            </header>

            <form
                id="registerForm"
                method="post"
                action="{{ route('professional.register.submit') }}"
                enctype="multipart/form-data"
                novalidate
                data-send-otp-url="{{ route('professional.send.otp') }}"
                data-verify-otp-url="{{ route('professional.verify.otp') }}"
            >
                @csrf

                <section class="space-y-3.5 sm:space-y-4" data-registration-step="1" aria-labelledby="email-step-title">
                    <div>
                        <h2 id="email-step-title" class="text-[17px] font-semibold text-brand-950">Verify your email</h2>
                        <p class="mt-1 text-[13px] leading-5 text-text-muted">We’ll send a one-time code to confirm your email address.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="verify-email" class="block text-xs font-medium text-text-primary">Email address</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-brand-600" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M2.5 5.833A1.667 1.667 0 0 1 4.167 4.167h11.666A1.667 1.667 0 0 1 17.5 5.833v8.334a1.667 1.667 0 0 1-1.667 1.666H4.167A1.667 1.667 0 0 1 2.5 14.167V5.833Z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="m3.333 5 6.667 5L16.667 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input
                                id="verify-email"
                                type="email"
                                autocomplete="email"
                                placeholder="you@example.com"
                                class="w-full rounded-control border border-border bg-white py-3 pl-10 pr-3 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error"
                                aria-describedby="verify-email-error"
                                required
                            >
                        </div>
                        <p id="verify-email-error" class="hidden text-xs text-error" data-error-for="verify-email"></p>
                    </div>

                    <button type="button" id="send-otp-btn" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-60">
                        Send Verification Code
                    </button>

                    <div id="otp-section" class="hidden space-y-3 rounded-control border border-divider bg-surface-muted p-3.5">
                        <div>
                            <h3 class="text-sm font-semibold text-brand-950">Enter your verification code</h3>
                            <p class="mt-1 text-xs leading-5 text-text-muted">The 6-digit code expires in 10 minutes.</p>
                        </div>
                        <div class="flex justify-between gap-1.5" role="group" aria-label="6-digit email verification code">
                            @for ($digit = 1; $digit <= 6; $digit++)
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}"
                                    maxlength="1"
                                    aria-label="Digit {{ $digit }} of 6"
                                    class="size-10 min-w-0 rounded-control border border-border bg-white text-center text-base font-semibold text-brand-950 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error sm:size-11"
                                    data-otp-digit
                                >
                            @endfor
                        </div>
                        <input type="hidden" id="otp" name="otp">
                        <p id="otp-error" class="hidden text-xs text-error" data-error-for="otp"></p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" id="verify-otp-btn" class="inline-flex min-h-10 items-center justify-center rounded-control bg-brand-primary px-3 text-xs font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-60" disabled>Verify Code</button>
                            <button type="button" id="resend-otp-btn" class="inline-flex min-h-10 items-center justify-center rounded-control border border-brand-200 bg-white px-3 text-xs font-semibold text-brand-800 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Resend Code</button>
                        </div>
                    </div>

                    <div id="personal-info-section" class="hidden space-y-3 border-t border-divider pt-4">
                        <div>
                            <h3 class="text-sm font-semibold text-brand-950">Your details</h3>
                            <p class="mt-1 text-xs text-text-muted">Tell us a little about yourself.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label for="first_name" class="block text-xs font-medium">First name</label>
                                <input id="first_name" name="first_name" type="text" autocomplete="given-name" placeholder="First name" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="first_name-error">
                                <p id="first_name-error" class="hidden text-xs text-error" data-error-for="first_name"></p>
                            </div>
                            <div class="space-y-1.5">
                                <label for="last_name" class="block text-xs font-medium">Last name</label>
                                <input id="last_name" name="last_name" type="text" autocomplete="family-name" placeholder="Last name" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="last_name-error">
                                <p id="last_name-error" class="hidden text-xs text-error" data-error-for="last_name"></p>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label for="phone" class="block text-xs font-medium">Phone number</label>
                            <input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="Phone number" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="phone-error">
                            <p id="phone-error" class="hidden text-xs text-error" data-error-for="phone"></p>
                        </div>
                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-medium">Password</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 6 characters" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="password-error">
                            <p id="password-error" class="hidden text-xs text-error" data-error-for="password"></p>
                        </div>
                        <div class="space-y-1.5">
                            <label for="password_confirmation" class="block text-xs font-medium">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter password" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="password_confirmation-error">
                            <p id="password_confirmation-error" class="hidden text-xs text-error" data-error-for="password_confirmation"></p>
                        </div>
                    </div>

                    <button type="button" id="step1-next" class="inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-50" disabled>
                        Continue
                    </button>
                </section>

                <section class="hidden space-y-4" data-registration-step="2" aria-labelledby="professional-step-title">
                    <div>
                        <h2 id="professional-step-title" class="text-[17px] font-semibold text-brand-950">Professional details</h2>
                        <p class="mt-1 text-[13px] leading-5 text-text-muted">Help clients understand your experience and services.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="specialization" class="block text-xs font-medium">Service you offer</label>
                        <select id="specialization" name="specialization" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="specialization-error">
                            <option value="">Select a service</option>
                            @foreach($services as $service)
                                <option value="{{ $service->name }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                        <p id="specialization-error" class="hidden text-xs text-error" data-error-for="specialization"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="experience" class="block text-xs font-medium">Professional experience</label>
                        <select id="experience" name="experience" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="experience-error">
                            <option value="">Select experience</option>
                            <option value="0-2">0–2 years</option>
                            <option value="2-4">2–4 years</option>
                            <option value="4-6">4–6 years</option>
                            <option value="6-8">6–8 years</option>
                            <option value="8-10">8–10 years</option>
                            <option value="10+">10+ years</option>
                        </select>
                        <p id="experience-error" class="hidden text-xs text-error" data-error-for="experience"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="starting_price" class="block text-xs font-medium">Starting price</label>
                        <input id="starting_price" name="starting_price" type="text" placeholder="e.g. 1500 or 1000-2000" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="starting_price-help starting_price-error">
                        <p id="starting_price-help" class="text-[11px] text-text-muted">Enter a fixed price or a price range per session.</p>
                        <p id="starting_price-error" class="hidden text-xs text-error" data-error-for="starting_price"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="address" class="block text-xs font-medium">Location</label>
                        <select id="address" name="address" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="address-error">
                            <option value="">Select your city</option>
                            <option value="Mumbai">Mumbai</option>
                            <option value="Kolkata">Kolkata</option>
                            <option value="Delhi">Delhi</option>
                            <option value="Bangalore">Bangalore</option>
                            <option value="Chennai">Chennai</option>
                            <option value="Hyderabad">Hyderabad</option>
                            <option value="Pune">Pune</option>
                            <option value="Ahmedabad">Ahmedabad</option>
                            <option value="Surat">Surat</option>
                            <option value="Jaipur">Jaipur</option>
                        </select>
                        <p id="address-error" class="hidden text-xs text-error" data-error-for="address"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="education" class="block text-xs font-medium">Education or qualification</label>
                        <input id="education" name="education" type="text" placeholder="Your education or qualification" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="education-error">
                        <p id="education-error" class="hidden text-xs text-error" data-error-for="education"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="education2" class="block text-xs font-medium">Additional education <span class="font-normal text-text-muted">(optional)</span></label>
                        <input id="education2" name="education2" type="text" placeholder="Additional qualification" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error">
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button" class="prev-btn inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-white px-3 text-sm font-medium text-text-primary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Back</button>
                        <button type="button" class="next-btn inline-flex min-h-11 items-center justify-center rounded-control bg-brand-primary px-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Continue</button>
                    </div>
                </section>

                <section class="hidden space-y-4" data-registration-step="3" aria-labelledby="documents-step-title">
                    <div>
                        <h2 id="documents-step-title" class="text-[17px] font-semibold text-brand-950">Documents</h2>
                        <p class="mt-1 text-[13px] leading-5 text-text-muted">Upload the documents needed to review your application.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="qualification_document" class="block text-xs font-medium">Qualification document <span class="text-error">*</span></label>
                        <input class="peer sr-only file-upload-input" type="file" name="qualification_document" id="qualification_document" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx,.txt,.bmp,.gif,.tiff,.webp" aria-describedby="qualification_document-error">
                        <label for="qualification_document" class="file-upload-label flex min-h-20 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-brand-50/70 px-3 py-3 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                            <span class="file-upload-text text-xs font-medium text-brand-900">Choose qualification document</span>
                            <span class="mt-1 text-[10px] text-text-muted">Document or image · Max 10 MB</span>
                        </label>
                        <div class="file-preview hidden space-y-1" id="qualification_document_preview"></div>
                        <p id="qualification_document-error" class="hidden text-xs text-error" data-error-for="qualification_document"></p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="id_proof_document" class="block text-xs font-medium">Identity proof <span class="text-error">*</span></label>
                        <input class="peer sr-only file-upload-input" type="file" name="id_proof_document" id="id_proof_document" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx,.txt,.bmp,.gif,.tiff,.webp" aria-describedby="id_proof_document-error">
                        <label for="id_proof_document" class="file-upload-label flex min-h-20 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-brand-50/70 px-3 py-3 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                            <span class="file-upload-text text-xs font-medium text-brand-900">Choose Aadhaar or PAN document</span>
                            <span class="mt-1 text-[10px] text-text-muted">Document or image · Max 10 MB</span>
                        </label>
                        <div class="file-preview hidden space-y-1" id="id_proof_document_preview"></div>
                        <p id="id_proof_document-error" class="hidden text-xs text-error" data-error-for="id_proof_document"></p>
                    </div>

                    <div class="rounded-control border border-divider p-3">
                        <label for="has_gst" class="flex cursor-pointer items-start gap-2.5">
                            <input type="checkbox" id="has_gst" name="has_gst" class="mt-0.5 size-4 rounded border-border text-brand-primary focus:ring-brand-primary">
                            <span>
                                <span class="block text-xs font-medium text-text-primary">I have GST registration</span>
                                <span class="mt-1 block text-[11px] leading-4 text-text-muted">Optional. Select this to add your GST details.</span>
                            </span>
                        </label>
                    </div>

                    <div id="gst-fields-container" class="hidden space-y-3 rounded-control border border-divider bg-surface-muted p-3">
                        <div class="space-y-1.5">
                            <label for="gst_number" class="block text-xs font-medium">GST number <span class="text-error">*</span></label>
                            <input id="gst_number" name="gst_number" type="text" maxlength="15" placeholder="15-digit GST number" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="gst_number-error">
                            <p id="gst_number-error" class="hidden text-xs text-error" data-error-for="gst_number"></p>
                        </div>
                        <div class="space-y-1.5">
                            <label for="state_code" class="block text-xs font-medium">GST state <span class="text-error">*</span></label>
                            <select id="state_code" name="state_code" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error" aria-describedby="state_code-error">
                                <option value="">Select state</option>
                                <option value="01" data-state="Jammu and Kashmir">01 - Jammu and Kashmir</option>
                                <option value="02" data-state="Himachal Pradesh">02 - Himachal Pradesh</option>
                                <option value="03" data-state="Punjab">03 - Punjab</option>
                                <option value="04" data-state="Chandigarh">04 - Chandigarh</option>
                                <option value="05" data-state="Uttarakhand">05 - Uttarakhand</option>
                                <option value="06" data-state="Haryana">06 - Haryana</option>
                                <option value="07" data-state="Delhi">07 - Delhi</option>
                                <option value="08" data-state="Rajasthan">08 - Rajasthan</option>
                                <option value="09" data-state="Uttar Pradesh">09 - Uttar Pradesh</option>
                                <option value="10" data-state="Bihar">10 - Bihar</option>
                                <option value="11" data-state="Sikkim">11 - Sikkim</option>
                                <option value="12" data-state="Arunachal Pradesh">12 - Arunachal Pradesh</option>
                                <option value="13" data-state="Nagaland">13 - Nagaland</option>
                                <option value="14" data-state="Manipur">14 - Manipur</option>
                                <option value="15" data-state="Mizoram">15 - Mizoram</option>
                                <option value="16" data-state="Tripura">16 - Tripura</option>
                                <option value="17" data-state="Meghalaya">17 - Meghalaya</option>
                                <option value="18" data-state="Assam">18 - Assam</option>
                                <option value="19" data-state="West Bengal">19 - West Bengal</option>
                                <option value="20" data-state="Jharkhand">20 - Jharkhand</option>
                                <option value="21" data-state="Odisha">21 - Odisha</option>
                                <option value="22" data-state="Chhattisgarh">22 - Chhattisgarh</option>
                                <option value="23" data-state="Madhya Pradesh">23 - Madhya Pradesh</option>
                                <option value="24" data-state="Gujarat">24 - Gujarat</option>
                                <option value="25" data-state="Daman and Diu">25 - Daman and Diu</option>
                                <option value="26" data-state="Dadra and Nagar Haveli">26 - Dadra and Nagar Haveli</option>
                                <option value="27" data-state="Maharashtra">27 - Maharashtra</option>
                                <option value="28" data-state="Andhra Pradesh">28 - Andhra Pradesh</option>
                                <option value="29" data-state="Karnataka">29 - Karnataka</option>
                                <option value="30" data-state="Goa">30 - Goa</option>
                                <option value="31" data-state="Lakshadweep">31 - Lakshadweep</option>
                                <option value="32" data-state="Kerala">32 - Kerala</option>
                                <option value="33" data-state="Tamil Nadu">33 - Tamil Nadu</option>
                                <option value="34" data-state="Puducherry">34 - Puducherry</option>
                                <option value="35" data-state="Andaman and Nicobar Islands">35 - Andaman and Nicobar Islands</option>
                                <option value="36" data-state="Telangana">36 - Telangana</option>
                                <option value="37" data-state="Andhra Pradesh (New)">37 - Andhra Pradesh (New)</option>
                                <option value="38" data-state="Ladakh">38 - Ladakh</option>
                            </select>
                            <p id="state_code-error" class="hidden text-xs text-error" data-error-for="state_code"></p>
                        </div>
                        <div class="space-y-1.5">
                            <label for="gst_address" class="block text-xs font-medium">GST registered address <span class="text-error">*</span></label>
                            <textarea id="gst_address" name="gst_address" rows="3" placeholder="Enter your GST registered address" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary placeholder:text-text-muted/70 focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100 aria-[invalid=true]:border-error resize-y" aria-describedby="gst_address-error"></textarea>
                            <p id="gst_address-error" class="hidden text-xs text-error" data-error-for="gst_address"></p>
                        </div>
                        <div class="space-y-1.5">
                            <label for="gst_certificate" class="block text-xs font-medium">GST certificate <span class="text-error">*</span></label>
                            <input class="peer sr-only file-upload-input" type="file" name="gst_certificate" id="gst_certificate" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx,.txt,.bmp,.gif,.tiff,.webp" aria-describedby="gst_certificate-error">
                            <label for="gst_certificate" class="file-upload-label flex min-h-16 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-white px-3 py-2 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                                <span class="file-upload-text text-xs font-medium text-brand-900">Choose GST certificate</span>
                                <span class="mt-1 text-[10px] text-text-muted">Max 10 MB</span>
                            </label>
                            <div class="file-preview hidden space-y-1" id="gst_certificate_preview"></div>
                            <p id="gst_certificate-error" class="hidden text-xs text-error" data-error-for="gst_certificate"></p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="profile_photo" class="block text-xs font-medium">Profile photo <span class="font-normal text-text-muted">(optional)</span></label>
                        <input class="peer sr-only file-upload-input" type="file" name="profile_photo" id="profile_photo" accept=".jpeg,.jpg,.png,.bmp,.gif,.tiff,.webp" aria-describedby="profile_photo-error">
                        <label for="profile_photo" class="file-upload-label flex min-h-16 cursor-pointer flex-col items-center justify-center rounded-control border border-dashed border-brand-300 bg-brand-50/70 px-3 py-2 text-center transition hover:border-brand-primary hover:bg-brand-50 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus-ring">
                            <span class="file-upload-text text-xs font-medium text-brand-900">Choose profile photo</span>
                            <span class="mt-1 text-[10px] text-text-muted">Image · Max 5 MB</span>
                        </label>
                        <div class="file-preview hidden space-y-1" id="profile_photo_preview"></div>
                        <p id="profile_photo-error" class="hidden text-xs text-error" data-error-for="profile_photo"></p>
                    </div>

                    <p class="rounded-control border border-info/20 bg-info-surface px-3 py-2.5 text-[11px] leading-4 text-info">Gallery images can be added to your professional profile after your registration is approved.</p>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button" class="prev-btn inline-flex min-h-11 items-center justify-center rounded-control border border-border bg-white px-3 text-sm font-medium text-text-primary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Back</button>
                        <button type="submit" id="register-submit" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-primary px-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:opacity-60">Submit application</button>
                    </div>
                </section>
            </form>

            <div id="loading-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-brand-950/40 p-4" role="status" aria-live="polite" aria-label="Submitting registration">
                <div class="w-full max-w-xs rounded-panel bg-white p-6 text-center shadow-popover">
                    <span class="mx-auto mb-3 block size-8 animate-spin rounded-full border-4 border-brand-100 border-t-brand-primary" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-brand-950">Submitting your application</p>
                    <p class="mt-1 text-xs text-text-muted">Please wait while your documents are uploaded.</p>
                </div>
            </div>

            <p class="mt-4 text-center text-[11px] text-text-muted sm:mt-5">© {{ date('Y') }} Yuktione</p>
        </section>
    </main>

    <x-yuktione.feedback />
    @vite('resources/js/yuktione-feedback.js')
    @vite('resources/js/professional-registration.js')
</body>
</html>

