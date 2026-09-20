@extends('layouts.app')

@section('title', 'Edit Feed')

@section('content')
<h1 class="text-lg font-semibold mb-4">Edit RSS Feed</h1>

<form method="POST" action="{{ route('feeds.update', $feed) }}" class="bg-white rounded-lg shadow-sm p-4">
    @csrf
    @method('PUT')
    @include('feeds._form')

    <div class="mt-4 flex gap-2">
        <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded-md text-sm">Save</button>
        <a href="{{ route('feeds.index') }}" class="px-4 py-2 rounded-md text-sm border">Cancel</a>
    </div>
</form>
@endsection
