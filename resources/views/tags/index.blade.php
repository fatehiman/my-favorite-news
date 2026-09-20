@extends('layouts.app')

@section('title', 'Tags')

@section('content')
<h1 class="text-lg font-semibold mb-1">Tags</h1>
<p class="text-sm text-gray-500 mb-4">
    Favorites shows articles matching an included tag/keyword and none of the excluded ones.
    A tag/keyword matches an article either because the feed tagged it that way, or because
    the word appears in the title or summary — so you can add a person's or country's name and
    it will be picked up even if no feed specifically tagged it.
</p>

<form method="POST" action="{{ route('tags.set') }}" class="bg-white rounded-lg shadow-sm p-4 mb-4 flex gap-2">
    @csrf
    <input type="text" name="name" required placeholder="Add a tag or keyword (e.g. a name, a country)"
           class="flex-1 border rounded-md px-3 py-2 text-sm">
    <input type="hidden" name="action" value="include">
    <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded-md text-sm">+ Include</button>
</form>

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <p class="text-sm font-medium mb-2">Included ({{ count($includedTags) }})</p>
    @if (empty($includedTags))
        <p class="text-sm text-gray-400">None yet — Favorites will show nothing until you add one.</p>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach ($includedTags as $tag)
                <span class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full bg-green-100 text-green-700">
                    {{ $tag }}
                    <form method="POST" action="{{ route('tags.set') }}">
                        @csrf
                        <input type="hidden" name="name" value="{{ $tag }}">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" title="Remove" class="hover:text-green-900">&times;</button>
                    </form>
                </span>
            @endforeach
        </div>
    @endif
</div>

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <p class="text-sm font-medium mb-2">Excluded ({{ count($excludedTags) }})</p>
    @if (empty($excludedTags))
        <p class="text-sm text-gray-400">None.</p>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach ($excludedTags as $tag)
                <span class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full bg-red-100 text-red-700">
                    {{ $tag }}
                    <form method="POST" action="{{ route('tags.set') }}">
                        @csrf
                        <input type="hidden" name="name" value="{{ $tag }}">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" title="Remove" class="hover:text-red-900">&times;</button>
                    </form>
                </span>
            @endforeach
        </div>
    @endif
</div>

@if ($seenTags->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm font-medium mb-2">Seen in your feeds, not yet included/excluded ({{ $seenTags->count() }})</p>
        <p class="text-xs text-gray-500 mb-2">Quality varies by source — some are clean topics, others are internal site taxonomy.</p>
        <div class="flex flex-wrap gap-1">
            @foreach ($seenTags as $tag)
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                    {{ $tag }}
                    <form method="POST" action="{{ route('tags.set') }}" class="inline">
                        @csrf
                        <input type="hidden" name="name" value="{{ $tag }}">
                        <input type="hidden" name="action" value="include">
                        <button type="submit" title="Include" class="hover:text-green-700">+</button>
                    </form>
                    <form method="POST" action="{{ route('tags.set') }}" class="inline">
                        @csrf
                        <input type="hidden" name="name" value="{{ $tag }}">
                        <input type="hidden" name="action" value="exclude">
                        <button type="submit" title="Exclude" class="hover:text-red-700">&minus;</button>
                    </form>
                </span>
            @endforeach
        </div>
    </div>
@endif
@endsection
