@php
    use App\Support\Icons\YuktioneIconRegistry;

    $name = (string) ($name ?? '');
    $class = trim((string) ($class ?? ''));
    $size = $size ?? null;
    $stroke = $stroke ?? 1.85;
    $decorative = filter_var($decorative ?? true, FILTER_VALIDATE_BOOLEAN);
    $label = (string) ($label ?? '');
    $title = (string) ($title ?? '');

    if ($name === '' && isset($emoji)) {
        $name = YuktioneIconRegistry::fromEmoji((string) $emoji) ?? 'sparkles';
    }

    $paths = YuktioneIconRegistry::paths($name);
    if ($paths === null) {
        $paths = YuktioneIconRegistry::paths('sparkles');
        $name = 'sparkles';
    }

    $classes = trim('yk-icon '.($class !== '' ? $class : 'yk-icon--md'));
    $width = $size !== null ? (int) $size : null;
@endphp
<svg
    class="{{ $classes }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $stroke }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    data-yk-icon="{{ $name }}"
    focusable="false"
    @if ($width) width="{{ $width }}" height="{{ $width }}" @endif
    @if ($decorative) aria-hidden="true" @else role="img" aria-label="{{ $label !== '' ? $label : $name }}" @endif
>
    @if ($title !== '')
        <title>{{ $title }}</title>
    @endif
    {!! $paths !!}
</svg>
