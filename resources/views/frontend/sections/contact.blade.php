@extends('layouts.layout')

@section('title', 'Contact | YuktiOne')
@section('meta_description', 'Contact YuktiOne for support, questions, or a new opportunity.')

@push('head')
    <script>document.documentElement.classList.add('yk-js')</script>
@endpush

@section('content')
<x-yuktione.page
    class="yk-contact"
    title="Let's Start a Conversation"
    lead="Have a question, need support, or want to explore an opportunity? Our team is here to help."
    crumb="Contact"
>
    @if (session('success'))
        <p class="yk-doc__status" role="status" data-reveal>{{ session('success') }}</p>
    @endif

    <div class="yk-contact__cards">
        <article class="yk-contact__card" data-reveal style="--reveal-delay: 0ms">
            <span class="yk-contact__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            </span>
            <div>
                <h2>Email Us</h2>
                <a href="mailto:{{ config('yuktione.email') }}">{{ config('yuktione.email') }}</a>
            </div>
        </article>
        <article class="yk-contact__card" data-reveal style="--reveal-delay: 80ms">
            <span class="yk-contact__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.5-1.1a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z"/></svg>
            </span>
            <div>
                <h2>Call Us</h2>
                <a href="tel:{{ config('yuktione.phone_tel') }}">{{ config('yuktione.phone_display') }}</a>
                <p>{{ config('yuktione.hours') }}</p>
            </div>
        </article>
        <article class="yk-contact__card" data-reveal style="--reveal-delay: 160ms">
            <span class="yk-contact__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            </span>
            <div>
                <h2>Visit Us</h2>
                <p>A street address is not published yet. The map below shows Kolkata, not a confirmed office pin.</p>
            </div>
        </article>
    </div>

    <div class="yk-contact__layout">
        <form class="yk-contact__form" method="post" action="{{ route('contact.store') }}" data-reveal data-contact-form novalidate>
            @csrf
            <h2>Send a message</h2>
            <div class="yk-contact__field">
                <label for="name_contact"><span>Full name <abbr title="required">*</abbr></span></label>
                <input id="name_contact" name="name_contact" type="text" value="{{ old('name_contact') }}" autocomplete="name" maxlength="255" required aria-invalid="{{ $errors->has('name_contact') ? 'true' : 'false' }}" @if($errors->has('name_contact')) aria-describedby="name_contact-error" @endif>
                @error('name_contact')<p class="yk-contact__error" id="name_contact-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="yk-contact__field">
                <label for="email_contact"><span>Email address <abbr title="required">*</abbr></span></label>
                <input id="email_contact" name="email_contact" type="email" value="{{ old('email_contact', request('email')) }}" autocomplete="email" maxlength="255" required aria-invalid="{{ $errors->has('email_contact') ? 'true' : 'false' }}" @if($errors->has('email_contact')) aria-describedby="email_contact-error" @endif>
                @error('email_contact')<p class="yk-contact__error" id="email_contact-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="yk-contact__field">
                <label for="phone_contact"><span>Phone number</span></label>
                <input id="phone_contact" name="phone_contact" type="tel" value="{{ old('phone_contact') }}" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" aria-invalid="{{ $errors->has('phone_contact') ? 'true' : 'false' }}" @if($errors->has('phone_contact')) aria-describedby="phone_contact-error" @endif>
                <p class="yk-contact__hint" id="phone_contact-hint">Optional. Use 10 digits if you add it.</p>
                @error('phone_contact')<p class="yk-contact__error" id="phone_contact-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="yk-contact__field">
                <label for="subject_contact"><span>Subject</span></label>
                <input id="subject_contact" name="subject_contact" type="text" value="{{ old('subject_contact') }}" maxlength="255" aria-invalid="{{ $errors->has('subject_contact') ? 'true' : 'false' }}">
                @error('subject_contact')<p class="yk-contact__error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="yk-contact__field">
                <label for="message_contact"><span>Message <abbr title="required">*</abbr></span></label>
                <textarea id="message_contact" name="message_contact" maxlength="5000" required aria-invalid="{{ $errors->has('message_contact') ? 'true' : 'false' }}" @if($errors->has('message_contact')) aria-describedby="message_contact-error" @endif>{{ old('message_contact') }}</textarea>
                @error('message_contact')<p class="yk-contact__error" id="message_contact-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="yk-contact__field">
                <label for="verify_contact"><span>Verification <abbr title="required">*</abbr></span></label>
                <input id="verify_contact" name="verify_contact" type="text" value="{{ old('verify_contact') }}" inputmode="numeric" autocomplete="off" required aria-describedby="verify_contact-hint" aria-invalid="{{ $errors->has('verify_contact') ? 'true' : 'false' }}">
                <p class="yk-contact__hint" id="verify_contact-hint">What is 3 + 1?</p>
                @error('verify_contact')<p class="yk-contact__error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="yk-doc__button yk-contact__submit" id="contactSubmit" type="submit">Send Message</button>
        </form>

        <section class="yk-contact__location" aria-labelledby="contact-map" data-reveal style="--reveal-delay: 120ms">
            <h2 id="contact-map">Kolkata area</h2>
            <p class="yk-contact__hint">This map shows Kolkata. It is not pinned to a confirmed YuktiOne office.</p>
            <div class="yk-contact__map">
                <iframe title="Map of Kolkata" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d117925.2168964497!2d88.26495015954832!3d22.535564937570197!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39f882db4908f667%3A0x43e330e68f6c2cbc!2sKolkata%2C%20West%20Bengal!5e0!3m2!1sen!2sin!4v1729928348060!5m2!1sen!2sin" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </section>
    </div>
</x-yuktione.page>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const items = document.querySelectorAll('.yk-contact [data-reveal]');
    if (reduce || !('IntersectionObserver' in window)) {
        items.forEach(function (item) { item.classList.add('is-in'); });
    } else {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.16 });
        items.forEach(function (item) { observer.observe(item); });
    }

    const form = document.querySelector('[data-contact-form]');
    const button = document.getElementById('contactSubmit');
    form?.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Sending...';
    });
});
</script>
@endpush
