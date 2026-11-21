<section class="yk-home-professional yk-home-professional--banner" aria-labelledby="yk-home-professional-title">
    <div class="yk-home-container">
        <div class="yk-home-professional__panel">
            <div class="yk-home-professional__media">
                <img
                    src="{{ asset('frontend/assets/img/professionals_photos/job-bussiness-career.jpg') }}"
                    alt="A professional ready to share expertise on YuktiOne"
                    loading="lazy"
                    decoding="async"
                >
            </div>
            <div class="yk-home-professional__copy">
                <p class="yk-home-professional__eyebrow">For professionals</p>
                <h2 id="yk-home-professional-title"><x-yuktione.two-tone text="Join as a Professional" /></h2>
                <p>Showcase your skills, reach more clients, and grow your practice on a trusted marketplace.</p>
                <ul class="yk-home-professional__points">
                    <li>Verified profile that builds trust</li>
                    <li>Easy booking and client management</li>
                    <li>Reach people actively looking for guidance</li>
                </ul>
                <a class="yk-home-button yk-home-button--primary" href="{{ route('professional.register') }}">
                    Join as a Professional
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </div>
</section>
