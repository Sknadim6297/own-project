@php
    /**
     * Compact Money & Finance tool header (Task 37) — decorative SVG art removed.
     * Pattern aligned with Financial Goal Task 31 compact header.
     *
     * @var list<array{0: string, 1: ?string}> $crumbs
     * @var string $eyebrow
     * @var string $title
     * @var string $titleId
     * @var string|null $lead
     * @var string|null $meta
     * @var string|null $heroIcon  Registry key for <x-yk-icon>
     */
    $crumbs = $crumbs ?? [];
    $lead = $lead ?? null;
    $meta = $meta ?? null;
    $heroIcon = $heroIcon ?? 'calculator';
@endphp
<header class="yk-pfm__hero yk-pfm__hero--compact" data-yk-tool-compact-header>
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
                <x-yk-icon :name="$heroIcon" class="yk-icon--sm" />
                {{ $eyebrow }}
            </p>
            <div class="yk-pfm__hero-title-row">
                <span class="yk-pfm__hero-icon" aria-hidden="true">
                    <x-yk-icon :name="$heroIcon" class="yk-icon--lg" />
                </span>
                <div>
                    <h1 id="{{ $titleId }}"><x-yuktione.two-tone :text="$title" /></h1>
                    @if (filled($lead))
                        <p class="yk-pfm__hero-lead" data-yk-tool-page-lead>{{ $lead }}</p>
                    @endif
                    @if (filled($meta))
                        <p class="yk-pfm__hero-meta">{{ $meta }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
