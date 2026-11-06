@php
    // Use Explore search chips — CMS service-detail slugs are often missing in QA/local.
    $heroTopics = [
        ['label' => 'Astrology', 'query' => 'Astrology'],
        ['label' => 'Relationships', 'query' => 'Relationships'],
        ['label' => 'Career', 'query' => 'Career'],
        ['label' => 'Mental Health', 'query' => 'Mental Health'],
        ['label' => 'Money', 'query' => 'Money'],
        ['label' => 'Wellness', 'query' => 'Wellness'],
    ];
@endphp

{{-- Poster: Pollianna Bonnett, Pexels License, https://www.pexels.com/photo/young-woman-posing-outside-and-facing-the-sun-with-eyes-closed-17418629/ --}}
<section class="yk-hero yk:overflow-x-clip yk:bg-[#FFFEFC] yk:px-4 yk:pb-8 yk:pt-5 yk:font-sans yk:text-[#0B2048] yk:sm:px-5 yk:md:px-6 yk:md:pt-7 yk:lg:px-8 yk:lg:pb-12 yk:xl:px-11 yk:xl:pt-8" aria-labelledby="yuktione-home-title">
    <div class="yk:mx-auto yk:grid yk:w-full yk:max-w-[1360px] yk:grid-cols-1 yk:items-stretch yk:gap-5 yk:md:grid-cols-2 yk:md:gap-5 yk:lg:grid-cols-[minmax(0,1.18fr)_minmax(0,1.08fr)_minmax(236px,0.76fr)] yk:lg:gap-4 yk:xl:gap-6">
        <div class="yk:flex yk:min-w-0 yk:flex-col yk:justify-center yk:md:col-span-2 yk:lg:col-span-1 yk:lg:py-2">
            <p class="yk:m-0 yk:text-[0.72rem] yk:font-semibold yk:uppercase yk:tracking-[0.16em] yk:text-[#6d7892]">Your journey starts here</p>
            <h1 class="yk:m-0 yk:mt-3 yk:text-[2.05rem] yk:font-semibold yk:leading-[1.08] yk:tracking-[-0.03em] yk:sm:text-[2.3rem] yk:md:text-[2.7rem] yk:lg:text-[2.05rem] yk:xl:text-[2.55rem] yk:2xl:text-[2.85rem]" id="yuktione-home-title">
                <span class="yk:block yk:text-[#0B2048]">Clarity today.</span>
                <span class="yk:mt-1 yk:block yk:text-[#9D81DB]">A brighter tomorrow.</span>
            </h1>
            <p class="yk:m-0 yk:mt-4 yk:max-w-[40rem] yk:text-base yk:leading-relaxed yk:text-[#42506a] yk:lg:max-w-[36ch] yk:lg:text-[0.98rem] yk:xl:text-[1.02rem]">Personalised tools, trusted experts and practical guidance for every area of your life.</p>

            <div class="yk-hero__search yk:relative yk:mt-6 yk:flex yk:h-14 yk:min-w-0 yk:items-center yk:gap-2 yk:rounded-full yk:border yk:border-[#E0E4EA] yk:bg-white yk:py-1.5 yk:pl-4 yk:pr-1.5">
                <svg class="yk:size-5 yk:shrink-0 yk:text-[#8b95ab]" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                    <path d="m16 16 4.2 4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                </svg>
                <label class="visually-hidden" for="serviceSearch">What are you looking for today?</label>
                <input class="yk:min-w-0 yk:flex-1 yk:border-0 yk:bg-transparent yk:text-[0.98rem] yk:text-[#0B2048] yk:outline-none yk:placeholder:text-[#8b95ab]" type="search" id="serviceSearch" placeholder="What are you looking for today?" autocomplete="off" enterkeyhint="search" data-search-endpoint="{{ route('search.services') }}" data-service-base="{{ url('/service') }}">
                <button class="yk:inline-flex yk:size-10 yk:shrink-0 yk:items-center yk:justify-center yk:rounded-full yk:border-0 yk:bg-[#316D61] yk:text-white yk:transition-colors yk:duration-150 yk:hover:bg-[#27574e]" type="button" id="searchButton" aria-label="Search">
                    <svg class="yk:size-5" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
                <div id="searchResults" class="yk-hero__results d-none" role="listbox" aria-label="Search results"></div>
            </div>

            <div class="yk:mt-4 yk:flex yk:flex-wrap yk:items-center yk:gap-2">
                <span class="yk:mr-1 yk:text-sm yk:font-medium yk:text-[#5c6b86]">Popular:</span>
                @foreach ($heroTopics as $topic)
                    @if (!empty($topic['url']))
                        <a class="yk-hero__chip" href="{{ $topic['url'] }}">{{ $topic['label'] }}</a>
                    @else
                        <button class="yk-hero__chip" type="button" data-hero-search="{{ $topic['query'] }}">{{ $topic['label'] }}</button>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="yk-hero__video yk:relative yk:min-h-[240px] yk:min-w-0 yk:overflow-hidden yk:rounded-[1.35rem] yk:aspect-[4/3] yk:md:aspect-[5/4] yk:lg:aspect-auto yk:lg:h-full yk:lg:min-h-[360px]" data-hero-video>
            <img class="yk-hero__poster yk:absolute yk:inset-0 yk:h-full yk:w-full yk:max-w-none yk:object-cover yk:object-[center_64%]" src="{{ asset('frontend/assets/img/yuktione-hero-poster.jpg') }}" alt="A woman with her eyes closed, face lifted toward warm sunlight" width="1400" height="2100" fetchpriority="high">
            <video class="yk-hero__player yk:absolute yk:inset-0 yk:hidden yk:h-full yk:w-full yk:max-w-none yk:object-cover" preload="none" playsinline>
                <source src="{{ asset('frontend/assets/video/hero-video.mp4') }}" type="video/mp4">
            </video>
            <p class="yk-hero__video-copy yk:pointer-events-none yk:absolute yk:left-4 yk:top-4 yk:z-[2] yk:m-0 yk:max-w-[12.5rem] yk:text-[1.2rem] yk:font-medium yk:leading-snug yk:text-white yk:sm:left-5 yk:sm:top-5 yk:sm:max-w-[14rem] yk:sm:text-[1.35rem] yk:xl:text-[1.45rem]">
                A calmer,<br>clearer, brighter<br>you — with YuktiOne
            </p>
            <button class="yk-hero__play yk:absolute yk:left-1/2 yk:top-1/2 yk:z-[2] yk:inline-flex yk:size-16 yk:-translate-x-1/2 yk:-translate-y-1/2 yk:items-center yk:justify-center yk:rounded-full yk:border yk:border-white/80 yk:bg-white/40 yk:text-[#0B2048] yk:backdrop-blur-md yk:xl:size-[4.6rem]" type="button" data-hero-play aria-label="Play the one-minute YuktiOne video">
                <svg class="yk:ml-1 yk:size-7" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M8.2 5.4v13.2L18.8 12 8.2 5.4Z" fill="currentColor"></path>
                </svg>
            </button>
            <button class="yk-hero__watch yk:absolute yk:bottom-4 yk:left-1/2 yk:z-[2] yk:max-w-[calc(100%-2rem)] yk:-translate-x-1/2 yk:rounded-full yk:border-0 yk:bg-[#1b2433]/80 yk:px-4 yk:py-2 yk:text-sm yk:font-medium yk:text-white" type="button" data-hero-play>
                Watch Our 1-Minute Video
            </button>
        </div>

        <aside class="yk-hero__benefits yk:relative yk:flex yk:min-h-[280px] yk:min-w-0 yk:flex-col yk:overflow-hidden yk:rounded-[1.35rem] yk:bg-[#F4F0FA] yk:px-5 yk:py-6 yk:sm:px-7 yk:lg:min-h-0 yk:lg:px-6 yk:lg:py-6 yk:xl:px-7" aria-label="Why join YuktiOne">
            <svg class="yk-hero__leaves yk:pointer-events-none yk:absolute yk:right-[-18px] yk:top-[-6px] yk:h-[86%] yk:w-[52%]" viewBox="0 0 220 380" aria-hidden="true" focusable="false">
                <path d="M118 360c12-78 28-150 62-268" fill="none" stroke="#6f9460" stroke-width="2.4" stroke-linecap="round"></path>
                <path d="M168 42c22 26 24 58 4 84-28-14-46-46-42-78 12-4 26-6 38-6Z" fill="#8aaf74"></path>
                <path d="M156 96c30 8 54 30 60 62-34 4-58-12-74-38 2-10 8-18 14-24Z" fill="#7ea36a"></path>
                <path d="M146 156c28 16 42 46 36 78-32-6-52-30-60-58 6-8 14-16 24-20Z" fill="#93b67c"></path>
                <path d="M136 220c24 20 32 52 18 82-30-12-48-40-48-70 8-6 18-10 30-12Z" fill="#7d9f68"></path>
                <path d="M124 286c18 24 16 54 2 80-24-16-36-44-32-70 8-4 18-8 30-10Z" fill="#8fb278"></path>
                <path d="M176 118c-6 22-2 40 12 54 8-20 6-38-12-54Z" fill="#6d945c"></path>
            </svg>
            <h2 class="yk-hero__script yk:relative yk:z-[1] yk:m-0 yk:max-w-[11rem] yk:pr-2 yk:text-[1.85rem] yk:leading-[1.2] yk:text-[#34386f] yk:xl:text-[2.05rem]">Smarter Choices<br>Happier You</h2>
            <ul class="yk:relative yk:z-[1] yk:m-0 yk:mt-5 yk:flex yk:list-none yk:flex-col yk:gap-3 yk:p-0">
                @foreach (['Practical guidance', 'Trusted experts', 'A supportive community'] as $benefit)
                    <li class="yk:flex yk:items-center yk:gap-2.5 yk:text-[0.98rem] yk:font-medium yk:text-[#0B2048]">
                        <svg class="yk:size-5 yk:shrink-0" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                            <circle cx="10" cy="10" r="10" fill="#316D61"></circle>
                            <path d="M5.8 10.2 8.5 12.9 14.2 7.2" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span>{{ $benefit }}</span>
                    </li>
                @endforeach
            </ul>
            <a class="yk:relative yk:z-[1] yk:mt-6 yk:inline-flex yk:w-fit yk:items-center yk:justify-center yk:gap-2 yk:rounded-full yk:bg-[#316D61] yk:px-5 yk:py-3 yk:text-[0.95rem] yk:font-semibold yk:text-white yk:no-underline yk:transition-colors yk:duration-150 yk:hover:bg-[#27574e] yk:lg:mt-auto" href="{{ route('register') }}">
                Join YuktiOne
                <span aria-hidden="true">→</span>
            </a>
        </aside>
    </div>
</section>
