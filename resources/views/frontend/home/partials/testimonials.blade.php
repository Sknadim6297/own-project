<section class="yk-home-testimonials" aria-labelledby="yk-home-testimonials-title">
    <div class="yk-home-container">
        <header class="yk-home-testimonials__heading">
            <p class="yk-home-testimonials__eyebrow">Testimonials</p>
            <h2 id="yk-home-testimonials-title"><x-yuktione.two-tone text="What Our Clients Say" /></h2>
            <p>Real experiences and feedback from our community.</p>
        </header>

        @if ($testimonials->isNotEmpty())
            <div class="yk-home-testimonials__carousel" data-testimonial-carousel>
                @if ($testimonials->count() > 1)
                    <div class="yk-home-testimonials__controls" aria-label="Testimonial navigation">
                        <button
                            class="yk-home-testimonials__arrow"
                            type="button"
                            aria-label="Show previous testimonials"
                            data-testimonial-previous
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </button>
                        <button
                            class="yk-home-testimonials__arrow"
                            type="button"
                            aria-label="Show next testimonials"
                            data-testimonial-next
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                @endif

                <div
                    class="yk-home-testimonials__track"
                    role="region"
                    aria-label="Client testimonials"
                    aria-roledescription="carousel"
                    tabindex="0"
                    data-testimonial-track
                >
                    @foreach ($testimonials as $testimonial)
                        <article class="yk-home-testimonial-card" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}">
                            <svg class="yk-home-testimonial-card__quote" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
                                <path d="M17.4 8.5c-6.3 2.8-10 7.8-10 15.1 0 4.3 2.3 7.1 5.8 7.1 3.2 0 5.4-2.3 5.4-5.4 0-3-2.1-5.1-5-5.1-.5 0-.9.1-1.3.2.8-3.3 3.3-5.7 7-7.7l-1.9-4.2Zm16 0c-6.3 2.8-10 7.8-10 15.1 0 4.3 2.3 7.1 5.8 7.1 3.2 0 5.4-2.3 5.4-5.4 0-3-2.1-5.1-5-5.1-.5 0-.9.1-1.3.2.8-3.3 3.3-5.7 7-7.7l-1.9-4.2Z"/>
                            </svg>

                            <blockquote class="yk-home-testimonial-card__review">
                                <p>{{ $testimonial->description }}</p>
                            </blockquote>

                            <div class="yk-home-testimonial-card__client">
                                @if ($testimonial->image)
                                    <img
                                        class="yk-home-testimonial-card__avatar"
                                        src="{{ '/storage/' . ltrim($testimonial->image, '/') }}"
                                        alt="Client testimonial photo"
                                        width="56"
                                        height="56"
                                        loading="lazy"
                                        decoding="async"
                                        data-testimonial-avatar
                                    >
                                    <span class="yk-home-testimonial-card__avatar-fallback" aria-hidden="true" hidden>
                                        <svg viewBox="0 0 24 24" focusable="false">
                                            <circle cx="12" cy="8" r="3.5"/>
                                            <path d="M5 20a7 7 0 0 1 14 0"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="yk-home-testimonial-card__avatar-fallback" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false">
                                            <circle cx="12" cy="8" r="3.5"/>
                                            <path d="M5 20a7 7 0 0 1 14 0"/>
                                        </svg>
                                    </span>
                                @endif
                                <span class="yk-home-testimonial-card__client-label">Client feedback</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @else
            <p class="yk-home-testimonials__empty">Client stories will appear here when available.</p>
        @endif
    </div>
</section>
