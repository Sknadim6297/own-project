@php
    /**
     * Shared lavender header for the Money & Finance tool pages (same look as the hub).
     *
     * @var list<array{0: string, 1: ?string}> $crumbs   Breadcrumb trail after "Back to Tools" as [label, url|null].
     * @var string $eyebrow
     * @var string $title
     * @var string $titleId
     * @var string|null $lead
     * @var string|null $meta
     */
    $crumbs = $crumbs ?? [];
    $lead = $lead ?? null;
    $meta = $meta ?? null;
@endphp
<header class="yk-pfm__hero">
    <div class="yk-pfm__hero-in">
        <div class="yk-pfm__hero-copy">
            <nav class="yk-pfm__crumbs" aria-label="Breadcrumb">
                <a href="{{ route('tools.index') }}"><span aria-hidden="true">←</span> Back to Tools</a>
                @foreach ($crumbs as [$label, $url])
                    <span aria-hidden="true">/</span>
                    @if ($url)
                        <a href="{{ $url }}">{{ $label }}</a>
                    @else
                        <span class="yk-pfm__crumb-now" aria-current="page">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
            <p class="yk-pfm__eyebrow">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H17v3"/><path d="M4 7.5V17a2 2 0 0 0 2 2h12a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H6.5A2.5 2.5 0 0 1 4 7.5Z"/><circle cx="15.5" cy="13.5" r="1" fill="currentColor"/></svg>
                {{ $eyebrow }}
            </p>
            <h1 id="{{ $titleId }}">{{ $title }}</h1>
            @if (filled($lead))
                <p class="yk-pfm__hero-lead">{{ $lead }}</p>
            @endif
            @if (filled($meta))
                <p class="yk-pfm__hero-meta">{{ $meta }}</p>
            @endif
        </div>

        <svg class="yk-pfm__hero-art" viewBox="0 0 440 260" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="pfmHeroBar" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#c58be6"/>
                    <stop offset="1" stop-color="#9a4fc7"/>
                </linearGradient>
                <linearGradient id="pfmHeroArrow" x1="0" y1="1" x2="1" y2="0">
                    <stop offset="0" stop-color="#b56ddb"/>
                    <stop offset="1" stop-color="#7a2fb0"/>
                </linearGradient>
            </defs>
            <path d="M30 150c-8-60 36-118 110-124 74-6 112 20 170 8 60-12 104 30 100 86-4 56-52 92-128 98-62 5-86-12-138-10-60 2-106-10-114-58Z" fill="#f0e0fb"/>
            <g fill="url(#pfmHeroBar)">
                <rect x="268" y="150" width="34" height="62" rx="6" opacity=".7"/>
                <rect x="312" y="116" width="34" height="96" rx="6" opacity=".85"/>
                <rect x="356" y="78" width="34" height="134" rx="6"/>
            </g>
            <path d="M250 150 316 96l36 22 62-58" fill="none" stroke="url(#pfmHeroArrow)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M386 56h32v32" fill="none" stroke="url(#pfmHeroArrow)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            <g fill="#fff" stroke="#c9a2e6" stroke-width="3"><circle cx="316" cy="96" r="7"/><circle cx="352" cy="118" r="7"/></g>
            <path d="M80 214h300" stroke="#d9bdf0" stroke-width="4" stroke-linecap="round"/>
            <g fill="none" stroke="#e58bb8" stroke-width="5" stroke-linecap="round"><path d="M70 70l-10-10M90 56l-3-13M108 70l10-9"/></g>
        </svg>
    </div>
</header>
