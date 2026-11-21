@extends('layouts.layout')

@section('title', 'Money & Finance Tools | YuktiOne')
@section('meta_description', 'Explore YuktiOne personal finance calculators for planning, goals, debt and savings.')
@section('body_class', 'yk-hub-body')

@push('styles')
    <link href="{{ asset('frontend/assets/css/yuktione-pfm-hub.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-pfm-hub.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $resumable = $resumable ?? [];
    $bySlug = $tools->keyBy('slug');

    // Presentation only: how the calculators are grouped on this page.
    $groups = [
        ['title' => 'Everyday Money', 'icon' => 'wallet', 'blurb' => 'Know where you stand today: cash flow, net worth and your safety net.', 'slugs' => ['monthly-money-planner', 'net-worth-calculator', 'emergency-fund-calculator']],
        ['title' => 'Goals & Growth', 'icon' => 'growth', 'blurb' => 'Plan what you are saving and investing for, from a first home to retirement.', 'slugs' => ['financial-goal-calculator', 'savings-investment-calculator', 'retirement-calculator']],
        ['title' => 'Debt & Prices', 'icon' => 'layers', 'blurb' => 'See what borrowing costs and how rising prices change your numbers.', 'slugs' => ['debt-emi-calculator', 'inflation-calculator']],
    ];
    $placed = collect($groups)->pluck('slugs')->flatten()->all();
    $leftover = $tools->pluck('slug')->reject(fn ($slug) => in_array($slug, $placed, true))->values()->all();
    if ($leftover !== []) {
        $groups[] = ['title' => 'More Tools', 'icon' => 'layers', 'blurb' => 'Further calculators for your money plan.', 'slugs' => $leftover];
    }

    $groupIcons = [
        'wallet' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H17v3"/><path d="M4 7.5V17a2 2 0 0 0 2 2h12a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H6.5A2.5 2.5 0 0 1 4 7.5Z"/><circle cx="15.5" cy="13.5" r="1" fill="currentColor"/>',
        'growth' => '<path d="M5 19v-6M10.5 19V9M16 19v-4"/><path d="M4 8.5l5-4 4 3 7-5"/><path d="M16.5 2.5H20V6"/>',
        'layers' => '<path d="M12 3.5 3.5 8 12 12.5 20.5 8 12 3.5Z"/><path d="M3.5 12 12 16.5 20.5 12"/><path d="M3.5 16 12 20.5 20.5 16"/>',
    ];

    $icons = [
        'monthly-money-planner' => '<rect x="4" y="5.5" width="16" height="14" rx="2.5"/><path d="M4 10h16M8.5 3.5v3.5M15.5 3.5v3.5"/><path d="M9 14.5h.01M12 14.5h.01M15 14.5h.01" stroke-width="2.2"/>',
        'net-worth-calculator' => '<path d="M12 3.5V12h8.5"/><path d="M20 15.2A8.5 8.5 0 1 1 8.8 4"/>',
        'emergency-fund-calculator' => '<path d="M12 3l7 3v5.5c0 4.2-2.8 7.6-7 9.5-4.2-1.9-7-5.3-7-9.5V6l7-3Z"/><path d="M8.8 12l2.2 2.2 4.2-4.4"/>',
        'financial-goal-calculator' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/>',
        'debt-emi-calculator' => '<rect x="3" y="6" width="18" height="12" rx="2.5"/><path d="M3 10.5h18M7 15h3.5"/>',
        'retirement-calculator' => '<path d="M3.5 12a8.5 8.5 0 0 1 17 0Z"/><path d="M12 12v6.5a1.8 1.8 0 0 1-3.6 0"/><path d="M12 3.5V2.5"/>',
        'inflation-calculator' => '<path d="M3.5 17l6-6 4 4 7-8"/><path d="M15 7h5.5v5.5"/>',
        'savings-investment-calculator' => '<ellipse cx="12" cy="7" rx="6" ry="2.6"/><path d="M6 7v4c0 1.4 2.7 2.6 6 2.6s6-1.2 6-2.6V7"/><path d="M6 11v4c0 1.4 2.7 2.6 6 2.6s6-1.2 6-2.6v-4"/>',
    ];
    $fallbackIcon = '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2"/>';

    // Soft tile colours per tool (presentation only).
    $tones = [
        'monthly-money-planner' => 'rose', 'net-worth-calculator' => 'sage', 'emergency-fund-calculator' => 'rose',
        'financial-goal-calculator' => 'rose', 'savings-investment-calculator' => 'amber', 'retirement-calculator' => 'lilac',
        'debt-emi-calculator' => 'rose', 'inflation-calculator' => 'sage',
    ];

    $openCount = collect($tools)->filter(fn ($t) => in_array($t['slug'], $openTools, true))->count();
@endphp
<main class="yk-hub" data-yk-hub>
    <svg class="yk-hub__leaf yk-hub__leaf--left" viewBox="0 0 160 300" aria-hidden="true" focusable="false">
        <g fill="#e0b0c4" opacity=".45">
            <path d="M-10 300C-6 240 10 190 50 150c8 50-8 110-60 150Z"/>
            <path d="M40 300c-4-50 10-100 50-140 10 50-6 100-50 140Z" opacity=".7"/>
            <path d="M-10 190c10-40 36-70 70-86-2 40-26 72-70 86Z" opacity=".8"/>
        </g>
    </svg>
    <svg class="yk-hub__leaf yk-hub__leaf--right" viewBox="0 0 160 300" aria-hidden="true" focusable="false">
        <g fill="#e0b0c4" opacity=".45">
            <path d="M170 300c-4-60-20-110-60-150-8 50 8 110 60 150Z"/>
            <path d="M120 300c4-50-10-100-50-140-10 50 6 100 50 140Z" opacity=".7"/>
            <path d="M170 190c-10-40-36-70-70-86 2 40 26 72 70 86Z" opacity=".8"/>
        </g>
    </svg>

    <header class="yk-hub__hero">
        <div class="yk-hub__wrap yk-hub__hero-grid">
            <div class="yk-hub__hero-copy">
                <nav class="yk-hub__crumbs" aria-label="Breadcrumb">
                    <a href="{{ route('tools.index') }}"><span aria-hidden="true">←</span> Back to Tools</a>
                </nav>
                <p class="yk-hub__eyebrow">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">{!! $groupIcons['wallet'] !!}</svg>
                    Money &amp; Finance
                </p>
                <h1 id="pfm-hub-heading"><x-yuktione.two-tone text="Personal Finance Tools" /></h1>
                <p class="yk-hub__lead">Plan cash flow, goals, debt and investments with educational calculators. Save results when you are signed in.</p>
                <ul class="yk-hub__facts">
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13 2.5 5 13.5h6l-1 8 8-11h-6l1-8Z"/></svg>
                        <span><b>{{ $openCount }}</b> {{ $openCount === 1 ? 'calculator' : 'calculators' }}</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 19v-6M11 19V6M17 19v-9"/></svg>
                        <span>Results in a few steps</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="5" y="10.5" width="14" height="9.5" rx="2.4"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/></svg>
                        <span>Save to your history when signed in</span>
                    </li>
                </ul>
            </div>

            <svg class="yk-hub__hero-art" viewBox="0 0 440 260" aria-hidden="true" focusable="false">
                <defs>
                    <linearGradient id="hubBar" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#c3196b"/>
                        <stop offset="1" stop-color="#8a0f4d"/>
                    </linearGradient>
                    <linearGradient id="hubArrow" x1="0" y1="1" x2="1" y2="0">
                        <stop offset="0" stop-color="#c3196b"/>
                        <stop offset="1" stop-color="#6e0b3c"/>
                    </linearGradient>
                </defs>
                <path d="M30 150c-8-60 36-118 110-124 74-6 112 20 170 8 60-12 104 30 100 86-4 56-52 92-128 98-62 5-86-12-138-10-60 2-106-10-114-58Z" fill="#f8eaf1"/>
                <g fill="url(#hubBar)">
                    <rect x="268" y="150" width="34" height="62" rx="6" opacity=".7"/>
                    <rect x="312" y="116" width="34" height="96" rx="6" opacity=".85"/>
                    <rect x="356" y="78" width="34" height="134" rx="6"/>
                </g>
                <path d="M250 150 316 96l36 22 62-58" fill="none" stroke="url(#hubArrow)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M386 56h32v32" fill="none" stroke="url(#hubArrow)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                <g fill="#fff" stroke="#e0b0c4" stroke-width="3">
                    <circle cx="316" cy="96" r="7"/><circle cx="352" cy="118" r="7"/>
                </g>
                <path d="M80 214h300" stroke="#efe0e8" stroke-width="4" stroke-linecap="round"/>
                <g fill="none" stroke="#e58bb8" stroke-width="5" stroke-linecap="round">
                    <path d="M70 70l-10-10M90 56l-3-13M108 70l10-9"/>
                </g>
            </svg>
        </div>
    </header>

    <div class="yk-hub__wrap yk-hub__body">
        @foreach ($groups as $index => $group)
            @php
                $items = collect($group['slugs'])->map(fn ($slug) => $bySlug->get($slug))->filter()->values();
            @endphp
            @continue($items->isEmpty())
            <section class="yk-hub__group" aria-labelledby="pfm-group-{{ $index }}">
                <div class="yk-hub__group-head">
                    <span class="yk-hub__group-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">{!! $groupIcons[$group['icon']] ?? $groupIcons['layers'] !!}</svg>
                    </span>
                    <div class="yk-hub__group-text">
                        <span class="yk-hub__group-no" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h2 id="pfm-group-{{ $index }}">{{ $group['title'] }}</h2>
                        <p>{{ $group['blurb'] }}</p>
                    </div>
                </div>

                <ul class="yk-hub__list">
                    @foreach ($items as $tool)
                        @php
                            $open = in_array($tool['slug'], $openTools, true);
                            $hasResult = in_array($tool['slug'], $resumable, true);
                        @endphp
                        <li class="yk-hub__card {{ $open ? '' : 'is-closed' }}">
                            <span class="yk-hub__icon yk-hub__icon--{{ $tones[$tool['slug']] ?? 'rose' }}" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">{!! $icons[$tool['slug']] ?? $fallbackIcon !!}</svg>
                            </span>
                            <div class="yk-hub__text">
                                <h3>
                                    @if ($open)
                                        <a class="yk-hub__link" href="{{ route($tool['route']) }}">{{ $tool['name'] }}</a>
                                    @else
                                        {{ $tool['name'] }}
                                    @endif
                                </h3>
                                <p>{{ $tool['description'] }}</p>
                            </div>
                            <div class="yk-hub__cta">
                                @if (! $open)
                                    <span class="yk-hub__badge yk-hub__badge--off">Unavailable</span>
                                @else
                                    @if ($hasResult)
                                        <a class="yk-hub__resume" href="{{ route($tool['route'].'.results') }}">View Result</a>
                                    @endif
                                    <span class="yk-hub__open" aria-hidden="true">
                                        Open
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h14M13.6 6.4 19.2 12l-5.6 5.6"/></svg>
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <p class="yk-hub__note">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 7.8v.01"/></svg>
            All figures are educational estimates based on the numbers you enter. They are not financial advice.
        </p>
    </div>
</main>
@endsection
