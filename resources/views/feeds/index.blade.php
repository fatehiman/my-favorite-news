@extends('layouts.app')

@section('title', 'Feeds')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-semibold">RSS Feeds</h1>
    <a href="{{ route('feeds.create') }}" class="bg-gray-900 text-white text-sm px-3 py-2 rounded-md">+ Add feed</a>
</div>

<div class="bg-white rounded-lg shadow-sm divide-y">
    @foreach ($feeds as $feed)
        <div class="p-4 flex items-center gap-3">
            <div class="flex-1">
                <div class="font-medium">{{ $feed->name }}</div>
                <div class="text-xs text-gray-500 capitalize">{{ $feed->category }} &middot; {{ $feed->url }}</div>
                @if ($feed->last_error)
                    <div class="text-xs text-red-600 mt-1">Last error: {{ $feed->last_error }}</div>
                @elseif ($feed->last_fetched_at)
                    <div class="text-xs text-gray-400 mt-1">Last fetched {{ $feed->last_fetched_at->diffForHumans() }}</div>
                @endif
            </div>
            <span class="text-xs px-2 py-1 rounded-full {{ $feed->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $feed->is_active ? 'Active' : 'Paused' }}
            </span>
            <a href="{{ route('feeds.edit', $feed) }}" class="text-sm text-gray-500 hover:text-gray-900">Edit</a>
            <form method="POST" action="{{ route('feeds.destroy', $feed) }}" onsubmit="return confirm('Delete this feed?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-gray-400 hover:text-red-600">Delete</button>
            </form>
        </div>
    @endforeach
</div>
@endsection
