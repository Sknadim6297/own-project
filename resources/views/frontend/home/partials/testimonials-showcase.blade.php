{{-- Testimonials — static presentation section for the home page. --}}
@php
    $voices = [
        [
            'quote' => 'YuktiOne helped me manage my anxiety and build a healthier routine. I feel more balanced and in control.',
            'name' => 'Ritika S.',
            'role' => 'Marketing Professional',
            'avatar' => 'avatar-ritika.jpg',
        ],
        [
            'quote' => 'The career counselling session gave me the clarity and confidence to switch to a role I truly enjoy.',
            'name' => 'Arjun M.',
            'role' => 'Product Manager',
            'avatar' => 'avatar-arjun.jpg',
        ],
        [
            'quote' => 'A simple, trusted platform with great experts and practical tools. It has become my go-to for overall wellbeing.',
            'name' => 'Neha P.',
            'role' => 'Entrepreneur',
            'avatar' => 'avatar-neha.jpg',
        ],
    ];
@endphp

<section class="yk-voices" aria-labelledby="yk-voices-title">
    <div class="yk-voices__container">
        <header class="yk-voices__head">
            <div class="yk-voices__heading">
                <span class="yk-voices__rule" aria-hidden="true"></span>
                <h2 id="yk-voices-title"><x-yuktione.two-tone text="Testimonials" :words="1" /></h2>
                <span class="yk-voices__rule" aria-hidden="true"></span>
            </div>
            <p>Real stories from our growing community</p>
        </header>

        <ul class="yk-voices__grid">
            @foreach ($voices as $voice)
                <li>
                    <figure class="yk-voice">
                        <svg class="yk-voice__mark" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
                            <path d="M17.4 8.5c-6.3 2.8-10 7.8-10 15.1 0 4.3 2.3 7.1 5.8 7.1 3.2 0 5.4-2.3 5.4-5.4 0-3-2.1-5.1-5-5.1-.5 0-.9.1-1.3.2.8-3.3 3.3-5.700 7-7.700l-1.900-4.200Zm16 0c-6.3 2.8-10 7.8-10 15.1 0 4.3 2.3 7.1 5.8 7.1 3.2 0 5.4-2.3 5.4-5.4 0-3-2.1-5.1-5-5.1-.5 0-.9.1-1.3.2.8-3.3 3.3-5.700 7-7.700l-1.900-4.200Z"/>
                        </svg>
                        <blockquote class="yk-voice__quote">
                            <p>&ldquo;{{ $voice['quote'] }}&rdquo;</p>
                        </blockquote>
                        <figcaption class="yk-voice__who">
                            <img
                                class="yk-voice__avatar"
                                src="{{ asset('frontend/assets/img/testimonials-showcase/'.$voice['avatar']) }}"
                                alt="{{ $voice['name'] }}"
                                width="68"
                                height="68"
                                loading="lazy"
                                decoding="async"
                            >
                            <span class="yk-voice__id">
                                <strong>{{ $voice['name'] }}</strong>
                                <span>{{ $voice['role'] }}</span>
                            </span>
                            <span class="yk-voice__stars" role="img" aria-label="Rated 5 out of 5">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 2.8 2.75 5.7 6.25.9-4.5 4.4 1.05 6.2L12 17.100 6.45 20l1.05-6.2L3 9.400l6.25-.9L12 2.800Z"/></svg>
                                @endfor
                            </span>
                        </figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
    </div>
</section>
