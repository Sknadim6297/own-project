@extends('customer.layout.layout')

@section('title', 'Edit Profile | Yuktione')

@section('content')
<div class="customer-profile-edit-page">
    <div class="customer-profile-edit-shell">
        <x-tazen-v2.panel.breadcrumbs :items="[
            ['label' => 'Dashboard', 'url' => route('user.dashboard')],
            ['label' => 'My Profile', 'url' => route('user.profile.index')],
            ['label' => 'Edit Profile'],
        ]" />

        <section class="edit-hero">
            <div class="hero-copy">
                <span class="hero-eyebrow">Edit Profile</span>
                <h1>Update Your Information</h1>
                <p>Keep your contact and profile details up to date so we can notify you about bookings and new offers without interruption.</p>
            </div>
            <div class="edit-hero-illustration" aria-hidden="true">
                <x-tazen-v2.panel.icon name="user" class="size-8" />
            </div>
        </section>

        <section class="profile-form-card">
            <form id="profileForm" enctype="multipart/form-data">
                @csrf

                <div class="form-section">
                    <header>
                        <h2>Profile Photo</h2>
                    </header>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="profile_image">Upload a new photo</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            @if($profile->customerProfile && $profile->customerProfile->profile_image)
                                <div class="avatar-preview">
                                    <img src="{{ asset('storage/'.$profile->customerProfile->profile_image) }}" alt="Current Photo">
                                    <span class="text-muted">Current photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <header>
                        <h2>Contact Details</h2>
                    </header>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $profile->name ?? 'N/A' ) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $profile->email) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', $profile->customerProfile ? $profile->customerProfile->phone : '') }}">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <header>
                        <h2>Address</h2>
                    </header>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="address">Street Address</label>
                            <textarea id="address" name="address" rows="3">{{ old('address', $profile->customerProfile ? $profile->customerProfile->address : '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input id="city" name="city" value="{{ old('city', $profile->customerProfile?->city) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input id="state" name="state" value="{{ old('state', $profile->customerProfile?->state) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="zip_code">Zip Code</label>
                            <input id="zip_code" name="zip_code" value="{{ old('zip_code', $profile->customerProfile?->zip_code) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <header>
                        <h2>Additional Notes</h2>
                    </header>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3">{{ old('notes', $profile->customerProfile ? $profile->customerProfile->notes : '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('user.profile.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Save Profile</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
@section('scripts')
<script>
$('#profileForm').submit(function(e) {
    e.preventDefault();
    let form = this;
    let formData = new FormData(form);
    formData.append('_method', 'PUT'); 

    $.ajax({
        url: "{{ route('user.profile.update', ['profile' => $profile->id]) }}", 
        method: "POST", 
        data: formData,
        contentType: false, 
        processData: false, 
        cache: false, 
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                toastr.success(response.message);
                form.reset();
                setTimeout(function() {
                    window.location.href = "{{ route('user.profile.index') }}";
                }, 1500);
            } else {
                toastr.error(response.message || "Something went wrong");
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    toastr.error(value[0]);
                });
            } else {
                toastr.error(xhr.responseJSON.message || "An unexpected error occurred");
            }
        }
    });
});
</script>
@endsection
