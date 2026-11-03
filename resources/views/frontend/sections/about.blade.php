@extends('layouts.layout')

@section('title', 'About Us | YuktiOne')
@section('meta_description', 'YuktiOne connects individuals and businesses with professionals across career, wellness, lifestyle, and other specialized services.')

@push('head')
    <script>document.documentElement.classList.add('yk-js')</script>
@endpush

@section('content')
<main class="yk-doc yk-about">
    <header class="yk-about__hero">
        <div class="yk-doc__wrap yk-about__hero-grid">
            <div data-reveal>
                <nav class="yk-doc__crumb" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span aria-hidden="true">›</span>
                    <span aria-current="page">About Us</span>
                </nav>
                <h1>Empowering Growth Through Expert Guidance</h1>
                <p class="yk-doc__lead">YuktiOne connects individuals and businesses with professionals across career, wellness, lifestyle, and other specialized services.</p>
                <div class="yk-doc__actions">
                    <a class="yk-doc__button" href="{{ route('professionals') }}">Explore Experts</a>
                </div>
            </div>
            @if ($aboutImage)
                <figure class="yk-about__photo" data-reveal style="--reveal-delay: 120ms">
                    <img src="{{ $aboutImage }}" alt="People gathered around a table" width="720" height="840">
                </figure>
            @endif
        </div>
    </header>

    <section class="yk-about__section" aria-labelledby="about-why">
        <div class="yk-doc__wrap">
            <header class="yk-about__intro" data-reveal>
                <h2 id="about-why">Why Choose YuktiOne?</h2>
                <p>A clearer way to find a professional, see what they offer, and book a time that is actually open.</p>
            </header>
            <div class="yk-about__cards">
                <article class="yk-about__card" data-reveal style="--reveal-delay: 0ms">
                    <span class="yk-about__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="3"/><path d="M19 8v6M22 11h-6"/></svg></span>
                    <h3>Trusted Professionals</h3>
                    <p>Profiles on Explore are professionals who have been accepted and listed on YuktiOne. Open a profile to read what that person offers.</p>
                </article>
                <article class="yk-about__card" data-reveal style="--reveal-delay: 70ms">
                    <span class="yk-about__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <h3>Transparent Pricing</h3>
                    <p>When a professional publishes a rate, it is shown on their profile and used for that booking. You can review it before you pay.</p>
                </article>
                <article class="yk-about__card" data-reveal style="--reveal-delay: 140ms">
                    <span class="yk-about__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3a7 7 0 0 0-4 12.7V19a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-3.3A7 7 0 0 0 12 3Z"/><path d="M9 22h6"/></svg></span>
                    <h3>Personalized Guidance</h3>
                    <p>You choose the professional and the consultation plan shown on their profile, rather than joining a generic group session.</p>
                </article>
                <article class="yk-about__card" data-reveal style="--reveal-delay: 210ms">
                    <span class="yk-about__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>
                    <h3>Convenient Consultations</h3>
                    <p>Booking uses the times that professional has opened. Sign in, pick an available slot, and finish checkout from the same page.</p>
                </article>
                <article class="yk-about__card" data-reveal style="--reveal-delay: 280ms">
                    <span class="yk-about__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="6" cy="7" r="2.5"/><circle cx="17" cy="8" r="2"/><circle cx="9" cy="17" r="2.5"/><path d="M8 9.2 15.2 9.6M7.2 14.8l1 0M14 9.8 10.2 15"/></svg></span>
                    <h3>Diverse Expertise</h3>
                    <p>Active services cover the categories published on this page, including career, wellness, lifestyle, and other specialist work.</p>
                </article>
                <article class="yk-about__card" data-reveal style="--reveal-delay: 350ms">
                    <span class="yk-about__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H7l-4 3V12a8 8 0 1 1 18 0Z"/></svg></span>
                    <h3>Ongoing Support</h3>
                    <p>After a booking is confirmed, it stays in My Bookings. From your account you can review it, reschedule an upcoming appointment, and message about that booking.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="yk-about__section" aria-labelledby="about-story">
        <div class="yk-doc__wrap yk-about__split">
            @if ($storyImage)
                <figure class="yk-about__photo" data-reveal>
                    <img src="{{ $storyImage }}" alt="People working together in a meeting room" width="800" height="640">
                </figure>
            @endif
            <div data-reveal style="--reveal-delay: 100ms">
                <h2 id="about-story">About YuktiOne</h2>
                <p>YuktiOne is a place to find a professional for a specific question, then book time with that person. The aim is practical guidance that is easier to reach than hunting for separate specialists on your own.</p>
                <p>The platform is built around choice and access: browse the services that are currently published, open a profile, and book an available consultation when you are ready.</p>
                <ul class="yk-about__points">
                    <li>Browse professionals by the service you need.</li>
                    <li>Read the profile and published rate before you book.</li>
                    <li>Keep confirmed appointments in your account.</li>
                </ul>
                <div class="yk-about__actions">
                    <a class="yk-doc__button" href="{{ route('professionals') }}">Explore Experts</a>
                    <a class="yk-doc__button-secondary" href="{{ route('register') }}">Create an account</a>
                </div>
            </div>
        </div>
    </section>

    <section class="yk-about__section" aria-labelledby="about-highlights">
        <div class="yk-doc__wrap">
            <header class="yk-about__intro" data-reveal>
                <h2 id="about-highlights">On the platform today</h2>
                <p>These figures come from the profiles and services currently published. They are not satisfaction scores.</p>
            </header>
            <div class="yk-about__stats">
                @if ($listedProfessionals > 0)
                    <article class="yk-about__stat" data-reveal>
                        <strong>{{ number_format($listedProfessionals) }}</strong>
                        <h3>Professionals listed</h3>
                        <p>Accepted profiles that are active on Explore.</p>
                    </article>
                @endif
                @if ($services->isNotEmpty())
                    <article class="yk-about__stat" data-reveal style="--reveal-delay: 70ms">
                        <strong>{{ $services->count() }}</strong>
                        <h3>Active services</h3>
                        <p>Service categories you can open right now.</p>
                    </article>
                @endif
                <article class="yk-about__stat" data-reveal style="--reveal-delay: 140ms">
                    <strong>Online</strong>
                    <h3>Book an open time</h3>
                    <p>Choose a slot from the calendar on a professional's page.</p>
                </article>
                <article class="yk-about__stat" data-reveal style="--reveal-delay: 210ms">
                    <strong>Account</strong>
                    <h3>Bookings stay with you</h3>
                    <p>Signed-in customers can review appointments in My Bookings.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="yk-about__section" aria-labelledby="about-steps">
        <div class="yk-doc__wrap">
            <header class="yk-about__intro" data-reveal>
                <h2 id="about-steps">How YuktiOne Works</h2>
                <p>The path from looking around to a confirmed consultation.</p>
            </header>
            <ol class="yk-about__steps">
                <li class="yk-about__step" data-reveal>
                    <span>01</span>
                    <h3>Discover Your Needs</h3>
                    <p>Start from Explore or open one of the active services to see the kind of guidance you want.</p>
                </li>
                <li class="yk-about__step" data-reveal style="--reveal-delay: 70ms">
                    <span>02</span>
                    <h3>Explore Professionals</h3>
                    <p>Open a profile to read the professional's details, services, and any rate they have published.</p>
                </li>
                <li class="yk-about__step" data-reveal style="--reveal-delay: 140ms">
                    <span>03</span>
                    <h3>Book a Consultation</h3>
                    <p>Sign in, choose an available date and time, and complete checkout. The booking is confirmed when payment succeeds.</p>
                </li>
                <li class="yk-about__step" data-reveal style="--reveal-delay: 210ms">
                    <span>04</span>
                    <h3>Get Personalized Guidance</h3>
                    <p>The confirmed appointment is saved to My Bookings, where you can follow it and message about that booking.</p>
                </li>
            </ol>
        </div>
    </section>

    @if ($services->isNotEmpty())
        <section class="yk-about__section" aria-labelledby="about-expertise">
            <div class="yk-doc__wrap">
                <header class="yk-about__intro" data-reveal>
                    <h2 id="about-expertise">Our Expertise</h2>
                    <p>Services that are active on YuktiOne now.</p>
                </header>
                <div class="yk-about__services">
                    @foreach ($services as $service)
                        @php
                            $serviceImage = ($service->image && is_file(public_path('storage/'.$service->image)))
                                ? asset('storage/'.$service->image)
                                : null;
                        @endphp
                        <a class="yk-about__service" href="{{ route('service.show', $service) }}" data-reveal style="--reveal-delay: {{ ($loop->index % 4) * 70 }}ms">
                            @if ($serviceImage)
                                <img src="{{ $serviceImage }}" alt="" width="480" height="320">
                            @endif
                            <span>{{ $service->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="yk-about__section" aria-labelledby="about-stories">
            <div class="yk-doc__wrap">
                <header class="yk-about__intro" data-reveal>
                    <h2 id="about-stories">What clients have shared</h2>
                    <p>Comments published on YuktiOne. Names appear only when they were included in the original comment.</p>
                </header>
                <div class="yk-about__quotes">
                    @foreach ($testimonials as $testimonial)
                        @php
                            $portrait = ($testimonial->image && is_file(public_path('storage/'.$testimonial->image)))
                                ? asset('storage/'.$testimonial->image)
                                : null;
                        @endphp
                        <figure class="yk-about__quote" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 70 }}ms">
                            @if ($portrait)
                                <img src="{{ $portrait }}" alt="" width="64" height="64">
                            @endif
                            <blockquote>
                                <p>{{ $testimonial->description }}</p>
                            </blockquote>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="yk-about__section" aria-labelledby="about-faq">
        <div class="yk-doc__wrap yk-about__faq-wrap">
            <header class="yk-about__intro" data-reveal>
                <h2 id="about-faq">Frequently Asked Questions</h2>
                <p>How browsing, booking, payment, and your account work today.</p>
            </header>
            <div class="yk-about__faq" data-reveal>
                <details open>
                    <summary>Why choose YuktiOne?</summary>
                    <div class="yk-about__answer"><p>YuktiOne brings published professionals into one place. You can browse by service, open a profile, and book an available consultation instead of searching for each specialist separately.</p></div>
                </details>
                <details>
                    <summary>How can I book a professional?</summary>
                    <div class="yk-about__answer"><p>Open a professional from Explore, sign in, and choose an open date and time on their calendar. Complete checkout to confirm the booking. If payment does not succeed, you can retry it.</p></div>
                </details>
                <details>
                    <summary>What services are available?</summary>
                    <div class="yk-about__answer"><p>The active services are listed in Our Expertise on this page. Each card opens that service. Explore then shows the professionals connected to the services you select.</p></div>
                </details>
                <details>
                    <summary>How do payments work?</summary>
                    <div class="yk-about__answer"><p>Payment is taken at checkout, using the rate and consultation plan published for that booking. The appointment is confirmed after payment succeeds. A payment that fails can be retried from your account.</p></div>
                </details>
                <details>
                    <summary>Can I manage my bookings?</summary>
                    <div class="yk-about__answer"><p>Yes, after you sign in. My Bookings lists your appointments. You can review a booking, reschedule an upcoming appointment when another time is open, and send a message on that booking.</p></div>
                </details>
            </div>
        </div>
    </section>

    <section class="yk-about__close" aria-labelledby="about-next" data-reveal>
        <div class="yk-doc__wrap">
            <h2 id="about-next">Ready to Take the Next Step?</h2>
            <p>Explore professionals and find guidance suited to your goals.</p>
            <a class="yk-doc__button" href="{{ route('professionals') }}">Explore Professionals</a>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const items = document.querySelectorAll('.yk-about [data-reveal]');
    if (reduce || !('IntersectionObserver' in window)) {
        items.forEach(function (item) { item.classList.add('is-in'); });
    } else {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.16 });
        items.forEach(function (item) { observer.observe(item); });
    }

    document.querySelectorAll('.yk-about__faq details').forEach(function (item) {
        item.addEventListener('toggle', function () {
            if (!item.open) return;
            document.querySelectorAll('.yk-about__faq details').forEach(function (other) {
                if (other !== item) other.open = false;
            });
        });
    });
});
</script>
@endpush
