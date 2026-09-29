@extends('layouts.app')

@section('title', 'API')

@section('content')
<h1 class="text-lg font-semibold mb-1">API</h1>
<p class="text-sm text-gray-500 mb-4">
    Another app can read your briefing as JSON with this key. It takes the same filters as the
    Briefing page (category, Favorites, Important, Unread), plus a date range, paging, and an
    option to mark the returned page as read. Full reference: <code>api-usage.md</code> in the repo.
</p>

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <p class="text-sm font-medium mb-2">Your API key</p>

    @if ($user->api_key)
        <div class="flex gap-2">
            <input id="api-key" type="password" readonly value="{{ $user->api_key }}"
                   class="flex-1 border rounded-md px-3 py-2 text-sm font-mono bg-gray-50">
            <button type="button" onclick="toggleKey(this)"
                    class="border rounded-md px-3 py-2 text-sm hover:bg-gray-100">Show</button>
            <button type="button" onclick="copyKey(this)"
                    class="border rounded-md px-3 py-2 text-sm hover:bg-gray-100">Copy</button>
        </div>
        <p class="text-xs text-gray-500 mt-2">
            Created {{ $user->api_key_created_at?->diffForHumans() }}
            &middot;
            Last used {{ $user->api_key_last_used_at?->diffForHumans() ?? 'never' }}
        </p>

        <form method="POST" action="{{ route('api-key.rotate') }}" class="mt-4"
              onsubmit="return confirm('Create a new key? The current key stops working right away, so any app using it must be updated.')">
            @csrf
            <button type="submit" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm">⟳ Rotate key</button>
        </form>
    @else
        <p class="text-sm text-gray-400 mb-3">No API key yet.</p>
        <form method="POST" action="{{ route('api-key.rotate') }}">
            @csrf
            <button type="submit" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm">Create API key</button>
        </form>
    @endif
</div>

<div class="bg-white rounded-lg shadow-sm p-4 text-sm">
    <p class="font-medium mb-2">Quick examples</p>
    <p class="text-gray-500 mb-1">Send the key in a header — <code>Authorization: Bearer &lt;key&gt;</code> or <code>X-API-Key: &lt;key&gt;</code>.</p>
    <pre class="bg-gray-50 rounded-md p-3 overflow-x-auto text-xs"># All + Important + Unread, 10 per page
curl -H "X-API-Key: YOUR_KEY" "{{ url('/api/v1/articles') }}?important=1&amp;unread=1"

# IT + Unread, and mark the returned 10 as read
curl -X POST -H "X-API-Key: YOUR_KEY" "{{ url('/api/v1/articles') }}?category=it&amp;unread=1&amp;mark_read=1"

# Date range (published between two days, inclusive)
curl -H "X-API-Key: YOUR_KEY" "{{ url('/api/v1/articles') }}?from=2026-09-25&amp;to=2026-09-29"

# Fetch now
curl -X POST -H "X-API-Key: YOUR_KEY" "{{ url('/api/v1/fetch-now') }}"</pre>
</div>

<script>
    function toggleKey(btn) {
        const input = document.getElementById('api-key');
        const hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';
        btn.textContent = hidden ? 'Hide' : 'Show';
    }

    function copyKey(btn) {
        navigator.clipboard.writeText(document.getElementById('api-key').value).then(() => {
            btn.textContent = 'Copied';
            setTimeout(() => btn.textContent = 'Copy', 1500);
        });
    }
</script>
@endsection
