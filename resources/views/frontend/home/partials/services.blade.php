@php
    $tones = ['lavender', 'mint', 'blush', 'cream', 'blue'];
    $homeServices = collect($services ?? [])
        ->filter(fn ($service) => filled($service->slug ?? null) && filled($service->name ?? null))
        ->values()
        ->take(16);
    $fallbackImage = asset('frontend/assets/img/new-icons/header-menu-icon/designer.png');
@endphp

<section class="yk-services" aria-labelledby="yk-services-title" data-services-carousel>
    <div class="yk-services__inner">
        <header class="yk-services__head">
            <div class="yk-services__copy">
                <p class="yk-services__eyebrow">Trusted guidance</p>
                <h2 class="yk-services__title" id="yk-services-title">
                    <span class="yk-services__heading-navy">Popular services.</span>
                    <span class="yk-services__heading-lavender">Expert support.</span>
                </h2>
                <p class="yk-services__lead">Browse verified services and connect with professionals for clearer next steps.</p>
            </div>
            <div class="yk-services__actions">
                <div class="yk-services__nav">
                    <button class="yk-services__nav-btn" type="button" data-services-prev aria-label="Previous services" disabled>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 6 9 12l6 6"></path></svg>
                    </button>
                    <button class="yk-services__nav-btn" type="button" data-services-next aria-label="Next services">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"></path></svg>
                    </button>
                </div>
                <a class="yk-services__all" href="{{ route('categories.index') }}">View all categories <span aria-hidden="true">→</span></a>
            </div>
        </header>

        @if ($homeServices->isEmpty())
            <p class="yk-home-empty-state">Services will appear here when they are published.</p>
        @else
            <div class="yk-services__viewport">
                <ul class="yk-services__track" data-services-track>
                    @foreach ($homeServices as $service)
                        @php
                            $tone = $tones[$loop->index % count($tones)];
                            $image = $service->image
                                ? asset('storage/' . ltrim($service->image, '/'))
                                : $fallbackImage;
                        @endphp
                        <li>
                            <a class="yk-services__card yk-services__card--{{ $tone }}" href="{{ route('service.show', $service->slug) }}">
                                <span class="yk-services__media" aria-hidden="true">
                                    <img src="{{ $image }}" alt="" width="72" height="72" loading="lazy" decoding="async">
                                </span>
                                <span class="yk-services__body">
                                    <span class="yk-services__name">{{ $service->name }}</span>
                                    <span class="yk-services__cta">Explore</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
