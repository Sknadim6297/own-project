@extends('layouts.layout')

@section('title', 'Parenting & Spirituality Tools | YuktiOne')
@section('meta_description', 'Explore YuktiOne parenting and spirituality tools for reflection, routines, conversations, mindfulness and values.')
@section('body_class', 'yk-ps-body')

@section('content')
@php
    $art = \App\Support\Tools\ToolIllustration::DIRECTORY;
    $artParenting = file_exists(public_path($art.'parenting-stress-3d.webp')) ? asset($art.'parenting-stress-3d.webp') : null;
    $artCalm = file_exists(public_path($art.'mindfulness-calm-3d.webp')) ? asset($art.'mindfulness-calm-3d.webp') : null;
    $groups = [
        ['key' => 'parenting', 'title' => 'Parenting', 'blurb' => 'Calmer days, clearer routines and kinder conversations.', 'glyph' => 'users', 'tools' => $parenting],
        ['key' => 'spirituality', 'title' => 'Spirituality', 'blurb' => 'Quiet practices and values that keep you grounded.', 'glyph' => 'leaf', 'tools' => $spirituality],
    ];
@endphp
<main class="yk-ps yk-tools-ds" data-yk-tools-ds data-yk-ps>
    <div class="yk-ps__wrap yk-ps__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')

        <header class="yk-ps__hero" aria-labelledby="ps-hub-heading">
            <div class="yk-ps__hero-copy">
                <p class="yk-ps__badge">@include('frontend.sections.ps.partials.icon', ['name' => 'leaf', 'size' => 14]) Parenting &amp; Spirituality</p>
                <h1 id="ps-hub-heading">Parenting &amp; Spirituality Tools</h1>
                <p class="yk-ps__lead">Practical, educational tools for family life and calm reflection. Saved results stay private when you are signed in.</p>
                <ul class="yk-ps__trust">
                    <li>@include('frontend.sections.ps.partials.icon', ['name' => 'lock', 'size' => 16]) Private by default</li>
                    <li>@include('frontend.sections.ps.partials.icon', ['name' => 'clock', 'size' => 16]) Quick, guided steps</li>
                    <li>@include('frontend.sections.ps.partials.icon', ['name' => 'book', 'size' => 16]) Educational guidance</li>
                </ul>
            </div>
            <div class="yk-ps__hero-art" aria-hidden="true">
                <span class="yk-ps__hero-blob"></span>
                @if ($artParenting)
                    <img class="yk-ps__hero-img yk-ps__hero-img--a" src="{{ $artParenting }}" alt="" decoding="async">
                @endif
                @if ($artCalm)
                    <img class="yk-ps__hero-img yk-ps__hero-img--b" src="{{ $artCalm }}" alt="" decoding="async">
                @endif
            </div>
        </header>

        @foreach ($groups as $group)
            <section class="yk-ps__group" aria-labelledby="ps-group-{{ $group['key'] }}">
                <div class="yk-ps__group-head">
                    <span class="yk-ps__group-icon yk-ps__group-icon--{{ $group['key'] }}">@include('frontend.sections.ps.partials.icon', ['name' => $group['glyph'], 'size' => 22])</span>
                    <div>
                        <h2 id="ps-group-{{ $group['key'] }}">{{ $group['title'] }}</h2>
                        <p>{{ $group['blurb'] }}</p>
                    </div>
                    <span class="yk-ps__count">{{ count($group['tools']) }} {{ \Illuminate\Support\Str::plural('tool', count($group['tools'])) }}</span>
                </div>

                <div class="yk-ps__tool-grid yk-ps__tool-grid--{{ $group['key'] }}">
                    @foreach ($group['tools'] as $tool)
                        @php $open = in_array($tool['slug'], $openTools, true); @endphp
                        <article @class(['yk-ps__tool-card', 'is-closed' => ! $open])>
                            <span class="yk-ps__tool-icon yk-ps__tool-icon--{{ $group['key'] }}">@include('frontend.sections.ps.partials.icon', ['name' => $tool['glyph'] ?? 'sparkle', 'size' => 26])</span>
                            <h3>{{ $tool['name'] }}</h3>
                            <p class="yk-ps__tool-desc">{{ $tool['description'] }}</p>
                            @if (! empty($tool['meta']))
                                <p class="yk-ps__tool-meta">@include('frontend.sections.ps.partials.icon', ['name' => 'clock', 'size' => 14]) {{ $tool['meta'] }}</p>
                            @endif
                            @if ($open)
                                <a class="yk-ps__btn yk-ps__btn--block" href="{{ route($tool['route']) }}">Open tool @include('frontend.sections.ps.partials.icon', ['name' => 'arrow', 'size' => 16])</a>
                            @else
                                <span class="yk-ps__btn yk-ps__btn--disabled yk-ps__btn--block" aria-disabled="true">Unavailable</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <p class="yk-ps__note yk-ps__note--center">@include('frontend.sections.ps.partials.icon', ['name' => 'info', 'size' => 16]) <span>{{ \App\Support\ParentingSpirituality\PsCatalog::DISCLAIMER }}</span></p>
    </div>
</main>
@endsection
