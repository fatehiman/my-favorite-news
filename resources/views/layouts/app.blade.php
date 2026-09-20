<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My Favorite News')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen">
    @auth
    <nav class="bg-white border-b sticky top-0 z-10">
        <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('articles.index') }}" class="font-semibold text-lg">📰 My Favorite News</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('articles.index') }}" class="hover:underline">Briefing</a>
                <a href="{{ route('feeds.index') }}" class="hover:underline">Feeds</a>
                <a href="{{ route('settings.edit') }}" class="hover:underline">Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-gray-800">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-2xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 text-green-800 px-4 py-2 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
