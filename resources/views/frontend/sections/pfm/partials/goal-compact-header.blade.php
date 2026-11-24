@php
    /**
     * Compact Financial Goal header (Task 31) — replaces the large promotional hero band.
     *
     * @var string $title
     * @var string|null $titleId
     * @var string|null $lead
     * @var string|null $eyebrow
     * @var bool $showToolCrumb
     * @var \Illuminate\Support\HtmlString|string|null $facts  Optional results meta chips HTML
     */
    $title = $title ?? 'Financial Goal Calculator';
    $titleId = $titleId ?? 'goal-heading';
    $lead = $lead ?? 'Estimate the monthly investment needed to achieve your financial goal.';
    $eyebrow = $eyebrow ?? null;
    $showToolCrumb = $showToolCrumb ?? false;
    $facts = $facts ?? null;
@endphp
<header class="yk-goal__header" data-yk-goal-compact-header>
    <div class="yk-goal__wrap">
        <nav class="yk-goal__crumbs" aria-label="Breadcrumb">
            <a href="{{ route('tools.index') }}"><span aria-hidden="true">←</span> Back to Tools</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('tools.pfm') }}">Money &amp; Finance</a>
            @if ($showToolCrumb)
                <span aria-hidden="true">/</span>
                <a href="{{ route($tool['route']) }}">{{ $tool['name'] }}</a>
            @endif
        </nav>

        @if (filled($eyebrow))
            <p class="yk-goal__eyebrow">
                <x-yk-icon name="target" class="yk-icon--sm" />
                {{ $eyebrow }}
            </p>
        @endif

        <div class="yk-goal__header-title-row">
            <span class="yk-goal__header-icon" aria-hidden="true">
                <x-yk-icon name="target" class="yk-icon--lg" />
            </span>
            <div>
                <h1 id="{{ $titleId }}">{{ $title }}</h1>
                @if (filled($lead))
                    <p class="yk-goal__lead" data-yk-tool-page-lead>{{ $lead }}</p>
                @endif
            </div>
        </div>

        @if (filled($facts))
            <div class="yk-goal__header-facts">
                {!! $facts !!}
            </div>
        @endif
    </div>
</header>
