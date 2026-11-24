@php
    /**
     * Shared compact tool header (Task 38) — Debt & EMI / page-hero structure.
     * Financial Goal keeps its dedicated goal-compact-header.
     *
     * @var string $title
     * @var string|null $titleId
     * @var string|null $lead
     * @var string|null $eyebrow
     * @var string|null $icon  YuktioneIconRegistry key
     * @var list<array{0: string, 1: ?string}>|null $crumbs  After Back to Tools
     * @var bool $showBack
     * @var string|null $historyUrl
     * @var string|null $historyLabel
     * @var bool $twoTone
     */
    $title = $title ?? 'Tool';
    $titleId = $titleId ?? 'tool-heading';
    $lead = $lead ?? null;
    $eyebrow = $eyebrow ?? null;
    $icon = $icon ?? 'sparkles';
    $crumbs = $crumbs ?? [];
    $showBack = $showBack ?? true;
    $historyUrl = $historyUrl ?? null;
    $historyLabel = $historyLabel ?? 'View history';
    $twoTone = $twoTone ?? true;
@endphp
<header class="yk-tool-shell__header" data-yk-tool-compact-header data-yk-tool-shell-header>
    <div class="yk-tool-shell__header-in">
        @if ($showBack || $crumbs !== [] || filled($historyUrl))
            <div class="yk-tool-shell__nav-row">
                <nav class="yk-tool-shell__crumbs" aria-label="Breadcrumb">
                    @if ($showBack)
                        <a href="{{ route('tools.index') }}"><span aria-hidden="true">←</span> Back to Tools</a>
                    @endif
                    @foreach ($crumbs as [$label, $url])
                        <span aria-hidden="true">/</span>
                        @if ($url)
                            <a href="{{ $url }}">{{ $label }}</a>
                        @else
                            <span class="yk-tool-shell__crumb-now" aria-current="page">{{ $label }}</span>
                        @endif
                    @endforeach
                </nav>
                @if (filled($historyUrl))
                    <a class="yk-tool-shell__history" href="{{ $historyUrl }}">
                        <x-yk-icon name="history" class="yk-icon--sm" />
                        {{ $historyLabel }}
                    </a>
                @endif
            </div>
        @endif

        @if (filled($eyebrow))
            <p class="yk-tool-shell__eyebrow">
                <x-yk-icon :name="$icon" class="yk-icon--sm" />
                {{ $eyebrow }}
            </p>
        @endif

        <div class="yk-tool-shell__title-row">
            <span class="yk-tool-shell__icon" aria-hidden="true">
                <x-yk-icon :name="$icon" class="yk-icon--lg" />
            </span>
            <div class="yk-tool-shell__copy">
                <h1 id="{{ $titleId }}">
                    @if ($twoTone)
                        <x-yuktione.two-tone :text="$title" />
                    @else
                        {{ $title }}
                    @endif
                </h1>
                @if (filled($lead))
                    <p class="yk-tool-shell__lead" data-yk-tool-page-lead>{{ $lead }}</p>
                @endif
            </div>
        </div>
    </div>
</header>
