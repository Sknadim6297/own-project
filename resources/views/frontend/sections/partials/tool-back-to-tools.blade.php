@php
    $label = $label ?? 'Back to Tools';
    $class = trim(($class ?? '').' yk-tool-back');
@endphp
<a class="{{ $class }}" href="{{ route('tools.index') }}">
    <span aria-hidden="true">←</span> {{ $label }}
</a>
