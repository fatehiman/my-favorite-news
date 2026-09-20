@extends('layouts.app')

@section('title', 'Briefing')

@php
    // Base query params for the current view, without the "important" toggle —
    // used so Important can be combined with whichever tab (All/category/Favorites)
    // is active, instead of being its own mutually-exclusive tab.
    $baseParams = array_filter([
        'category' => $activeCategory,
        'favorites' => $showFavoritesOnly ? 1 : null,
    ]);
    $importantToggleParams = $showImportantOnly ? $baseParams : $baseParams + ['important' => 1];
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
        <a href="{{ route('articles.index') }}"
           class="px-3 py-1 rounded-full {{ !$activeCategory && !$showFavoritesOnly ? 'bg-gray-900 text-white' : 'bg-white border' }}">
            All
        </a>
        @foreach ($categories as $category)
            <a href="{{ route('articles.index', ['category' => $category] + ($showImportantOnly ? ['important' => 1] : [])) }}"
               class="px-3 py-1 rounded-full capitalize {{ $activeCategory === $category ? 'bg-gray-900 text-white' : 'bg-white border' }}">
                {{ $category }}
            </a>
        @endforeach
        <a href="{{ route('articles.index', ['favorites' => 1] + ($showImportantOnly ? ['important' => 1] : [])) }}"
           class="px-3 py-1 rounded-full {{ $showFavoritesOnly ? 'bg-rose-500 text-white' : 'bg-white border' }}">
            ♥ Favorites
        </a>
    </div>

    <a href="{{ route('articles.index', $importantToggleParams) }}"
       title="Show only important stories, combined with the tab on the left"
       class="shrink-0 px-2.5 py-1 rounded-full text-sm border {{ $showImportantOnly ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-500' }}">
        ⭐
    </a>
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
        <div class="bg-white rounded-lg shadow-sm p-4 {{ $article->is_read ? 'opacity-60' : '' }}">
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
                   onclick="document.getElementById('read-{{ $article->id }}').submit()"
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

            @if ($article->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach ($article->tags as $tag)
                        @php
                            $isIncluded = in_array($tag->name, $includedTags);
                            $isExcluded = in_array($tag->name, $excludedTags);
                        @endphp
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                            {{ $isIncluded ? 'bg-green-100 text-green-700' : ($isExcluded ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $tag->name }}
                            <form method="POST" action="{{ route('tags.set') }}" class="inline">
                                @csrf
                                <input type="hidden" name="name" value="{{ $tag->name }}">
                                <input type="hidden" name="action" value="include">
                                <button type="submit" title="Include this tag in Favorites" class="hover:text-green-700">+</button>
                            </form>
                            <form method="POST" action="{{ route('tags.set') }}" class="inline">
                                @csrf
                                <input type="hidden" name="name" value="{{ $tag->name }}">
                                <input type="hidden" name="action" value="exclude">
                                <button type="submit" title="Exclude this tag from Favorites" class="hover:text-red-700">&minus;</button>
                            </form>
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-3 mt-3 text-sm">
                <form id="read-{{ $article->id }}" method="POST" action="{{ route('articles.read', $article) }}">
                    @csrf
                </form>

                @unless ($article->is_read)
                    <form method="POST" action="{{ route('articles.read', $article) }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-gray-700">Mark read</button>
                    </form>
                @endunless
            </div>
        </div>
    @empty
        <p class="text-center text-gray-500 py-12">No articles yet. Add some feeds and run <code>php artisan news:fetch</code>.</p>
    @endforelse
</div>

<div class="mt-6">
    {{ $articles->links() }}
</div>
@endsection
