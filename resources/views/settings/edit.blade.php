@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<h1 class="text-lg font-semibold mb-4">Settings</h1>

<form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-lg shadow-sm p-4">
    @csrf
    @method('PUT')

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

    <button type="submit" class="mt-4 bg-gray-900 text-white px-4 py-2 rounded-md text-sm">Save</button>
</form>

<p class="text-sm text-gray-500 mt-4">
    Looking for included/excluded tags? That moved to the
    <a href="{{ route('tags.index') }}" class="text-blue-600 hover:underline">Tags</a> page.
</p>
@endsection
