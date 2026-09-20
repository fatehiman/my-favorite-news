@extends('layouts.app')

@section('title', 'Briefing')

@section('content')
<div class="flex flex-wrap gap-2 mb-4 text-sm">
    <a href="{{ route('articles.index') }}"
       class="px-3 py-1 rounded-full {{ !$activeCategory && !$showFavoritesOnly && !$showImportantOnly ? 'bg-gray-900 text-white' : 'bg-white border' }}">
        All
    </a>
    @foreach ($categories as $category)
        <a href="{{ route('articles.index', ['category' => $category]) }}"
           class="px-3 py-1 rounded-full capitalize {{ $activeCategory === $category ? 'bg-gray-900 text-white' : 'bg-white border' }}">
            {{ $category }}
        </a>
    @endforeach
    <a href="{{ route('articles.index', ['important' => 1]) }}"
       class="px-3 py-1 rounded-full {{ $showImportantOnly ? 'bg-amber-500 text-white' : 'bg-white border' }}">
        ⭐ Important
    </a>
    <a href="{{ route('articles.index', ['favorites' => 1]) }}"
       class="px-3 py-1 rounded-full {{ $showFavoritesOnly ? 'bg-rose-500 text-white' : 'bg-white border' }}">
        ♥ Favorites
    </a>
</div>

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
                    <span class="ml-auto bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">
                        ⭐ {{ $article->cluster->sources_count }} sources
                    </span>
                @endif
            </div>

            <a href="{{ $article->url }}" target="_blank" rel="noopener"
               onclick="document.getElementById('read-{{ $article->id }}').submit()"
               class="font-medium text-gray-900 hover:underline">
                {{ $article->title }}
            </a>

            @if ($article->description)
                <p class="text-sm text-gray-600 mt-1">{{ Str::limit($article->description, 180) }}</p>
            @endif

            <div class="flex items-center gap-3 mt-3 text-sm">
                <form id="read-{{ $article->id }}" method="POST" action="{{ route('articles.read', $article) }}">
                    @csrf
                </form>

                <form method="POST" action="{{ route('articles.favorite', $article) }}">
                    @csrf
                    <button type="submit" class="{{ $article->is_favorite ? 'text-rose-500' : 'text-gray-400' }} hover:text-rose-500">
                        {{ $article->is_favorite ? '♥ Favorited' : '♡ Favorite' }}
                    </button>
                </form>

                @unless ($article->is_read)
                    <form method="POST" action="{{ route('articles.read', $article) }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-gray-700">Mark read</button>
                    </form>
                @endunless

                <form method="POST" action="{{ route('articles.destroy', $article) }}" class="ml-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-gray-400 hover:text-red-600">Remove</button>
                </form>
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
