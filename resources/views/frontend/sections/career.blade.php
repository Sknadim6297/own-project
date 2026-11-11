@extends('layouts.layout')

@section('title', 'Careers | YuktiOne')
@section('meta_description', 'Explore open roles and grow with YuktiOne.')

@section('content')
<x-yuktione.page
    class="yk-career"
    title="Careers"
    lead="Flexibility, innovation, and room to grow. Browse open roles and apply when you are ready."
    crumb="Careers"
>
    <div class="yk-career__layout">
        <section class="yk-career__intro yk-doc__panel" aria-labelledby="career-offices-heading">
            <p class="yk-doc__kicker">Our offices</p>
            <h2 id="career-offices-heading">We’re country-wide</h2>
            <p>Join a team building guidance tools and professional services for people across India.</p>
            <div class="yk-career__chips" aria-label="Office locations">
                <span>Mumbai</span>
                <span>Kolkata</span>
            </div>
            <div class="yk-career__contact">
                <a href="mailto:info@yuktione.in">info@yuktione.in</a>
                <p class="yk-doc__lead">Connect with us on the social links in the site footer.</p>
            </div>
        </section>

        <section class="yk-career__jobs" aria-labelledby="open-positions-heading">
            <h2 id="open-positions-heading">Open positions</h2>
            @if ($careers->isEmpty())
                <div class="yk-doc__empty">
                    <h3>No openings right now</h3>
                    <p>Check back soon, or email <a href="mailto:info@yuktione.in">info@yuktione.in</a> with your interest.</p>
                </div>
            @else
                <ul class="yk-career__list">
                    @foreach ($careers as $career)
                        <li>
                            <a href="{{ route('job.details', $career->id) }}">
                                <h3>{{ $career->title }}</h3>
                                <p>
                                    {{ $career->job_type ?: 'Role type not listed' }}
                                    @if ($career->location)
                                        · {{ $career->location }}
                                    @endif
                                    @if ($career->department)
                                        · {{ $career->department }}
                                    @endif
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-yuktione.page>
@endsection
