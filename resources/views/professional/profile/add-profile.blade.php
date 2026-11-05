@extends('professional.layout.layout')

@section('title', 'Edit Profile | YuktiOne')

@section('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
<style>
    /* Crop modal + aspect controls — Dom-independent panel styles; IDs/hooks unchanged */
    .photo-crop-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 70;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgb(20 43 77 / .55);
        overflow-y: auto;
    }
    .photo-crop-modal.is-open,
    .photo-crop-modal[style*="display: block"] {
        display: flex !important;
    }
    .crop-modal-content { margin: auto; }
    .crop-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border: 0;
        border-radius: 0.75rem;
        background: transparent;
        color: #475569;
        cursor: pointer;
    }
    .crop-close:hover { background: #F7FAFB; color: #142B4D; }
    .crop-controls { margin-bottom: 1rem; }
    .aspect-ratio-buttons { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
    .aspect-btn {
        min-height: 2.25rem;
        padding: .4rem .9rem;
        border: 1px solid #E2E9EE;
        border-radius: .75rem;
        background: #fff;
        color: #475569;
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
    }
    .aspect-btn.active,
    .aspect-btn:hover {
        border-color: #36877F;
        background: #EAF6F2;
        color: #245E59;
    }
    .crop-container { width: 100%; max-height: 360px; margin-bottom: 1rem; }
    .crop-container img { max-width: 100%; display: block; }
    .crop-preview { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: .5rem; }
    .preview-box { text-align: center; }
    .preview-box h6 { margin: 0 0 .5rem; font-size: .75rem; font-weight: 600; color: #475569; }
    .preview-circle,
    .preview-square {
        margin: 0 auto;
        overflow: hidden;
        border: 1px solid #E2E9EE;
        background: #F7FAFB;
    }
    .preview-circle { width: 80px; height: 80px; border-radius: 9999px; }
    .preview-square { width: 100px; height: 80px; border-radius: .75rem; }
    .crop-buttons { display: flex; flex-wrap: wrap; gap: .75rem; }
    .btn-crop {
        display: inline-flex;
        min-height: 2.5rem;
        align-items: center;
        gap: .4rem;
        padding: .5rem 1rem;
        border: 0;
        border-radius: .75rem;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-crop-cancel { background: #fff; border: 1px solid #E2E9EE; color: #475569; }
    .btn-crop-cancel:hover { background: #F7FAFB; }
    .btn-crop-save { background: #36877F; color: #fff; }
    .btn-crop-save:hover { background: #245E59; }
    .form-section-title {
        margin: 0 0 .25rem;
        font-size: .95rem;
        font-weight: 600;
        color: #142B4D;
    }
</style>
@endsection

@section('content')
<div class="profile-edit-page mx-auto w-full max-w-7xl space-y-5 text-text-primary">
    <div class="profile-edit-shell mx-auto w-full space-y-5">
        <section class="profile-hero flex flex-col gap-4 rounded-panel border border-border bg-gradient-to-br from-white to-brand-50 p-5 shadow-card sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="profile-hero__meta min-w-0">
                <nav aria-label="Breadcrumb" class="mb-2 text-xs font-medium text-text-muted">
                    <ol class="profile-hero__breadcrumb flex flex-wrap items-center gap-2">
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.dashboard') }}">Dashboard</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li><a class="text-brand-primary hover:text-brand-800" href="{{ route('professional.profile.index') }}">Profile</a></li>
                        <li aria-hidden="true" class="text-border">/</li>
                        <li class="active text-text-secondary" aria-current="page">Edit Profile</li>
                    </ol>
                </nav>
                <span class="profile-hero__eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><i class="fas fa-user-cog" aria-hidden="true"></i>Profile Management</span>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand-950 sm:text-[1.75rem]">Edit Your Professional Profile</h1>
                <p class="mt-1 text-sm text-text-secondary">Keep your personal, professional, and banking information up to date so clients always see your best self.</p>
            </div>
            <div class="profile-hero__actions flex flex-wrap gap-2">
                <a href="{{ route('professional.profile.index') }}" class="btn-pill btn-pill--outline inline-flex min-h-10 items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-semibold text-text-secondary transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Back to Overview
                </a>
                <a href="{{ route('professional.events.create') }}" class="btn-pill btn-pill--primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                    <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                    Create Event
                </a>
            </div>
        </section>

        <section class="form-card add-profile-form fade-in rounded-panel border border-border bg-white p-5 shadow-card sm:p-7">
            <header class="form-card__head flex flex-col gap-4 border-b border-divider pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-brand-950"><i class="fas fa-file-signature text-brand-primary" aria-hidden="true"></i>Complete Your Profile</h2>
                    <p class="mt-1 text-sm text-text-secondary">All the details you provide help us match you with the right clients and opportunities.</p>
                </div>
                <span class="status-pill inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-2 text-xs font-medium text-brand-800">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    Updated {{ $profile->updated_at ? $profile->updated_at->diffForHumans() : 'just now' }}
                </span>
            </header>
            <div class="form-card__body">
                <form id="profileForm">
                    @csrf
                    <div class="form-section space-y-5 border-b border-divider pb-6 last:border-0 last:pb-0">
                        <h4 class="form-section-title border-b border-divider pb-2">Profile Information</h4>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="photo" class="block text-sm font-medium text-text-secondary">Profile Photo</label>
                            <div class="crop-info mb-4 rounded-control border border-info/20 bg-info-surface p-4 text-sm text-info">
                                <i class="fas fa-info-circle"></i>
                                Upload a high-quality photo that will be perfectly cropped for all your profile displays. Click the edit button after upload to crop your image.
                            </div>
                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none" class="block w-full cursor-pointer rounded-control border border-border bg-white text-sm text-text-secondary file:mr-4 file:rounded-control file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                            <input type="hidden" id="cropped_photo" name="cropped_photo">
                            
                            <div class="photo-preview-container flex flex-col items-start gap-5 rounded-control border border-divider bg-surface-muted p-4 sm:flex-row sm:items-center">
                                @if($profile->photo)
                                    <div class="current-photo relative h-32 w-32 shrink-0 overflow-hidden rounded-full border-4 border-white shadow-card">
                                        <img src="{{ file_exists(public_path('storage/'.$profile->photo)) ? asset('storage/'.$profile->photo) : asset('img/default-avatar.jpg') }}" alt="Current Photo" class="photo-preview h-full w-full object-cover" id="photoPreview">
                                        <button type="button" class="change-photo-btn absolute bottom-1 right-1 inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-white shadow-card hover:bg-brand-800" id="changePhotoBtn" title="Change Photo">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="current-photo relative h-32 w-32 shrink-0 overflow-hidden rounded-full border-4 border-white shadow-card">
                                        <img src="{{ asset('images/default-avatar.png') }}" alt="Default Photo" class="photo-preview h-full w-full object-cover" id="photoPreview">
                                        <button type="button" class="change-photo-btn absolute bottom-1 right-1 inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-white shadow-card hover:bg-brand-800" id="changePhotoBtn" title="Add Photo">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                @endif
                                <div class="photo-instructions text-sm text-text-secondary">
                                    <h6 class="text-sm font-semibold text-brand-950">Photo Requirements</h6>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-5 text-text-muted">
                                        <li>High resolution (minimum 400x400px)</li>
                                        <li>Clear, professional headshot</li>
                                        <li>Good lighting and focus</li>
                                        <li>JPG, PNG formats supported</li>
                                        <li>Maximum file size: 5MB</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="gallery" class="block text-sm font-medium text-text-secondary">Gallery Images (Multiple)</label>
                            <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple class="block w-full cursor-pointer rounded-control border border-border bg-white text-sm text-text-secondary file:mr-4 file:rounded-control file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                            <div class="file-input-wrapper">
                                @if($profile->gallery)
                                    <div class="image-preview flex flex-wrap gap-3">
                                        @php
                                            $gallery = is_array($profile->gallery) ? $profile->gallery : json_decode($profile->gallery, true);
                                            $gallery = is_array($gallery) ? $gallery : [];
                                        @endphp
                                        @if(!empty($gallery))
                                            @foreach($gallery as $index => $img)
                                                <div class="gallery-image-container relative h-24 w-24 overflow-hidden rounded-control border border-border bg-surface-muted" id="gallery-image-{{ $index }}">
                                                    <button type="button" class="delete-image-btn absolute right-1 top-1 inline-flex h-7 w-7 items-center justify-center rounded-full bg-error text-white shadow-sm hover:bg-red-800" data-path="{{ $img }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <img src="{{ asset('storage/'.$img) }}" alt="Gallery Image" width="80">
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <input type="hidden" name="deleted_images" id="deleted_images" value="">
                        </div>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-text-secondary">Name <span class="text-error">*</span></label>
                                <input type="text" id="name" name="name" value="{{ old('name', $profile->professional->name ?? 'N/A' ) }}" required class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <div class="form-control-feedback hidden text-xs text-error"></div>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-text-secondary">Email Address <span class="text-error">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $profile->email) }}" required class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <div class="form-control-feedback hidden text-xs text-error"></div>
                            </div>
                        </div>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-text-secondary">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone', $profile->phone) }}" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            </div>
                            <div>
                                <label for="specialization" class="block text-sm font-medium text-text-secondary">Specialization</label>
                                <input type="text" id="specialization" name="specialization" value="{{ old('specialization', $profile->specialization) }}" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            </div>
                        </div>
                    </div>

                    <div class="form-section space-y-5 border-b border-divider pb-6 last:border-0 last:pb-0">
                        <h4 class="form-section-title border-b border-divider pb-2">Professional Details</h4>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="experience" class="block text-sm font-medium text-text-secondary">Years of Experience</label>
                                <input type="text" id="experience" name="experience" value="{{ old('experience', $profile->experience) }}" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            </div>
                            <div>
                                <label for="startingPrice" class="block text-sm font-medium text-text-secondary">Starting Price (e.g., 5000 or 5000-8000)</label>
                                <input type="text" id="startingPrice" name="startingPrice" value="{{ old('startingPrice', $profile->starting_price) }}" placeholder="Price per session (e.g., 1000, 1000-2000, 1500)" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            </div>
                        </div>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-text-secondary">Address</label>
                            <textarea id="address" name="address" rows="3" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('address', $profile->address) }}</textarea>
                        </div>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="education" class="block text-sm font-medium text-text-secondary">Education Details</label>
                            <textarea id="education" name="education" rows="3" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('education', $profile->education) }}</textarea>
                        </div>
                    </div>

                    <div class="form-section space-y-5 border-b border-divider pb-6 last:border-0 last:pb-0">
                        <h4 class="form-section-title border-b border-divider pb-2">GST Information (Optional)</h4>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="gst_number" class="block text-sm font-medium text-text-secondary">GST Number</label>
                                <input type="text" id="gst_number" name="gst_number" value="{{ old('gst_number', $profile->gst_number) }}" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">Optional: Enter your GST number if applicable</small>
                            </div>
                            <div class="gst-certificate-group" style="display: none">
                                <label for="gst_certificate" class="block text-sm font-medium text-text-secondary">GST Certificate</label>
                                <input type="file" id="gst_certificate" name="gst_certificate" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-control border border-border bg-white text-sm text-text-secondary file:mr-4 file:rounded-control file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                                <div class="file-input-wrapper">
                                    @if($profile->gst_certificate)
                                        <a href="{{ asset('storage/'.$profile->gst_certificate) }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-semibold text-brand-primary hover:text-brand-800">View GST Certificate</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="gst_address" class="block text-sm font-medium text-text-secondary">GST Address</label>
                            <textarea id="gst_address" name="gst_address" rows="3" placeholder="GST Address (Optional)" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('gst_address', $profile->gst_address) }}</textarea>
                            <small class="text-muted text-text-muted">Optional: Enter your GST registered address</small>
                        </div>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="state_code" class="block text-sm font-medium text-text-secondary">State Code</label>
                                <select id="state_code" name="state_code" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Select State (Optional)</option>
                                    @php
                                        $selectedStateCode = old('state_code', $profile->state_code ?? '');
                                    @endphp
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
                                <small class="text-muted text-text-muted">Select your state for GST registration (if applicable)</small>
                            </div>
                            <div>
                                <label for="state_name" class="block text-sm font-medium text-text-secondary">State Name</label>
                                <input type="text" id="state_name" name="state_name" value="{{ old('state_name', $profile->state_name) }}" readonly placeholder="Auto-filled based on state code" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">This field is auto-filled when you select a state code</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-section space-y-5 border-b border-divider pb-6 last:border-0 last:pb-0">
                        <h4 class="form-section-title border-b border-divider pb-2">Bank Account Details</h4>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="account_holder_name" class="block text-sm font-medium text-text-secondary">Account Holder Name</label>
                                <input type="text" id="account_holder_name" name="account_holder_name" value="{{ old('account_holder_name', $profile->account_holder_name) }}" placeholder="Full name as per bank records" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">Enter name exactly as it appears in your bank account</small>
                            </div>
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-text-secondary">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}" placeholder="e.g., State Bank of India" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">Full name of your bank</small>
                            </div>
                        </div>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="account_number" class="block text-sm font-medium text-text-secondary">Account Number</label>
                                <input type="text" id="account_number" name="account_number" value="{{ old('account_number', $profile->account_number) }}" placeholder="Bank account number" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">Your bank account number</small>
                            </div>
                            <div>
                                <label for="ifsc_code" class="block text-sm font-medium text-text-secondary">IFSC Code</label>
                                <input type="text" id="ifsc_code" name="ifsc_code" value="{{ old('ifsc_code', $profile->ifsc_code) }}" placeholder="e.g., SBIN0000123" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">11-character IFSC code of your bank branch</small>
                            </div>
                        </div>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="account_type" class="block text-sm font-medium text-text-secondary">Account Type</label>
                                <select id="account_type" name="account_type" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Select Account Type</option>
                                    <option value="savings" {{ old('account_type', $profile->account_type) == 'savings' ? 'selected' : '' }}>Savings Account</option>
                                    <option value="current" {{ old('account_type', $profile->account_type) == 'current' ? 'selected' : '' }}>Current Account</option>
                                </select>
                                <small class="text-muted text-text-muted">Type of your bank account</small>
                            </div>
                            <div>
                                <label for="bank_branch" class="block text-sm font-medium text-text-secondary">Branch Name</label>
                                <input type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $profile->bank_branch) }}" placeholder="Branch name/location" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <small class="text-muted text-text-muted">Name or location of your bank branch (optional)</small>
                            </div>
                        </div>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="bank_document" class="block text-sm font-medium text-text-secondary">Bank Account Proof (Cancelled Cheque/Passbook)</label>
                            <input type="file" id="bank_document" name="bank_document" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-control border border-border bg-white text-sm text-text-secondary file:mr-4 file:rounded-control file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                            <div class="file-input-wrapper">
                                @if($profile->bank_document)
                                    <a href="{{ asset('storage/'.$profile->bank_document) }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-semibold text-brand-primary hover:text-brand-800">View Bank Document</a>
                                @endif
                            </div>
                            <small class="text-muted text-text-muted">Upload cancelled cheque or passbook first page for verification</small>
                        </div>
                    </div>

                    <div class="form-section space-y-5 border-b border-divider pb-6 last:border-0 last:pb-0">
                        <h4 class="form-section-title border-b border-divider pb-2">Documents</h4>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="qualificationDocument" class="block text-sm font-medium text-text-secondary">Qualification Document</label>
                            <input type="file" id="qualificationDocument" name="qualificationDocument" accept=".pdf,.doc,.docx,image/*" class="block w-full cursor-pointer rounded-control border border-border bg-white text-sm text-text-secondary file:mr-4 file:rounded-control file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                            <div class="file-input-wrapper">
                                @if($profile->qualification_document)
                                    <a href="{{ asset('storage/'.$profile->qualification_document) }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-semibold text-brand-primary hover:text-brand-800">View Document</a>
                                @endif
                            </div>
                        </div>
                        <div class="form-group col-2 mb-5 space-y-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="idProofDocument" class="block text-sm font-medium text-text-secondary">ID Proof Document (Aadhaar / PAN Card)</label>
                                <input type="file" id="idProofDocument" name="idProofDocument" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-control border border-border bg-white text-sm text-text-secondary file:mr-4 file:rounded-control file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                                <div class="file-input-wrapper">
                                    @if($profile->id_proof_document)
                                        <a href="{{ asset('storage/'.$profile->id_proof_document) }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-semibold text-brand-primary hover:text-brand-800">View ID Proof</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section space-y-5 border-b border-divider pb-6 last:border-0 last:pb-0">
                        <h4 class="form-section-title border-b border-divider pb-2">Additional Information</h4>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="comments" class="block text-sm font-medium text-text-secondary">Additional Comments</label>
                            <textarea id="comments" name="comments" rows="3" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('comments', $profile->comments) }}</textarea>
                        </div>
                        <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                            <label for="bio" class="block text-sm font-medium text-text-secondary">Bio</label>
                            <textarea id="bio" name="bio" rows="4" class="w-full rounded-control border border-border bg-white px-3 py-2.5 text-sm text-text-primary shadow-sm placeholder:text-text-muted focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('bio', $profile->bio) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group col-full mb-5 space-y-2 md:col-span-2">
                        <button type="submit" id="submitBtn" class="btn btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-control bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                            <i class="fas fa-save" aria-hidden="true"></i>
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<!-- Photo Crop Modal -->
<div id="photoCropModal" class="photo-crop-modal">
    <div class="crop-modal-content mx-auto w-full max-w-3xl overflow-hidden rounded-panel border border-border bg-white shadow-popover">
        <div class="crop-modal-header flex items-center justify-between border-b border-divider p-5">
            <h4 class="text-base font-semibold text-brand-950"><i class="fas fa-crop-alt text-brand-primary" aria-hidden="true"></i> Crop Your Profile Photo</h4>
            <button type="button" class="crop-close" id="closeCropModal" aria-label="Close crop modal">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="crop-modal-body max-h-[70vh] overflow-auto p-5">
            <div class="crop-info mb-4 rounded-control border border-info/20 bg-info-surface p-4 text-sm text-info">
                <i class="fas fa-info-circle"></i>
                Crop your photo to ensure it looks perfect across all platforms. Choose an aspect ratio and adjust the crop area.
            </div>
            
            <div class="crop-controls">
                <div>
                    <label class="block text-sm font-medium text-text-secondary">Aspect Ratio:</label>
                    <div class="aspect-ratio-buttons">
                        <button type="button" class="aspect-btn" data-ratio="1" title="Square (1:1) - Perfect for profile pictures">
                            <i class="fas fa-square"></i> Square
                        </button>
                        <button type="button" class="aspect-btn" data-ratio="4/3" title="Landscape (4:3) - Good for headers">
                            <i class="fas fa-rectangle-landscape"></i> 4:3
                        </button>
                        <button type="button" class="aspect-btn" data-ratio="16/9" title="Widescreen (16:9) - For banners">
                            <i class="fas fa-rectangle-wide"></i> 16:9
                        </button>
                        <button type="button" class="aspect-btn active" data-ratio="NaN" title="Free crop - Any size">
                            <i class="fas fa-crop"></i> Free
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="crop-container">
                <img id="cropImage" src="" alt="Image to crop">
            </div>
            
            <div class="crop-preview">
                <div class="preview-box">
                    <h6>Dashboard Preview</h6>
                    <div class="preview-circle" id="previewCircle"></div>
                </div>
                <div class="preview-box">
                    <h6>Listing Preview</h6>
                    <div class="preview-square" id="previewSquare"></div>
                </div>
            </div>
        </div>
        <div class="crop-modal-footer flex justify-end gap-3 border-t border-divider p-5">
            <div class="crop-info mb-4 rounded-control border border-info/20 bg-info-surface p-4 text-sm text-info">
                <i class="fas fa-mouse"></i> Drag to move • <i class="fas fa-expand-arrows-alt"></i> Drag corners to resize
            </div>
            <div class="crop-buttons">
                <button type="button" class="btn-crop btn-crop-cancel" id="cancelCrop">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-crop btn-crop-save" id="saveCrop">
                    <i class="fas fa-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</div>


@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script>
// Photo Cropping Functionality
let cropper = null;
let currentFile = null;

// Array to store deleted image paths
const deletedImages = [];

// Photo Cropping Functions
function initializeCropper(imageFile) {
    const modal = document.getElementById('photoCropModal');
    const cropImage = document.getElementById('cropImage');
    
    // Create object URL for the selected image
    const imageUrl = URL.createObjectURL(imageFile);
    cropImage.src = imageUrl;
    
    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Initialize cropper after image loads
    cropImage.onload = function() {
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(cropImage, {
            aspectRatio: NaN, // Free crop by default
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            responsive: true,
            modal: true,
            background: true,
            ready: function () {
                updatePreviews();
            },
            cropend: function () {
                updatePreviews();
            }
        });
    };
}

function updatePreviews() {
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        width: 200,
        height: 200,
        imageSmoothingQuality: 'high'
    });
    
    const circleCanvas = cropper.getCroppedCanvas({
        width: 80,
        height: 80,
        imageSmoothingQuality: 'high'
    });
    
    const squareCanvas = cropper.getCroppedCanvas({
        width: 100,
        height: 80,
        imageSmoothingQuality: 'high'
    });
    
    if (canvas && circleCanvas && squareCanvas) {
        const previewCircle = document.getElementById('previewCircle');
        const previewSquare = document.getElementById('previewSquare');
        
        // Clear previous previews
        previewCircle.innerHTML = '';
        previewSquare.innerHTML = '';
        
        // Add new previews
        const circleImg = circleCanvas.toDataURL();
        const squareImg = squareCanvas.toDataURL();
        
        previewCircle.style.backgroundImage = `url(${circleImg})`;
        previewCircle.style.backgroundSize = 'cover';
        previewCircle.style.backgroundPosition = 'center';
        
        previewSquare.style.backgroundImage = `url(${squareImg})`;
        previewSquare.style.backgroundSize = 'cover';
        previewSquare.style.backgroundPosition = 'center';
    }
}

function closeCropModal() {
    const modal = document.getElementById('photoCropModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    
    // Reset file input
    const photoInput = document.getElementById('photo');
    photoInput.value = '';
    currentFile = null;
}

function saveCroppedImage() {
    if (!cropper) return;
    
    // Get cropped canvas with high quality
    const canvas = cropper.getCroppedCanvas({
        width: 800,
        height: 800,
        imageSmoothingQuality: 'high',
        fillColor: '#fff'
    });
    
    if (canvas) {
        // Convert to blob
        canvas.toBlob(function(blob) {
            // Create a new file from the blob
            const fileName = currentFile.name.replace(/\.[^/.]+$/, '') + '_cropped.jpg';
            const croppedFile = new File([blob], fileName, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            
            // Update preview
            const photoPreview = document.getElementById('photoPreview');
            const previewUrl = URL.createObjectURL(blob);
            photoPreview.src = previewUrl;
            
            // Store the cropped image data
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('cropped_photo').value = e.target.result;
            };
            reader.readAsDataURL(blob);
            
            // Close modal
            closeCropModal();
            
            // Show success message
            showToast('Photo cropped successfully!', 'success');
            
        }, 'image/jpeg', 0.9);
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Change photo button
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const photoInput = document.getElementById('photo');
    
    changePhotoBtn.addEventListener('click', function() {
        photoInput.click();
    });
    
    // Photo input change
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showToast('File size must be less than 5MB', 'error');
                this.value = '';
                return;
            }
            
            currentFile = file;
            initializeCropper(file);
        } else if (file) {
            showToast('Please select a valid image file', 'error');
            this.value = '';
        }
    });
    
    // Aspect ratio buttons
    document.querySelectorAll('.aspect-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.aspect-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update cropper aspect ratio
            if (cropper) {
                const ratio = parseFloat(this.dataset.ratio);
                cropper.setAspectRatio(ratio);
                setTimeout(updatePreviews, 100);
            }
        });
    });
    
    // Close modal buttons
    document.getElementById('closeCropModal').addEventListener('click', closeCropModal);
    document.getElementById('cancelCrop').addEventListener('click', closeCropModal);
    
    // Save crop button
    document.getElementById('saveCrop').addEventListener('click', saveCroppedImage);
    
    // Close modal when clicking outside
    document.getElementById('photoCropModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCropModal();
        }
    });
});

// Toast notification function
function showToast(message, type = 'info') {
    const showNotification = window.YuktioneToast?.[type];
    if (typeof showNotification !== 'function') {
        console.error(`Professional profile notification type "${type}" is unavailable.`);
        return;
    }

    showNotification(message);
}

// Handle delete button click
$(document).on('click', '.delete-image-btn', function() {
    const imagePath = $(this).data('path');
    const container = $(this).closest('.gallery-image-container');
    
    // Add confirmation dialog
    if (confirm('Are you sure you want to delete this image?')) {
        // Add fade-out animation
        container.addClass('fade-out');
        
        // Add the image path to the deleted images array
        deletedImages.push(imagePath);
        
        // Update the hidden input with deleted images
        $('#deleted_images').val(JSON.stringify(deletedImages));
        
        // Remove the element after animation
        setTimeout(() => {
            container.remove();
        }, 300);
    }
});

// Handle form submission
$('#profileForm').submit(function(e) {
    e.preventDefault();
    let form = this;
    let formData = new FormData(form);
    formData.append('_method', 'PUT');
    
    // Add the deleted images to formData if any
    if (deletedImages.length > 0) {
        formData.set('deleted_images', JSON.stringify(deletedImages));
    }
    
    // Add loading state
    const submitButton = document.getElementById('submitBtn');
    submitButton.disabled = true;
    $('#submitBtn').addClass('btn-loading').html('');
    
    // Reset validation state
    $('.is-invalid').removeClass('is-invalid');
    $('.form-control-feedback').text('');

    $.ajax({
        url: "{{ route('professional.profile.update', ['profile' => $profile->id]) }}", 
        method: "POST", 
        data: formData,
        contentType: false, 
        processData: false, 
        cache: false, 
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log(response);
            
            if (response.status === 'success') {
                toastr.success(response.message);
                
                // Show success animation
                $('.add-profile-form').addClass('fade-in');
                
                setTimeout(function() {
                    window.location.href = "{{ route('professional.profile.index') }}";
                }, 1500);
            } else {
                toastr.error(response.message || "Something went wrong");
                submitButton.disabled = false;
                $('#submitBtn').removeClass('btn-loading').html('<i class="fas fa-save"></i> Save Profile');
            }
        },
        error: function(xhr) {
            submitButton.disabled = false;
            $('#submitBtn').removeClass('btn-loading').html('<i class="fas fa-save"></i> Save Profile');
            
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    toastr.error(value[0]);
                    
                    // Add validation styling
                    $('#' + key).addClass('is-invalid');
                    $('#' + key).next('.form-control-feedback').text(value[0]);
                });
            } else {
                toastr.error(xhr.responseJSON?.message || "An unexpected error occurred");
            }
        }
    });
});

// Update file input preview functionality for gallery images
$('#gallery').change(function(e) {
    const fileInput = $(this);
    let previewContainer = fileInput.next('.file-input-wrapper').find('.image-preview');
    
    if (!previewContainer.length) {
        fileInput.next('.file-input-wrapper').append('<div class="image-preview flex flex-wrap gap-3"></div>');
        previewContainer = fileInput.next('.file-input-wrapper').find('.image-preview');
    }
    
    if (this.files && this.files.length > 0) {
        // For each file, create a preview with delete button
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            
            if (file.type.match('image.*')) {
                const reader = new FileReader();
                const uniqueId = 'new-img-' + Date.now() + '-' + i;
                
                reader.onload = function(e) {
                    const newImage = `
                        <div class="new-image-preview relative h-24 w-24 overflow-hidden rounded-control border border-border bg-surface-muted" id="${uniqueId}">
                            <button type="button" class="delete-image-btn absolute right-1 top-1 inline-flex h-7 w-7 items-center justify-center rounded-full bg-error text-white shadow-sm hover:bg-red-800" data-id="${uniqueId}">
                                <i class="fas fa-times"></i>
                            </button>
                            <img src="${e.target.result}" alt="New image" width="80">
                        </div>
                    `;
                    previewContainer.append(newImage);
                };
                
                reader.readAsDataURL(file);
            }
        }
    }
});

// Handle delete button for newly uploaded images
$(document).on('click', '.new-image-preview .delete-image-btn', function() {
    const containerId = $(this).data('id');
    const $container = $('#' + containerId);
    
    $container.addClass('fade-out');
    setTimeout(() => {
        $container.remove();
    }, 300);
});

// Rest of your existing JS code...

// Add form field validation on blur
$('input, textarea').blur(function() {
    const input = $(this);
    
    if (input.attr('required') && !input.val().trim()) {
        input.addClass('is-invalid');
        input.next('.form-control-feedback').text('This field is required');
    } else {
        input.removeClass('is-invalid');
        
        // Special validation for starting price
        if (input.attr('name') === 'startingPrice' && input.val().trim()) {
            const startingPrice = input.val().trim();
            const pricePattern = /^(\d+(\.\d{1,2})?(-\d+(\.\d{1,2})?)?)$/;
            
            if (!pricePattern.test(startingPrice)) {
                input.addClass('is-invalid');
                input.next('.form-control-feedback').text('Please enter a valid price (e.g., 1000 or 1000-2000)');
                return;
            }
        }
        
        if (input.val().trim()) {
            input.addClass('is-valid');
        } else {
            input.removeClass('is-valid');
        }
    }
});

// Add smooth scroll to sections
$('.form-section-title').click(function() {
    $(this).next().slideToggle(300);
});

// Add entrance animations
$(document).ready(function() {
    $('.form-section').each(function(index) {
        $(this).css({
            'animation-delay': (index * 0.1) + 's',
            'animation': 'fadeIn 0.5s ease forwards'
        });
    });
    
    // GST Number and Certificate Handler
    $('#gst_number').on('input', function() {
        var gstNumber = $(this).val().trim();
        if (gstNumber) {
            $('.gst-certificate-group').slideDown();
            $('#gst_certificate').attr('required', false); // Optional unless GST number is provided
        } else {
            $('.gst-certificate-group').slideUp();
            $('#gst_certificate').attr('required', false);
        }
    });
});
</script>
@endsection