@extends('layouts.layout')

@section('title', 'My BMI/BMR History | YuktiOne')
@section('meta_description', 'Private history of saved BMI and BMR calculations.')
@section('body_class', 'yk-bmi-body')

@section('content')
<main class="yk-bmi">
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card" aria-labelledby="bmi-history-heading">
            <p class="yk-bmi__badge">Private</p>
            <h1 id="bmi-history-heading">My BMI/BMR History</h1>
            <p class="yk-bmi__lead">Saved calculations stay on your YuktiOne profile. You can delete them at any time.</p>

            @if (session('status'))
                <p class="yk-bmi__lead">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-bmi__error">{{ $errors->first() }}</p>
            @endif

            <div class="yk-bmi__actions" style="margin-top:0;">
                <a class="yk-bmi__btn" href="{{ route('tools.bmi-bmr') }}">New calculation</a>
            </div>

            @if ($items->isEmpty())
                <p class="yk-bmi__lead" style="margin-top:1.2rem;">No saved calculations yet.</p>
            @else
                <ul class="yk-bmi__history">
                    @foreach ($items as $item)
                        @php $when = optional($item->calculated_at)->timezone($timezone)?->format('d M Y, g:i A'); @endphp
                        <li>
                            <a href="{{ route('tools.bmi-bmr.history.show', $item) }}">
                                <strong>BMI {{ number_format((float) $item->bmi_display, 1) }}</strong>
                                @if ($item->bmi_category_label) · {{ $item->bmi_category_label }} @endif
                                @if ($item->tdee_kcal) · TDEE {{ number_format($item->tdee_kcal) }} kcal @endif
                                <br><small>{{ $when }} · Asia/Kolkata</small>
                            </a>
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
