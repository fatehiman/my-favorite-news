@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="max-w-sm mx-auto mt-16 bg-white p-6 rounded-lg shadow-sm">
    <h1 class="text-xl font-semibold mb-4 text-center">📰 My Favorite News</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required autofocus
                   class="w-full border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full border rounded-md px-3 py-2">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button type="submit" class="w-full bg-gray-900 text-white rounded-md py-2 font-medium">
            Log in
        </button>
    </form>
</div>
@endsection
