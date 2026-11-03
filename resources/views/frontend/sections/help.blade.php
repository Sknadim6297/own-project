@extends('layouts.layout')

@section('title', 'Help and Support | YuktiOne')
@section('meta_description', 'Search questions and useful articles about bookings, payments, programmes, and your account.')

@section('content')
<x-yuktione.page
    title="Help and support"
    lead="Search questions or useful articles."
    crumb="Help"
>
    <form class="yk-doc__search" action="{{ route('help.index') }}" method="get" role="search">
        <label class="visually-hidden" for="help-search">What are you looking for?</label>
        <input id="help-search" type="search" name="search" value="{{ request('search') }}" placeholder="What are you looking for?" maxlength="120">
        <button class="yk-doc__button" type="submit">Search</button>
    </form>

    <section class="yk-doc__topics" aria-labelledby="help-topics">
        <h2 id="help-topics">Select a topic</h2>
        <div class="yk-doc__grid cols-topics">
            @foreach (App\Models\Help::CATEGORIES as $category)
                <a class="yk-doc__card" href="{{ route('help.index', ['category' => $category]) }}" data-help-topic>
                    <h3>{{ $category === 'Events' ? 'Programmes' : $category }}</h3>
                    <p>{{ $helps->where('category', $category)->count() }} articles available</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="yk-doc__topics" aria-labelledby="help-faqs">
        <h2 id="help-faqs">{{ request('category') ? (request('category') === 'Events' ? 'Programmes' : request('category')).' FAQs' : 'All FAQs' }}</h2>
        @if ($helps->isEmpty())
            <p class="yk-doc__empty">No FAQs found{{ request('search') ? ' for “'.request('search').'”' : '' }}.</p>
        @else
            <div class="yk-doc__faq">
                @foreach ($helps as $help)
                    <details>
                        <summary>{{ $help->question }}</summary>
                        <p>{!! nl2br(e($help->answer)) !!}</p>
                    </details>
                @endforeach
            </div>
        @endif
    </section>
</x-yuktione.page>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.yk-doc__search');
    const input = form?.querySelector('input[name="search"]');
    form?.addEventListener('submit', function (event) {
        if (!input.value.trim()) {
            event.preventDefault();
        }
    });

    document.querySelectorAll('[data-help-topic]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            const current = new URLSearchParams(window.location.search).get('category');
            const next = new URL(link.href).searchParams.get('category');
            if (current && current === next) {
                event.preventDefault();
                window.location.href = @json(route('help.index'));
            }
        });
    });
});
</script>
@endpush
