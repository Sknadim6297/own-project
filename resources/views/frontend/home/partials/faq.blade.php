<section class="yk-home-faq" aria-labelledby="yk-home-faq-title" data-home-faq>
    <div class="yk-home-container yk-home-faq__layout">
        <header class="yk-home-section-heading">
            <p class="yk-home-eyebrow">Have Questions?</p>
            <h2 id="yk-home-faq-title">Frequently Asked Questions</h2>
            <p>Find answers to common questions about getting started with {{ config('yuktione.name', 'YuktiOne') }}.</p>
            <a class="yk-home-faq__more-link" href="{{ route('help.index') }}">More FAQs</a>
        </header>

        @if ($faqs->isNotEmpty())
            @php
                $brand = config('yuktione.name', 'YuktiOne');
                $brandizeFaq = static fn (?string $text): string => str_ireplace(['Tazen', 'Yuktione'], $brand, (string) $text);
            @endphp
            <div class="yk-home-faq__list">
                @foreach ($faqs as $faq)
                    <article class="yk-home-faq__item">
                        <h3 class="yk-home-faq__question">
                            <button
                                class="yk-home-faq__toggle"
                                type="button"
                                id="home-faq-question-{{ $faq->id }}"
                                aria-expanded="false"
                                aria-controls="home-faq-answer-{{ $faq->id }}"
                                data-home-faq-toggle
                            >
                                <span>{{ $brandizeFaq($faq->question) }}</span>
                                <span class="yk-home-faq__icon" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div
                            class="yk-home-faq__panel"
                            id="home-faq-answer-{{ $faq->id }}"
                            role="region"
                            aria-labelledby="home-faq-question-{{ $faq->id }}"
                            aria-hidden="true"
                            inert
                            data-home-faq-panel
                        >
                            <div class="yk-home-faq__answer">{{ $brandizeFaq($faq->answer) }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="yk-home-empty-state">There are no FAQs available right now.</p>
        @endif
    </div>
</section>
