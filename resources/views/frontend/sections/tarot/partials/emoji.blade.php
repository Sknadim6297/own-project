@php
    $label = $label ?? '';
    $decorative = $decorative ?? true;
@endphp
<span class="yk-tarot__emoji" @if ($decorative) aria-hidden="true" @else role="img" aria-label="{{ $label }}" @endif>{{ $emoji }}</span>
