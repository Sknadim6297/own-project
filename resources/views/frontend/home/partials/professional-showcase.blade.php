{{-- "Join as a Professional" horizontal band — matches the approved mockup. --}}
<section class="yk-pro" aria-labelledby="yk-join-title">
    <div class="yk-pro__container">
        <div class="yk-pro__strip">
            <div class="yk-pro__media">
                <img
                    src="{{ asset('frontend/assets/img/professional-ribbon-banner.png') }}"
                    alt="A smiling professional working on a laptop"
                    width="1024"
                    height="341"
                    loading="lazy"
                    decoding="async"
                >
            </div>

            <div class="yk-pro__intro">
                <span class="yk-pro__icon" aria-hidden="true">
                    <svg viewBox="0 0 48 40" focusable="false"><circle cx="16" cy="12" r="5"/><circle cx="32" cy="12" r="5"/><path d="M6 33c0-6 4.5-9.5 10-9.5s10 3.5 10 9.5M22 33c0-6 4.5-9.5 10-9.5s10 3.5 10 9.5"/></svg>
                </span>
                <div>
                    <h2 class="yk-pro__title" id="yk-join-title">Join as <span>Professional</span></h2>
                    <p class="yk-pro__lead">Showcase your expertise, reach the right audience and grow your practice with YuktiOne.</p>
                </div>
            </div>

            <ul class="yk-pro__points">
                @foreach (['Create your professional profile', 'Get client bookings and visibility', 'Be part of a trusted expert network'] as $point)
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m5 12.5 4.3 4.3L19 7.5"/></svg>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>

            <div class="yk-pro__actions">
                <a class="yk-pro__cta" href="{{ route('professional.register') }}">
                    Join as a Professional
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
                </a>
                <a class="yk-pro__login" href="{{ route('professional.login') }}">Already a professional? <span>Log in</span></a>
            </div>
        </div>
    </div>
</section>
