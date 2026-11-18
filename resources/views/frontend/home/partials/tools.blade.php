@php
    $spotlight = \App\Support\Tools\ToolsSpotlight::cards(12);
@endphp

<section class="yk-tools-strip yk-tools-strip--rail" aria-labelledby="yk-tools-strip-title" data-tools-carousel>
    <div class="yk-tools-strip__shell">
        <header class="yk-tools-strip__head">
            <h2 class="yk-tools-strip__title" id="yk-tools-strip-title">
                Try <span>Our Tools</span>
            </h2>
            <div class="yk-tools-strip__actions">
                <div class="yk-tools-strip__nav">
                    <button class="yk-tools-strip__nav-btn" type="button" data-tools-prev aria-label="Previous tools" disabled>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 6 9 12l6 6"></path></svg>
                    </button>
                    <button class="yk-tools-strip__nav-btn" type="button" data-tools-next aria-label="Next tools">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"></path></svg>
                    </button>
                </div>
                <a class="yk-tools-strip__all" href="{{ route('tools.index') }}">View All Tools <span aria-hidden="true">→</span></a>
            </div>
        </header>

        @if ($spotlight === [])
            <p class="yk-home-empty-state">Tools will appear here when they are published.</p>
        @else
            <div class="yk-tools-strip__viewport">
                <ul class="yk-tools-strip__track" data-tools-track>
                    @foreach ($spotlight as $card)
                        <li>
                            @include('frontend.sections.partials.tool-tile', [
                                'label' => $card['label'],
                                'url' => $card['url'],
                                'icon' => $card['icon'],
                                'slug' => $card['slug'] ?? '',
                            ])
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
