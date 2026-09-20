@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<h1 class="text-lg font-semibold mb-4">Settings</h1>

<form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-lg shadow-sm p-4 space-y-6">
    @csrf
    @method('PUT')

    <div>
        <p class="text-sm font-medium mb-2">Hide these categories from your briefing</p>
        <p class="text-xs text-gray-500 mb-3">You can still see them from the category tabs — this only affects the "All" view.</p>
        <div class="space-y-2">
            @foreach ($categories as $category)
                <label class="flex items-center gap-2 text-sm capitalize">
                    <input type="checkbox" name="hidden_categories[]" value="{{ $category }}"
                           @checked(in_array($category, $hiddenCategories))>
                    {{ $category }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="border-t pt-4">
        <p class="text-sm font-medium mb-1">Favorites — included tags</p>
        <p class="text-xs text-gray-500 mb-2">
            Comma-separated. The Favorites tab shows articles that have at least one of these tags.
            Quicker way: click "+" on a tag chip under any article.
        </p>
        <input type="text" name="included_tags" value="{{ implode(', ', $includedTags) }}"
               placeholder="e.g. ai, election, white house"
               class="w-full border rounded-md px-3 py-2 text-sm">
    </div>

    <div>
        <p class="text-sm font-medium mb-1">Favorites — excluded tags</p>
        <p class="text-xs text-gray-500 mb-2">
            Comma-separated. Articles with any of these tags are hidden from Favorites, even if they
            also have an included tag.
        </p>
        <input type="text" name="excluded_tags" value="{{ implode(', ', $excludedTags) }}"
               placeholder="e.g. sports, celebrity"
               class="w-full border rounded-md px-3 py-2 text-sm">
    </div>

    @if ($allTags->isNotEmpty())
        <div class="border-t pt-4">
            <p class="text-xs text-gray-500 mb-2">Tags seen so far in your feeds (click one to copy):</p>
            <div class="flex flex-wrap gap-1">
                @foreach ($allTags as $tagName)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $tagName }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded-md text-sm">Save</button>
</form>
@endsection
