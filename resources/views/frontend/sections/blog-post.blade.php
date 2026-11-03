@extends('layouts.layout')

@section('meta')
@php
    $hasCustomMetaTitle = filled($relatedBlog->meta_title ?? null);
    $metaTitle = $relatedBlog->meta_title ?? $relatedBlog->title;
    $metaDescription = $relatedBlog->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($blogPost->content ?? ''), 150);
    $pageTitle = $hasCustomMetaTitle
        ? $metaTitle . ' | ' . config('yuktione.name') . ' Blog'
        : ($relatedBlog->title ?? 'Blog') . ' | ' . config('yuktione.name') . ' Blog';
    $blogUrl = route('blog.show', \Illuminate\Support\Str::slug($relatedBlog->title ?? ''));
@endphp
<meta name="description" content="{{ $metaDescription }}">
<meta name="keywords" content="{{ $blogPost->category }}, {{ $relatedBlog->title }}, blog, {{ config('yuktione.name') }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:site_name" content="{{ config('yuktione.name') }}">
<meta property="og:url" content="{{ $blogUrl }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:type" content="article">
<meta property="og:image" content="{{ asset('storage/' . $blogPost->image) }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ asset('storage/' . $blogPost->image) }}">
<title>{{ $pageTitle }}</title>
@endsection

@section('content')
@php
    $shareText = ($relatedBlog->title ?? '') . ' - ' . $blogUrl;
@endphp
<x-yuktione.page
    class="yk-blog yk-blog__post"
    title="{{ $relatedBlog->title }}"
    lead="{{ $blogPost->category }} · {{ $blogPost->author_name }}"
    crumb="Blog"
>
    <div class="yk-blog__layout">
        <aside class="yk-blog__aside" aria-label="Related reading">
            <div class="yk-blog__panel">
                <h2>Latest posts</h2>
                <ul class="yk-blog__latest">
                    @foreach ($latestBlogs as $latestBlog)
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
                    @endforeach
                </ul>
            </div>
            <div class="yk-blog__panel">
                <h2>Categories</h2>
                <ul class="yk-blog__cats">
                    @foreach ($categoryCounts as $cat)
                        <li>
                            <a href="{{ route('blog.index', ['category' => $cat->category]) }}">
                                {{ $cat->category }}
                                <small>({{ $cat->post_count }})</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <article class="yk-blog__main yk-doc__article">
            @if ($blogPost->image)
                <figure class="yk-blog__post-hero">
                    <img src="{{ asset('storage/' . $blogPost->image) }}" alt="" width="960" height="540" loading="eager" decoding="async">
                </figure>
            @endif

            <ul class="yk-blog__post-meta">
                <li>{{ $blogPost->category }}</li>
                @if ($blogPost->published_at)
                    <li>{{ $blogPost->published_at }}</li>
                @endif
                <li>{{ $blogPost->author_name }}</li>
            </ul>

            <div class="yk-doc__share" aria-label="Share this post">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($blogUrl) }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($blogUrl) }}&text={{ urlencode($relatedBlog->title) }}" target="_blank" rel="noopener noreferrer">X</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($blogUrl) }}" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                <a href="mailto:?subject={{ urlencode($relatedBlog->title) }}&body={{ urlencode('Check out this blog post: ' . $blogUrl) }}">Email</a>
            </div>

            <div class="yk-blog__content">
                {!! $blogPost->content !!}
            </div>

            <section class="yk-blog__comments" aria-labelledby="blog-comments-heading">
                <h2 id="blog-comments-heading">Comments</h2>
                @if ($comments->isEmpty())
                    <p class="yk-doc__lead">No comments yet. Be the first to share a thought.</p>
                @else
                    <ul class="yk-blog__comment-list">
                        @foreach ($comments as $comment)
                            <li>
                                <strong>{{ $comment->name }}</strong>
                                <small> · {{ $comment->created_at->format('d/m/Y') }}</small>
                                <p>{{ $comment->comment }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="yk-blog__comment-form" aria-labelledby="leave-comment-heading">
                <h2 id="leave-comment-heading">Leave a comment</h2>
                <form class="yk-doc__form" action="{{ route('blog.comment.store', $blogPost->id) }}" method="post">
                    @csrf
                    <div class="yk-blog__fields">
                        <label class="visually-hidden" for="name2">Name</label>
                        <input id="name2" type="text" name="name" placeholder="Name" required maxlength="255" autocomplete="name">
                        <label class="visually-hidden" for="email2">Email</label>
                        <input id="email2" type="email" name="email" placeholder="Email" required maxlength="255" autocomplete="email">
                        <label class="visually-hidden" for="mobile2">Mobile number</label>
                        <input id="mobile2" type="tel" name="mobile" placeholder="Mobile number" pattern="[0-9]{10,15}" maxlength="15" required autocomplete="tel">
                    </div>
                    <label class="visually-hidden" for="comments2">Comment</label>
                    <textarea id="comments2" name="comment" rows="6" placeholder="Comment" required maxlength="5000"></textarea>
                    <button class="yk-doc__button" type="submit">Submit</button>
                </form>
            </section>
        </article>
    </div>
</x-yuktione.page>
@endsection
