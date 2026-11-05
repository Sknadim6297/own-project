@extends('layouts.layout')

@section('title', ($editing ? 'Edit gratitude entry' : 'Gratitude Journal').' | YuktiOne')
@section('meta_description', 'Pause. Reflect. Appreciate. A private three-card gratitude journal.')
@section('body_class', 'yk-gratitude-body')

@section('content')
<main class="yk-gratitude">
    <div class="yk-gratitude__wrap">
        <a class="yk-gratitude__back" href="{{ $backUrl }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-gratitude__closed" aria-labelledby="gratitude-closed-heading">
                <p class="yk-gratitude__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1 id="gratitude-closed-heading">{{ $heading }}</h1>
                <p>{{ $editing ? 'Editing is unavailable right now.' : 'This journal is unavailable right now.' }}</p>
            </section>
        @else
            <form
                class="yk-gratitude__form"
                method="post"
                action="{{ $formAction }}"
                data-gratitude-form
                data-answer-max="{{ $answerMax }}"
                data-min-answered="{{ $minAnswered }}"
            >
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <p class="yk-gratitude__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1>{{ $heading }}</h1>
                <p class="yk-gratitude__lead">{{ $lead }}</p>
                @if ($showDate)
                    <p class="yk-gratitude__date"><time datetime="{{ now(\App\Services\GratitudeJournal::TIMEZONE)->toDateString() }}">{{ $today }}</time></p>
                    <h2 class="yk-gratitude__prompt">What are you grateful for today?</h2>
                    <p class="yk-gratitude__support">Fill in whichever cards you like. Skip the rest.</p>
                @endif
                <p class="yk-gratitude__private" role="status">Private · Only you can see this entry</p>

                @if ($errors->any())
                    <p class="yk-gratitude__error" role="alert" data-gratitude-error>{{ $errors->first() }}</p>
                @else
                    <p class="yk-gratitude__error" role="alert" data-gratitude-error hidden></p>
                @endif

                <div
                    class="yk-gratitude-carousel"
                    data-gratitude-carousel
                    aria-roledescription="carousel"
                    aria-label="Gratitude cards"
                >
                    <p class="yk-gratitude-carousel__status" data-gratitude-status aria-live="polite">Card 1 of {{ count($cards) }}</p>

                    <div class="yk-gratitude-carousel__viewport" data-gratitude-track-wrap>
                        <ul class="yk-gratitude-carousel__track" data-gratitude-track>
                            @foreach ($cards as $index => $card)
                                @php
                                    $value = old('answers.'.$card['key'], $answers[$card['key']] ?? '');
                                @endphp
                                <li
                                    class="yk-gratitude-card @if ($index === 0) is-active @endif"
                                    data-gratitude-card
                                    data-card-index="{{ $index }}"
                                    aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                                >
                                    <p class="yk-gratitude-card__eyebrow">Card {{ $index + 1 }} of {{ count($cards) }}</p>
                                    <h3 class="yk-gratitude-card__title">{{ $card['label'] }}</h3>
                                    @if ($card['hint'])
                                        <p class="yk-gratitude-card__hint">{{ $card['hint'] }}</p>
                                    @endif
                                    <label class="yk-gratitude-card__label" for="gratitude-answer-{{ $card['key'] }}">
                                        {{ $card['question'] ?? $card['label'] }}
                                    </label>
                                    <textarea
                                        id="gratitude-answer-{{ $card['key'] }}"
                                        class="yk-gratitude-card__input"
                                        name="answers[{{ $card['key'] }}]"
                                        rows="5"
                                        maxlength="{{ $answerMax }}"
                                        placeholder="Write a few words…"
                                        data-gratitude-answer
                                        data-card-key="{{ $card['key'] }}"
                                    >{{ $value }}</textarea>
                                    <p class="yk-gratitude-card__count">
                                        <span data-gratitude-count>{{ mb_strlen((string) $value) }}</span> / {{ number_format($answerMax) }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="yk-gratitude-dots" role="tablist" aria-label="Card progress">
                        @foreach ($cards as $index => $card)
                            <button
                                class="yk-gratitude-dot @if ($index === 0) is-active @endif"
                                type="button"
                                role="tab"
                                aria-label="Go to card {{ $index + 1 }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-gratitude-dot
                                data-card-index="{{ $index }}"
                            ></button>
                        @endforeach
                    </div>

                    <div class="yk-gratitude-nav">
                        <button class="yk-gratitude-nav__prev" type="button" data-gratitude-prev hidden>Previous</button>
                        <button class="yk-gratitude-nav__next" type="button" data-gratitude-next hidden>Next</button>
                        <button class="yk-gratitude-nav__skip" type="button" data-gratitude-skip>Skip</button>
                    </div>
                </div>

                <button class="yk-gratitude__save" type="submit" data-gratitude-save>{{ $submitLabel }}</button>
            </form>
        @endif
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-gratitude-form]');
    const carousel = document.querySelector('[data-gratitude-carousel]');
    if (!form || !carousel) return;

    const cards = Array.from(carousel.querySelectorAll('[data-gratitude-card]'));
    const dots = Array.from(carousel.querySelectorAll('[data-gratitude-dot]'));
    const track = carousel.querySelector('[data-gratitude-track]');
    const wrap = carousel.querySelector('[data-gratitude-track-wrap]');
    const status = carousel.querySelector('[data-gratitude-status]');
    const prevBtn = carousel.querySelector('[data-gratitude-prev]');
    const nextBtn = carousel.querySelector('[data-gratitude-next]');
    const skipBtn = carousel.querySelector('[data-gratitude-skip]');
    const error = form.querySelector('[data-gratitude-error]');
    const minAnswered = Number(form.dataset.minAnswered || 1);
    const answerMax = Number(form.dataset.answerMax || 1000);
    let index = 0;
    let touchStartX = null;

    const answerOf = (card) => {
        const field = card.querySelector('[data-gratitude-answer]');
        return field ? field.value.trim() : '';
    };

    const answeredCount = () => cards.reduce((total, card) => total + (answerOf(card) ? 1 : 0), 0);

    const syncCounts = () => {
        cards.forEach((card) => {
            const field = card.querySelector('[data-gratitude-answer]');
            const count = card.querySelector('[data-gratitude-count]');
            if (field && count) count.textContent = String(field.value.length);
        });
    };

    const show = (nextIndex) => {
        index = Math.max(0, Math.min(cards.length - 1, nextIndex));
        cards.forEach((card, i) => {
            const active = i === index;
            card.classList.toggle('is-active', active);
            card.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
        dots.forEach((dot, i) => {
            const active = i === index;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (track) {
            track.style.transform = `translateX(-${index * 100}%)`;
        }
        if (status) {
            status.textContent = `Card ${index + 1} of ${cards.length}`;
        }
        const hasAnswer = answerOf(cards[index]) !== '';
        if (prevBtn) prevBtn.hidden = index === 0;
        if (nextBtn) nextBtn.hidden = !(hasAnswer && index < cards.length - 1);
        if (skipBtn) skipBtn.hidden = hasAnswer || index >= cards.length - 1;
    };

    const go = (delta) => show(index + delta);

    cards.forEach((card) => {
        const field = card.querySelector('[data-gratitude-answer]');
        field?.addEventListener('input', () => {
            if (field.value.length > answerMax) {
                field.value = field.value.slice(0, answerMax);
            }
            syncCounts();
            show(index);
        });
    });

    prevBtn?.addEventListener('click', () => go(-1));
    nextBtn?.addEventListener('click', () => {
        if (answerOf(cards[index])) go(1);
    });
    skipBtn?.addEventListener('click', () => go(1));
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.cardIndex || 0));
        });
    });

    const onTouchStart = (event) => {
        touchStartX = event.changedTouches?.[0]?.clientX ?? null;
    };
    const onTouchEnd = (event) => {
        if (touchStartX === null) return;
        const endX = event.changedTouches?.[0]?.clientX ?? touchStartX;
        const delta = endX - touchStartX;
        touchStartX = null;
        if (Math.abs(delta) < 40) return;
        if (delta < 0) {
            if (answerOf(cards[index]) || index < cards.length - 1) go(1);
        } else if (index > 0) {
            go(-1);
        }
    };
    wrap?.addEventListener('touchstart', onTouchStart, { passive: true });
    wrap?.addEventListener('touchend', onTouchEnd, { passive: true });

    form.addEventListener('submit', (event) => {
        if (answeredCount() >= minAnswered) {
            if (error) {
                error.hidden = true;
                error.textContent = '';
            }
            return;
        }
        event.preventDefault();
        if (error) {
            error.hidden = false;
            error.textContent = 'Please fill in at least one card before saving.';
        }
        show(0);
        cards[0]?.querySelector('[data-gratitude-answer]')?.focus();
    });

    syncCounts();
    show(0);
})();
</script>
@endpush
