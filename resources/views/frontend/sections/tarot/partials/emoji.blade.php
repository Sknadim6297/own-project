@php
    use App\Support\Icons\YuktioneIconRegistry;

    $label = $label ?? '';
    $decorative = $decorative ?? true;
    $icon = $icon ?? null;
    if (! $icon && isset($emoji)) {
        $icon = YuktioneIconRegistry::fromEmoji((string) $emoji) ?? 'sparkles';
    }
    $icon = $icon ?: 'sparkles';
@endphp
<span class="yk-tarot__emoji" @if ($decorative) aria-hidden="true" @else role="img" aria-label="{{ $label }}" @endif>
    <x-yk-icon :name="$icon" class="yk-icon--md" :decorative="true" />
</span>
