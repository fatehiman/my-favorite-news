@extends('layouts.app')

@section('title', 'Briefing')

@php
    // Base query params for the current view, without the Unread/Important toggles —
    // used so those two toggles combine with whichever tab (All/category/Favorites)
    // and with each other, instead of replacing one another.
    $baseParams = array_filter([
        'category' => $activeCategory,
        'favorites' => $showFavoritesOnly ? 1 : null,
    ]);
    $toggleParams = array_filter([
        'important' => $showImportantOnly ? 1 : null,
        'unread' => $showUnreadOnly ? 1 : null,
    ]);
    $importantToggleParams = $baseParams
        + ($showImportantOnly ? [] : ['important' => 1])
        + ($showUnreadOnly ? ['unread' => 1] : []);
    $unreadToggleParams = $baseParams
        + ($showImportantOnly ? ['important' => 1] : [])
        + ($showUnreadOnly ? [] : ['unread' => 1]);
@endphp

@section('content')
<div class="flex items-center justify-between mb-3">
    <div class="text-xs text-gray-500">
        @if ($lastFetchedAt)
            Last fetched {{ $lastFetchedAt->diffForHumans() }}
        @else
            Never fetched yet
        @endif
    </div>
    <form method="POST" action="{{ route('articles.fetch-now') }}">
        @csrf
        <button type="submit" class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-full">
            ⟳ Fetch now
        </button>
    </form>
</div>

<div class="flex items-center justify-between gap-2 mb-4">
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('articles.index', $toggleParams) }}"
           class="px-3 py-1 rounded-full {{ !$activeCategory && !$showFavoritesOnly ? 'bg-gray-900 text-white' : 'bg-white border' }}">
            All
        </a>
        @foreach ($categories as $category)
            @php $catParams = ['category' => $category] + ($showImportantOnly ? ['important' => 1] : []) + ($showUnreadOnly ? ['unread' => 1] : []); @endphp
            <a href="{{ route('articles.index', $catParams) }}"
               class="px-3 py-1 rounded-full capitalize {{ $activeCategory === $category ? 'bg-gray-900 text-white' : 'bg-white border' }}">
                {{ $category }}
            </a>
        @endforeach
        @php $favParams = ['favorites' => 1] + ($showImportantOnly ? ['important' => 1] : []) + ($showUnreadOnly ? ['unread' => 1] : []); @endphp
        <a href="{{ route('articles.index', $favParams) }}"
           class="px-3 py-1 rounded-full {{ $showFavoritesOnly ? 'bg-rose-500 text-white' : 'bg-white border' }}">
            ♥ Favorites
        </a>
    </div>

    <div class="shrink-0 flex items-center gap-1.5">
        <a href="{{ route('articles.index', $unreadToggleParams) }}"
           title="Show only unread stories, combined with the tab on the left"
           class="px-2.5 py-1 rounded-full text-sm border {{ $showUnreadOnly ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-500' }}">
            ●
        </a>
        <a href="{{ route('articles.index', $importantToggleParams) }}"
           title="Show only important stories, combined with the tab on the left"
           class="px-2.5 py-1 rounded-full text-sm border {{ $showImportantOnly ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-500' }}">
            ⭐
        </a>
    </div>
</div>

@if ($showFavoritesOnly && empty($includedTags))
    <p class="text-sm text-gray-500 bg-white rounded-lg p-4 mb-3">
        No included tags yet — Favorites shows nothing until you add one.
        Click "+" on a tag below on any article, or go to
        <a href="{{ route('tags.index') }}" class="text-blue-600 hover:underline">Tags</a>.
    </p>
@endif

<div class="space-y-3">
    @forelse ($articles as $article)
        <div id="card-{{ $article->id }}" class="bg-white rounded-lg shadow-sm p-4 {{ $article->is_read ? 'opacity-60' : '' }}">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <span class="capitalize font-medium">{{ $article->feed->category }}</span>
                <span>&middot;</span>
                <span>{{ $article->feed->name }}</span>
                <span>&middot;</span>
                <span>{{ $article->published_at?->diffForHumans() ?? $article->created_at->diffForHumans() }}</span>
                @if ($article->cluster?->is_important)
                    <span class="ml-auto bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium cursor-help"
                          title="Also reported by: {{ $article->cluster->articles->pluck('feed.name')->unique()->implode(', ') }}">
                        ⭐ {{ $article->cluster->sources_count }} sources
                    </span>
                @endif
            </div>

            <div class="flex items-start justify-between gap-2">
                <a id="title-{{ $article->id }}" href="{{ $article->url }}" target="_blank" rel="noopener"
                   onclick="markReadAjax({{ $article->id }})"
                   class="font-medium text-gray-900 hover:underline">
                    {{ $article->title }}
                </a>
                <div class="shrink-0 flex items-center gap-1">
                    <button type="button" onclick="translateCard({{ $article->id }}, this)"
                            title="Translate to Persian"
                            class="text-xs text-gray-500 border rounded-full w-7 h-7 flex items-center justify-center hover:bg-gray-100">
                        🌐
                    </button>
                    <button type="button" onclick="openArticle({{ $article->id }}, {{ Illuminate\Support\Js::from($article->title) }})"
                            class="text-xs text-gray-500 border rounded-full px-2 py-1 hover:bg-gray-100">
                        Read here
                    </button>
                </div>
            </div>

            @if ($article->description)
                <p id="desc-{{ $article->id }}" class="text-sm text-gray-600 mt-1">{{ Str::limit($article->description, 180) }}</p>
            @endif

            @if ($article->all_tags->isNotEmpty())
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach ($article->all_tags as $tag)
                        @php
                            $isIncluded = in_array($tag->name, $includedTags);
                            $isExcluded = in_array($tag->name, $excludedTags);
                        @endphp
                        <span data-tag-name="{{ $tag->name }}" class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                            {{ $isIncluded ? 'bg-green-100 text-green-700' : ($isExcluded ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $tag->name }}
                            <button type="button" onclick="setTagAjax({{ Illuminate\Support\Js::from($tag->name) }}, 'include', this)"
                                    title="Include this tag in Favorites" class="hover:text-green-700">+</button>
                            <button type="button" onclick="setTagAjax({{ Illuminate\Support\Js::from($tag->name) }}, 'exclude', this)"
                                    title="Exclude this tag from Favorites" class="hover:text-red-700">&minus;</button>
                        </span>
                    @endforeach
                </div>
            @endif

            @unless ($article->is_read)
                <div class="flex items-center gap-3 mt-3 text-sm">
                    <button type="button" id="mark-read-btn-{{ $article->id }}" onclick="markReadAjax({{ $article->id }})"
                            class="text-gray-400 hover:text-gray-700">
                        Mark read
                    </button>
                </div>
            @endunless
        </div>
    @empty
        <p class="text-center text-gray-500 py-12">No articles yet. Add some feeds and run <code>php artisan news:fetch</code>.</p>
    @endforelse
</div>

<div class="mt-6">
    {{ $articles->links() }}
</div>
@endsection
