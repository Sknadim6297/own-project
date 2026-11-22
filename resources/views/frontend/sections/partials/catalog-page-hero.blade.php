{{-- Shared Career-style page hero: back link, title, lead --}}
@php
    $backHref = $backHref ?? route('categories.index');
    $backLabel = $backLabel ?? 'All Categories';
    $title = $title ?? '';
    $lead = $lead ?? '';
@endphp
<section class="yk-cat-detail__hero">
    <div class="yk-cat-detail__wrap">
        <a class="yk-cat-detail__back" href="{{ $backHref }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 6 9 12l6 6"/></svg>
            <span>{{ $backLabel }}</span>
        </a>
        <h1>{{ $title }}</h1>
        @if ($lead !== '')
            <p class="yk-cat-detail__lead">{{ $lead }}</p>
        @endif
    </div>
</section>
