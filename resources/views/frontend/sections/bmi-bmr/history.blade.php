@extends('layouts.layout')

@section('title', 'My BMI/BMR History | YuktiOne')
@section('meta_description', 'Private history of saved BMI and BMR calculations.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php
    $activityLabels = collect(\App\Support\BmiBmr\BmiBmrCatalog::ACTIVITIES)->mapWithKeys(fn ($a) => [$a['key'] => $a['label']]);
    $activityEmojis = collect(\App\Support\BmiBmr\BmiBmrCatalog::ACTIVITIES)->mapWithKeys(fn ($a) => [$a['key'] => $a['emoji'] ?? '']);
@endphp
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <div class="yk-bmi__wrap yk-bmi__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-bmi__card" aria-labelledby="bmi-history-heading">
            <p class="yk-bmi__badge">Private</p>
            <h1 id="bmi-history-heading">My BMI/BMR History</h1>
            <p class="yk-bmi__lead">Saved calculations on your YuktiOne profile.</p>

            @if (session('status'))
                <p class="yk-bmi__status" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-bmi__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="yk-bmi__actions" style="margin-top:0;">
                <a class="yk-bmi__btn" href="{{ route('tools.bmi-bmr') }}">New calculation</a>
            </div>

            @if ($items->isEmpty())
                <div class="yk-bmi__empty" role="status">
                    <p style="margin:0 0 0.35rem;font-weight:700;color:var(--yk-navy);">No saved calculations yet</p>
                    <p style="margin:0;">Run the calculator and tap Save to history.</p>
                </div>
            @else
                <ul class="yk-bmi__history">
                    @foreach ($items as $item)
                        @php
                            $when = optional($item->calculated_at)->timezone($timezone)?->format('d M Y, g:i A');
                            $actKey = $item->activity;
                            $actLabel = $activityLabels[$actKey] ?? str_replace('-', ' ', (string) $actKey);
                            $actEmoji = $activityEmojis[$actKey] ?? '';
                        @endphp
                        <li class="yk-bmi__history-card">
                            <div class="yk-bmi__history-meta">
                                <span>{{ $when }}</span>
                                <span>{{ $item->bmi_category_label ?: '—' }}</span>
                            </div>
                            <div class="yk-bmi__history-metrics">
                                <div class="yk-bmi__history-metric">
                                    <strong>{{ number_format((float) $item->bmi_display, 1) }}</strong>
                                    <span>BMI</span>
                                </div>
                                <div class="yk-bmi__history-metric">
                                    <strong>{{ $item->bmr_kcal ? number_format($item->bmr_kcal) : '—' }}</strong>
                                    <span>BMR</span>
                                </div>
                                <div class="yk-bmi__history-metric">
                                    <strong>{{ $item->tdee_kcal ? number_format($item->tdee_kcal) : '—' }}</strong>
                                    <span>TDEE</span>
                                </div>
                            </div>
                            <div class="yk-bmi__history-foot">
                                <p class="yk-bmi__history-activity">
                                    @if ($actEmoji)<span aria-hidden="true">{{ $actEmoji }}</span> @endif
                                    {{ $actLabel }}
                                    · ×{{ rtrim(rtrim(number_format((float) $item->activity_factor, 3, '.', ''), '0'), '.') }}
                                </p>
                                <a class="yk-bmi__history-link" href="{{ route('tools.bmi-bmr.history.show', $item) }}">View details</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($items->hasPages())
                    <nav class="yk-bmi__pages" aria-label="BMI history pages">
                        @if ($items->previousPageUrl())
                            <a class="yk-bmi__btn-secondary" href="{{ $items->previousPageUrl() }}">Newer</a>
                        @endif
                        <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                        @if ($items->nextPageUrl())
                            <a class="yk-bmi__btn-secondary" href="{{ $items->nextPageUrl() }}">Older</a>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
