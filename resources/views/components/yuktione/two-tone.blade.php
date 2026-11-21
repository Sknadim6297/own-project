@props([
    'text' => '',
    'words' => 2,
])

{!! \App\Support\BrandHeading::twoTone((string) $text, (int) $words) !!}
