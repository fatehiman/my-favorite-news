@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<h1 class="text-lg font-semibold mb-4">Settings</h1>

<form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-lg shadow-sm p-4">
    @csrf
    @method('PUT')

    <p class="text-sm text-gray-600 mb-3">Hide these categories from your briefing (you can still filter to see them from the tabs):</p>

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
@endsection
