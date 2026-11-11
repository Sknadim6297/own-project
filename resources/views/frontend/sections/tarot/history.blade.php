@extends('layouts.layout')

@section('title', 'My Readings | Tarot Reflection')
@section('meta_description', 'Reopen your saved YuktiOne Tarot readings.')
@section('body_class', 'yk-tarot-body')

@section('content')
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap yk-tarot__wrap--wide">
        <div class="yk-tarot__toolbar">
            @include('frontend.sections.partials.tool-back-to-tools')
            <a class="yk-tarot__history-link" href="{{ route('tools.tarot.intro') }}">New reading</a>
        </div>

        <p class="yk-tarot__badge">
            @include('frontend.sections.tarot.partials.emoji', ['emoji' => '📖'])
            Saved
        </p>
        <h1>My Readings</h1>
        <p class="yk-tarot__lead">Reopen a reading exactly as it was saved — cards and insights stay fixed.</p>

        @if (session('status'))
            <p class="yk-tarot__success" role="status">{{ session('status') }}</p>
        @endif

        @if ($readings->isEmpty())
            <section class="yk-tarot__empty">
                <p>
                    @include('frontend.sections.tarot.partials.emoji', ['emoji' => '🔮'])
                    No saved readings yet.
                </p>
                <a class="yk-tarot__btn" href="{{ route('tools.tarot.intro') }}">Start a Reading</a>
            </section>
        @else
            <ul class="yk-tarot__history">
                @foreach ($readings as $item)
                    @php $ui = \App\Support\Tarot\TarotUi::categoryPresentation($item->category); @endphp
                    <li class="yk-tarot__history-card">
                        <div>
                            <p class="yk-tarot__history-date">{{ optional($item->created_at)->timezone('Asia/Kolkata')->format('d M Y · g:i A') }}</p>
                            <h2>
                                <span class="yk-tarot__emoji" aria-hidden="true">{{ $ui['emoji'] }}</span>
                                {{ \App\Support\Tarot\TarotCatalog::categoryLabel($item->category) }}
                            </h2>
                            <p class="yk-tarot__history-q">“{{ \Illuminate\Support\Str::limit($item->question, 110) }}”</p>
                            <p class="yk-tarot__history-meta">
                                {{ \App\Support\Tarot\TarotCatalog::spreadLabel($item->spread_type) }}
                                · {{ $item->cards_count }} {{ \Illuminate\Support\Str::plural('card', $item->cards_count) }}
                            </p>
                        </div>
                        <a class="yk-tarot__btn yk-tarot__btn--compact" href="{{ route('tools.tarot.history.show', $item) }}">Open Reading</a>
                    </li>
                @endforeach
            </ul>

            <div class="yk-tarot__pager">
                {{ $readings->links() }}
            </div>
        @endif
    </div>
</main>
@endsection
