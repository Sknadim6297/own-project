@props([
    'title',
    'lead' => null,
    'crumb' => null,
])

<main {{ $attributes->class('yk-doc') }}>
    <header class="yk-doc__hero">
        <div class="yk-doc__wrap">
            <nav class="yk-doc__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">›</span>
                <span aria-current="page">{{ $crumb ?? $title }}</span>
            </nav>
            <h1>{{ $title }}</h1>
            @if ($lead)
                <p class="yk-doc__lead">{{ $lead }}</p>
            @endif
            @if (isset($actions) && ! $actions->isEmpty())
                <div class="yk-doc__actions">{{ $actions }}</div>
            @endif
        </div>
    </header>
    <div class="yk-doc__wrap yk-doc__body">
        {{ $slot }}
    </div>
</main>
