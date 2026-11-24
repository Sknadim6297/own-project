@extends('professional.layout.layout')

@section('title', 'My Reviews | Yuktione')

@section('content')
<div class="mx-auto w-full max-w-[1600px] space-y-5">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Your reputation</p>
            <h1 class="mt-1 text-2xl font-semibold leading-tight text-brand-950">My Reviews</h1>
            <p class="mt-1 text-sm text-text-muted">See what customers say about your consultations.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-800">
            <span class="size-1.5 rounded-full bg-brand-accent" aria-hidden="true"></span>
            {{ $totalReviews }} {{ \Illuminate\Support\Str::plural('review', $totalReviews) }}
        </span>
    </header>

    <section class="grid gap-3 lg:grid-cols-[minmax(15rem,0.8fr)_minmax(0,1.7fr)]" aria-label="Review rating summary">
        <article class="flex min-h-36 flex-col justify-between rounded-panel border border-divider bg-white p-4 shadow-card sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-text-muted">Overall rating</p>
                    <p class="mt-2 text-4xl font-semibold leading-none tracking-tight text-brand-950">{{ number_format($averageRating, 1) }}</p>
                </div>
                <span class="rounded-control bg-brand-50 p-2 text-brand-700" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none">
                        <path d="m12 3 2.7 5.47 6.03.88-4.36 4.25 1.03 6.01L12 16.77l-5.4 2.84 1.03-6.01L3.27 9.35l6.03-.88L12 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                </span>
            </div>

            @if($totalReviews > 0)
                <div class="mt-3">
                    <div class="flex items-center gap-1 text-lg leading-none text-amber-500" role="img" aria-label="{{ number_format($averageRating, 1) }} out of 5 stars">
                        @for($i = 1; $i <= 5; $i++)
                            @if($averageRating >= $i)
                                <span aria-hidden="true" class="text-amber-500"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>
                            @elseif($averageRating >= ($i - 0.5))
                                <span aria-hidden="true" class="text-amber-400"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>
                            @else
                                <span aria-hidden="true" class="text-border"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>
                            @endif
                        @endfor
                    </div>
                    <p class="mt-1.5 text-xs text-text-muted">Based on {{ $totalReviews }} {{ \Illuminate\Support\Str::plural('review', $totalReviews) }}</p>
                </div>
            @else
                <div class="mt-3">
                    <div class="flex items-center gap-1 text-lg leading-none text-border" role="img" aria-label="No ratings yet">
                        @for($i = 1; $i <= 5; $i++)
                            <span aria-hidden="true" class="text-border"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>
                        @endfor
                    </div>
                    <p class="mt-1.5 text-xs font-medium text-text-muted">No ratings yet</p>
                </div>
            @endif
        </article>

        <article class="rounded-panel border border-divider bg-white p-4 shadow-card sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-brand-950">Rating breakdown</h2>
                    <p class="mt-0.5 text-[11px] text-text-muted">Distribution across all your reviews</p>
                </div>
                <span class="text-xs font-medium text-text-muted">{{ $totalReviews }} total</span>
            </div>

            <div class="mt-3 space-y-2">
                @for($i = 5; $i >= 1; $i--)
                    @php
                        $ratingCount = $ratingCounts[$i] ?? 0;
                        $percentage = $totalReviews > 0 ? ($ratingCount / $totalReviews) * 100 : 0;
                    @endphp
                    <div class="grid grid-cols-[2.25rem_minmax(0,1fr)_2.5rem] items-center gap-2.5 text-xs">
                        <span class="text-text-secondary">{{ $i }} star</span>
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-brand-50"
                            role="progressbar"
                            aria-label="{{ $i }} star reviews"
                            aria-valuenow="{{ number_format($percentage, 1, '.', '') }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div class="h-full rounded-full bg-brand-primary" style="width: {{ $percentage }}%"></div>
                        </div>
                        <span class="text-right tabular-nums text-text-muted">{{ $ratingCount }}</span>
                    </div>
                @endfor
            </div>
        </article>
    </section>

    <section class="rounded-panel border border-divider bg-white p-4 shadow-card sm:p-5" aria-label="Filter reviews">
        <form action="{{ route('professional.reviews.index') }}" method="GET" id="filter-form" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.8fr)_auto] lg:items-end">
            <div class="space-y-1.5">
                <label for="start_date" class="block text-xs font-medium text-text-primary">Date from</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="h-10 w-full rounded-control border border-border bg-white px-3 text-xs text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
            </div>
            <div class="space-y-1.5">
                <label for="end_date" class="block text-xs font-medium text-text-primary">Date to</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="h-10 w-full rounded-control border border-border bg-white px-3 text-xs text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
            </div>
            <div class="space-y-1.5">
                <label for="rating" class="block text-xs font-medium text-text-primary">Rating</label>
                <select
                    id="rating"
                    name="rating"
                    class="h-10 w-full rounded-control border border-border bg-white px-3 text-xs text-text-primary focus:border-brand-primary focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
                    <option value="">All ratings</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((string) request('rating') === (string) $i)>{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-control bg-brand-primary px-4 text-xs font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M3 4h14l-5.5 6.2v4.3L8.5 16v-5.8L3 4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('professional.reviews.index') }}" class="inline-flex h-10 flex-1 items-center justify-center rounded-control border border-border bg-white px-4 text-xs font-semibold text-text-secondary transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-panel border border-divider bg-white shadow-card" aria-labelledby="all-reviews-title">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-divider px-4 py-3.5 sm:px-5">
            <div>
                <h2 id="all-reviews-title" class="text-sm font-semibold text-brand-950">All Reviews</h2>
                <p class="mt-0.5 text-[11px] text-text-muted">Customer feedback for your consultations</p>
            </div>
            @if($totalReviews > 0)
                <span class="text-xs text-text-muted">{{ $reviews->total() }} {{ \Illuminate\Support\Str::plural('result', $reviews->total()) }}</span>
            @endif
        </div>

        @if($reviews->isEmpty())
            @if($totalReviews > 0)
                <div class="px-4 py-10 text-center sm:px-6">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none">
                            <circle cx="10.5" cy="10.5" r="6.75" stroke="currentColor" stroke-width="1.6"/>
                            <path d="m15.5 15.5 4.25 4.25" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3 class="mt-3 text-sm font-semibold text-brand-950">No matching reviews</h3>
                    <p class="mt-1 text-xs text-text-muted">Try adjusting your filters to see more results.</p>
                    <a href="{{ route('professional.reviews.index') }}" class="mt-3 inline-flex min-h-9 items-center justify-center rounded-control border border-border bg-white px-3 text-xs font-semibold text-brand-800 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
                        Reset filters
                    </a>
                </div>
            @else
                <div class="px-4 py-10 text-center sm:px-6">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none">
                            <path d="m12 3 2.7 5.47 6.03.88-4.36 4.25 1.03 6.01L12 16.77l-5.4 2.84 1.03-6.01L3.27 9.35l6.03-.88L12 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="mt-3 text-sm font-semibold text-brand-950">No Reviews Yet</h3>
                    <p class="mt-1 text-xs text-text-muted">Customer reviews will appear here once they're submitted.</p>
                </div>
            @endif
        @else
            <div class="divide-y divide-divider">
                @foreach($reviews as $review)
                    @php
                        $reviewerName = $review->user->name ?? 'Anonymous';
                        $reviewInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($reviewerName, 0, 1));
                        $reviewRating = (int) $review->rating;
                    @endphp
                    <article class="flex gap-3 px-4 py-4 sm:gap-4 sm:px-5">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-800" aria-hidden="true">
                            {{ $reviewInitial }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                                <div class="min-w-0">
                                    <h3 class="truncate text-xs font-semibold text-brand-950">{{ $reviewerName }}</h3>
                                    <time class="mt-0.5 block text-[11px] text-text-muted" datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->format('M d, Y') }}</time>
                                </div>
                                <div class="flex items-center gap-1" role="img" aria-label="{{ $reviewRating }} out of 5 stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-sm leading-none {{ $i <= $reviewRating ? 'text-amber-500' : 'text-border' }}" aria-hidden="true">
                                            @if ($i <= $reviewRating)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            @endif
                                        </span>
                                    @endfor
                                    <span class="ml-1 text-[11px] font-medium text-text-secondary">{{ $reviewRating }}.0</span>
                                </div>
                            </header>
                            @if(filled($review->review_text))
                                <p class="mt-2 whitespace-pre-line break-words text-xs leading-5 text-text-secondary">{{ $review->review_text }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if($reviews->hasPages())
                <div class="border-t border-divider px-4 py-3 sm:px-5">
                    {{ $reviews->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
