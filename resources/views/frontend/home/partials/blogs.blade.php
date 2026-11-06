<section class="yk-home-blogs" aria-labelledby="yk-home-blogs-title">
    <div class="yk-home-container">
        <header class="yk-home-section-heading">
            <p class="yk-home-eyebrow">Insights &amp; Stories</p>
            <h2 id="yk-home-blogs-title">Explore Our Latest Blogs</h2>
            <p>Ideas, perspectives and practical guidance from the {{ config('yuktione.name', 'YuktiOne') }} community.</p>
        </header>

        @if ($blogPosts->isNotEmpty())
            <div class="yk-home-blog-grid">
                @foreach ($blogPosts as $blogPost)
                    @php
                        $blog = $blogPost->blog;
                        $blogTitle = $blog?->title;
                        $blogUrl = $blogTitle ? route('blog.show', \Illuminate\Support\Str::slug($blogTitle)) : route('blog.index');
                        $blogImagePath = collect([$blogPost->image, $blog?->image])
                            ->filter(fn ($path) => filled($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                            ->first();
                        $blogExcerpt = \Illuminate\Support\Str::limit(
                            trim(strip_tags($blog?->description_short ?: $blogPost->content)),
                            150
                        );
                        $blogAuthor = $blogPost->author_name ?: $blog?->created_by;
                    @endphp

                    <article class="yk-home-blog-card">
                        <a class="yk-home-blog-card__image" href="{{ $blogUrl }}" aria-label="Read {{ $blogTitle }}">
                            @if ($blogImagePath)
                                <img
                                    src="{{ '/storage/' . ltrim($blogImagePath, '/') }}"
                                    alt="{{ $blogTitle }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                                <span class="yk-home-blog-placeholder" aria-hidden="true" data-blog-image-fallback hidden>
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <path d="M13 10h38a4 4 0 0 1 4 4v36a4 4 0 0 1-4 4H13a4 4 0 0 1-4-4V14a4 4 0 0 1 4-4Z"/>
                                        <path d="M17 43 28 31l8 8 6-6 9 10"/>
                                        <circle cx="23" cy="22" r="4"/>
                                    </svg>
                                    <span>{{ config('yuktione.name', 'YuktiOne') }} Journal</span>
                                </span>
                            @else
                                <span class="yk-home-blog-placeholder" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" focusable="false">
                                        <path d="M13 10h38a4 4 0 0 1 4 4v36a4 4 0 0 1-4 4H13a4 4 0 0 1-4-4V14a4 4 0 0 1 4-4Z"/>
                                        <path d="M17 43 28 31l8 8 6-6 9 10"/>
                                        <circle cx="23" cy="22" r="4"/>
                                    </svg>
                                    <span>{{ config('yuktione.name', 'YuktiOne') }} Journal</span>
                                </span>
                            @endif
                        </a>

                        <div class="yk-home-blog-card__body">
                            @if ($blogPost->category || $blogPost->published_at)
                                <div class="yk-home-blog-card__meta">
                                    @if ($blogPost->category)
                                        <span class="yk-home-blog-card__category">{{ $blogPost->category }}</span>
                                    @endif
                                    @if ($blogPost->published_at)
                                        <time datetime="{{ $blogPost->published_at->toDateString() }}">
                                            {{ $blogPost->published_at->format('M j, Y') }}
                                        </time>
                                    @endif
                                </div>
                            @endif

                            <h3><a href="{{ $blogUrl }}">{{ $blogTitle }}</a></h3>
                            @if ($blogExcerpt !== '')
                                <p class="yk-home-blog-card__excerpt">{{ $blogExcerpt }}</p>
                            @endif

                            <div class="yk-home-blog-card__footer">
                                @if ($blogAuthor)
                                    <span class="yk-home-blog-card__author">{{ $blogAuthor }}</span>
                                @else
                                    <span aria-hidden="true"></span>
                                @endif
                                <a class="yk-home-blog-card__read-link" href="{{ $blogUrl }}" aria-label="Read article: {{ $blogTitle }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M4 12h15M13 6l6 6-6 6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="yk-home-empty-state">New stories are on their way.</p>
        @endif

        <div class="yk-home-section-action">
            <a class="yk-home-button yk-home-button--outline" href="{{ route('blog.index') }}">View All Blogs</a>
        </div>
    </div>
</section>
