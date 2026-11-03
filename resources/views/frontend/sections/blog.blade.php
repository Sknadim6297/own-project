@extends('layouts.layout')

@section('title', 'Blog | YuktiOne')
@section('meta_description', 'Expert articles on career, health, finance, and lifestyle from accepted professionals.')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ url('/blog') }}"
  },
  "name": "{{ config('yuktione.name') }} Blog",
  "description": "Explore expert articles on career, health, finance, and lifestyle.",
  "url": "{{ url('/blog') }}",
  "publisher": {
    "@type": "Organization",
    "name": "{{ config('yuktione.name') }}"
  }
}
</script>
@endsection

@section('content')
@php
    $heroHeading = optional($blogbanners->first())->heading;
    $heroLead = optional($blogbanners->first())->subheading;
@endphp
<x-yuktione.page
    class="yk-blog"
    title="{{ $heroHeading ?: 'Insights & guidance' }}"
    lead="{{ $heroLead ?: 'Articles from accepted professionals across career, wellness, lifestyle, and more.' }}"
    crumb="Blog"
>
    <form class="yk-doc__search" action="{{ route('blog.index') }}" method="get" role="search">
        @if ($category)
            <input type="hidden" name="category" value="{{ $category }}">
        @endif
        <label class="visually-hidden" for="blog-search">Search blogs or categories</label>
        <input id="blog-search" type="search" name="search" value="{{ $search }}" placeholder="Search blogs or categories..." maxlength="100" autocomplete="off">
        <button class="yk-doc__button" type="submit">Search</button>
    </form>

    <div class="yk-blog__layout">
        <aside class="yk-blog__aside" aria-label="Blog filters">
            <div class="yk-blog__panel">
                <h2>Latest posts</h2>
                <ul class="yk-blog__latest">
                    @forelse ($latestBlogs as $latestBlog)
                        @php $latestSlug = \Illuminate\Support\Str::slug($latestBlog->blog->title ?? ''); @endphp
                        <li>
                            <a href="{{ route('blog.show', $latestSlug) }}">
                                <img src="{{ asset('storage/' . $latestBlog->image) }}" alt="" width="64" height="64" loading="lazy">
                            </a>
                            <div>
                                <small>{{ $latestBlog->category }} · {{ $latestBlog->created_at->format('d M Y') }}</small>
                                <a href="{{ route('blog.show', $latestSlug) }}">{{ \Illuminate\Support\Str::limit($latestBlog->blog->title ?? '', 60) }}</a>
                            </div>
                        </li>
                    @empty
                        <li><p class="yk-doc__lead">No recent posts yet.</p></li>
                    @endforelse
                </ul>
            </div>

            <div class="yk-blog__panel">
                <h2>Categories</h2>
                <ul class="yk-blog__cats">
                    <li>
                        <a href="{{ route('blog.index', array_filter(['search' => $search])) }}" @class(['is-active' => blank($category)])>
                            All
                        </a>
                    </li>
                    @foreach ($categoryCounts as $cat)
                        <li>
                            <a href="{{ route('blog.index', array_filter(['category' => $cat->category, 'search' => $search])) }}" @class(['is-active' => $category === $cat->category])>
                                {{ $cat->category }}
                                <small>({{ $cat->post_count }})</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($categoryCounts->isNotEmpty())
                <div class="yk-blog__panel">
                    <h2>Popular tags</h2>
                    <ul class="yk-blog__tags">
                        @foreach ($categoryCounts as $cat)
                            <li>
                                <a href="{{ route('blog.index', ['category' => $cat->category]) }}" @class(['is-active' => $category === $cat->category])>
                                    {{ $cat->category }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>

        <section class="yk-blog__main" aria-label="Blog posts">
            @if ($blogPosts->isEmpty())
                <div class="yk-doc__empty">
                    <h2>No posts found</h2>
                    <p>Try another search, or <a href="{{ route('blog.index') }}">browse all posts</a>.</p>
                </div>
            @else
                <ul class="yk-blog__grid">
                    @foreach ($blogPosts as $blogPost)
                        @php $slug = \Illuminate\Support\Str::slug($blogPost->blog->title ?? ''); @endphp
                        <li>
                            <article class="yk-blog__card">
                                <a class="yk-blog__card-media" href="{{ route('blog.show', $slug) }}">
                                    <img src="{{ asset('storage/' . $blogPost->image) }}" alt="" width="640" height="400" loading="lazy" decoding="async">
                                </a>
                                <div class="yk-blog__card-body">
                                    <small>{{ $blogPost->category }} · {{ $blogPost->created_at->format('d M Y') }}</small>
                                    <h2><a href="{{ route('blog.show', $slug) }}">{{ $blogPost->blog->title }}</a></h2>
                                    <p>{{ \Illuminate\Support\Str::words(strip_tags($blogPost->content ?? ''), 28, '...') }}</p>
                                    <div class="yk-blog__meta">
                                        @if ($blogPost->author_avatar)
                                            <img src="{{ asset('storage/' . $blogPost->author_avatar) }}" alt="" width="28" height="28" loading="lazy">
                                        @endif
                                        <span>{{ $blogPost->author_name }}</span>
                                        <span>{{ $blogPost->comment_count }} comments</span>
                                    </div>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>

                @if ($blogPosts->hasPages())
                    <nav class="yk-blog__pages" aria-label="Blog pages">
                        @if ($blogPosts->onFirstPage())
                            <span aria-disabled="true">Previous</span>
                        @else
                            <a href="{{ $blogPosts->appends(request()->query())->previousPageUrl() }}">Previous</a>
                        @endif
                        <span>Page {{ $blogPosts->currentPage() }} of {{ $blogPosts->lastPage() }}</span>
                        @if ($blogPosts->hasMorePages())
                            <a href="{{ $blogPosts->appends(request()->query())->nextPageUrl() }}">Next</a>
                        @else
                            <span aria-disabled="true">Next</span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</x-yuktione.page>
@endsection
