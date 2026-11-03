@extends('layouts.layout')

@section('title', $metaTitle)
@section('meta_description', $metaDescription)

@section('content')
<x-yuktione.page
    class="yk-service"
    title="{{ $service->detail->banner_heading ?? $service->name }}"
    lead="{{ $service->detail->banner_sub_heading ?? 'Book our professional services' }}"
    crumb="{{ $service->name }}"
>
    <x-slot:actions>
        <a class="yk-doc__button" href="{{ route('gridlisting', ['service' => $service->slug]) }}">Book now</a>
    </x-slot:actions>

    <section class="yk-doc__split" aria-labelledby="service-about">
        <div>
            <p class="yk-doc__kicker">About</p>
            <h2 id="service-about">{{ $service->detail->about_heading ?? 'About '.$service->name }}</h2>
            @if ($service->detail->about_subheading)
                <p class="yk-doc__lead">{{ $service->detail->about_subheading }}</p>
            @endif
            <p class="yk-doc__lead">{{ $service->detail->about_description ?? 'Discover our professional services tailored to your needs.' }}</p>
            <div class="yk-doc__actions">
                <a class="yk-doc__button" href="{{ route('gridlisting', ['service' => $service->slug]) }}">Start searching</a>
            </div>
        </div>
        <div class="yk-doc__media">
            <img
                src="{{ $service->detail->about_image ? asset('storage/'.$service->detail->about_image) : asset('frontend/assets/img/default-about.jpg') }}"
                alt="{{ $service->detail->about_image_alt ?? 'About '.$service->name }}"
                width="720"
                height="540"
            >
        </div>
    </section>

    <section class="yk-doc__topics" aria-labelledby="service-steps">
        <h2 id="service-steps">{{ $service->detail->content_heading ?? 'How it works' }}</h2>
        <p class="yk-doc__lead">{{ $service->detail->how_it_works_subheading ?? $service->detail->content_sub_heading ?? 'Follow these simple steps to get started' }}</p>
        <div class="yk-doc__steps">
            @foreach ([1, 2, 3] as $step)
                <article class="yk-doc__card">
                    <span class="yk-doc__step-no">{{ $step }}</span>
                    <h3>{{ $service->detail->{'step'.$step.'_heading'} ?? 'Step '.$step }}</h3>
                    <p>{{ $service->detail->{'step'.$step.'_description'} ?? 'Continue to the next step' }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="yk-doc__topics" aria-labelledby="service-stories">
        <h2 id="service-stories">What clients say</h2>
        @if ($testimonials->isEmpty())
            <p class="yk-doc__empty">Client stories will appear here when they are published.</p>
        @else
            <div class="yk-doc__grid cols-topics">
                @foreach ($testimonials->take(6) as $testimonial)
                    <article class="yk-doc__card">
                        <h3>{{ $testimonial->name ?? 'Client' }}</h3>
                        <p>{{ $testimonial->description }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="yk-doc__panel" style="display: grid; gap: 0.7rem; margin-top: 1rem; padding: 1.15rem;">
        <h2 style="margin: 0; font-size: 1.35rem;">Are you a professional?</h2>
        <p class="yk-doc__lead" style="margin: 0;">Join YuktiOne to reach people who are looking for a professional service or consultation.</p>
        <div>
            <a class="yk-doc__button" href="{{ route('professional.register') }}">Start onboarding</a>
        </div>
    </section>

    <section class="yk-doc__topics" aria-labelledby="service-share">
        <h2 id="service-share">Share this service</h2>
        <ul class="yk-doc__share">
            <li><button type="button" data-share="facebook">Facebook</button></li>
            <li><button type="button" data-share="x">X</button></li>
            <li><button type="button" data-share="whatsapp">WhatsApp</button></li>
            <li><button type="button" data-share="instagram">Instagram</button></li>
            <li><button type="button" data-share="copy">Copy link</button></li>
        </ul>
    </section>
</x-yuktione.page>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const name = @json($service->name ?? 'Service');
    const url = window.location.href;
    const openShare = function (target) {
        window.open(target, '_blank', 'width=600,height=400');
    };
    const copyLink = function () {
        const done = function () {
            if (window.YuktioneToast) {
                window.YuktioneToast.success('Service link copied to clipboard!');
            }
        };
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(done);
            return;
        }
        const field = document.createElement('textarea');
        field.value = url;
        document.body.appendChild(field);
        field.select();
        document.execCommand('copy');
        field.remove();
        done();
    };

    document.querySelectorAll('[data-share]').forEach(function (button) {
        button.addEventListener('click', function () {
            const kind = button.getAttribute('data-share');
            if (kind === 'facebook') {
                openShare('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&quote=' + encodeURIComponent('Check out this service: ' + name));
            } else if (kind === 'x') {
                openShare('https://x.com/intent/tweet?text=' + encodeURIComponent('Check out this service: ' + name) + '&url=' + encodeURIComponent(url));
            } else if (kind === 'whatsapp') {
                openShare('https://wa.me/?text=' + encodeURIComponent('Check out this service: ' + name + ' ' + url));
            } else if (kind === 'instagram') {
                copyLink();
            } else {
                copyLink();
            }
        });
    });
});
</script>
@endpush
