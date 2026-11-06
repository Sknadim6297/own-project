@php
    $tones = ['lavender', 'mint', 'blush', 'blue', 'cream', 'mint'];
    $accents = ['purple', 'teal', 'pink', 'purple', 'orange', 'teal'];
    $homeTools = \App\Models\Tool::query()
        ->publiclyAvailable()
        ->orderBy('display_order')
        ->orderBy('id')
        ->take(6)
        ->get()
        ->filter(fn ($tool) => $tool->canLaunch())
        ->values();
@endphp

<section class="yk-tools" aria-labelledby="yk-tools-title">
    <div class="yk-tools__inner">
        <header class="yk-tools__head">
            <div class="yk-tools__copy">
                <p class="yk-tools__eyebrow">Tools for everyday clarity</p>
                <h2 class="yk-tools__title" id="yk-tools-title">
                    <span class="yk-tools__heading-navy">Simple tools.</span>
                    <span class="yk-tools__heading-lavender">Real insights.</span>
                </h2>
                <p class="yk-tools__lead">Explore our easy-to-use tools designed to bring more clarity, confidence and balance to your everyday life.</p>
            </div>
            <a class="yk-tools__all" href="{{ route('tools.index') }}">View All Tools <span aria-hidden="true">→</span></a>
            <svg class="yk-tools__leaves" viewBox="0 0 180 150" aria-hidden="true" focusable="false">
                <path d="M96 148c8-46 22-84 48-124" fill="none" stroke="#7d9a68" stroke-width="2.2" stroke-linecap="round"></path>
                <path d="M138 18c16 18 16 42 2 60-22-8-36-32-32-56 10-2 20-4 30-4Z" fill="#9d81db" opacity=".85"></path>
                <path d="M128 58c22 6 40 22 44 46-26 2-44-10-56-28 2-8 6-14 12-18Z" fill="#8aaf74"></path>
                <path d="M118 96c18 12 26 32 20 54-22-4-36-20-40-40 6-6 12-10 20-14Z" fill="#b7a4e4"></path>
                <path d="M108 128c12 10 14 26 6 40-16-8-24-22-20-36 4-2 8-3 14-4Z" fill="#7ea36a"></path>
            </svg>
        </header>

        @if ($homeTools->isEmpty())
            <p class="yk-home-empty-state">Tools will appear here when they are published.</p>
        @else
            <ul class="yk-tools__grid">
                @foreach ($homeTools as $tool)
                    @php
                        $tone = $tones[$loop->index % count($tones)];
                        $accent = $accents[$loop->index % count($accents)];
                        $href = route($tool->route_name);
                    @endphp
                    <li>
                        <a class="yk-tools__card yk-tools__card--{{ $tone }} yk-tools__card-link" href="{{ $href }}">
                            <div class="yk-tools__art" aria-hidden="true">
                                <svg viewBox="0 0 160 160" focusable="false">
                                    <circle cx="80" cy="82" r="54" fill="#EAF6F2"></circle>
                                    <circle cx="80" cy="82" r="36" fill="#fff" stroke="#316d61" stroke-width="2"></circle>
                                    <path d="M62 82h36M80 64v36" fill="none" stroke="#316d61" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <h3 class="yk-tools__name">{{ $tool->name }}</h3>
                            <p class="yk-tools__text">{{ \Illuminate\Support\Str::limit($tool->description ?: ('Open this tool on '.config('yuktione.name', 'YuktiOne').'.'), 90) }}</p>
                            <span class="yk-tools__cta yk-tools__cta--{{ $accent }}">
                                Open
                                <span class="yk-tools__arrow" aria-hidden="true">
                                    <svg viewBox="0 0 28 28" focusable="false">
                                        <circle cx="14" cy="14" r="12.2" fill="none" stroke="currentColor" stroke-width="1.4"></circle>
                                        <path d="M10.5 14h7M14.8 10.6 18.2 14l-3.4 3.4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
