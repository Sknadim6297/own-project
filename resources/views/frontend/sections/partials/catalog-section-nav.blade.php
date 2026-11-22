{{-- Cross-page Tools / Experts / Programmes pill tabs (Career category style) --}}
@php
    $active = $active ?? 'tools';
    $counts = $counts ?? [];
    $programmesEnabled = (bool) config('yuktione.programmes_enabled');
    $sections = [
        'tools' => ['label' => 'Tools', 'href' => route('tools.index')],
        'experts' => ['label' => 'Experts', 'href' => route('professionals')],
        'programmes' => [
            'label' => 'Programmes',
            'href' => $programmesEnabled ? route('programmes.index') : '#',
            'disabled' => ! $programmesEnabled,
        ],
    ];
@endphp
<nav class="yk-cat-detail__tabs" aria-label="Browse sections">
    @foreach ($sections as $key => $section)
        @if (! empty($section['disabled']))
            <span class="yk-cat-detail__tab is-disabled" aria-disabled="true">
                {{ $section['label'] }}
                <em class="yk-cat-detail__soon">Soon</em>
            </span>
        @else
            <a
                class="yk-cat-detail__tab{{ $active === $key ? ' is-active' : '' }}"
                href="{{ $section['href'] }}"
                @if ($active === $key) aria-current="page" @endif
            >
                {{ $section['label'] }}
                @if (isset($counts[$key]))
                    <span class="yk-cat-detail__count">{{ $counts[$key] }}</span>
                @endif
            </a>
        @endif
    @endforeach
</nav>
