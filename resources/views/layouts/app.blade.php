<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                <a href="{{ route('tags.index') }}" class="hover:underline">Tags</a>
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

    @auth
    <dialog id="article-modal" class="rounded-lg p-0 w-full max-w-lg backdrop:bg-black/40">
        <div class="p-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-start gap-3 mb-2">
                <h2 id="modal-title" class="font-semibold text-lg"></h2>
                <button type="button" onclick="document.getElementById('article-modal').close()"
                        class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div id="modal-body" class="text-sm text-gray-700 whitespace-pre-line"></div>

            <div class="mt-4 border-t pt-3">
                <button type="button" id="translate-btn" class="text-sm text-blue-600 hover:underline">
                    Translate to Persian
                </button>
                <div id="modal-translation" class="mt-2 text-sm text-gray-800" dir="rtl" lang="fa"></div>
            </div>
        </div>
    </dialog>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        let currentArticleId = null;

        function openArticle(id, title) {
            currentArticleId = id;
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-body').textContent = 'Loading…';
            document.getElementById('modal-translation').textContent = '';
            document.getElementById('article-modal').showModal();

            fetch(`/articles/${id}/content`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('modal-body').textContent = data.content || data.message;
                })
                .catch(() => {
                    document.getElementById('modal-body').textContent = 'Could not load the article.';
                });
        }

        document.getElementById('translate-btn').addEventListener('click', () => {
            if (!currentArticleId) return;
            const el = document.getElementById('modal-translation');
            el.textContent = 'Translating…';

            fetch(`/articles/${currentArticleId}/translate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
            })
                .then(r => r.json())
                .then(data => {
                    el.textContent = data.translation || data.message || 'Translation failed.';
                })
                .catch(() => {
                    el.textContent = 'Could not reach the translation service.';
                });
        });

        function translateCard(id, btn) {
            const titleEl = document.getElementById(`title-${id}`);
            const descEl = document.getElementById(`desc-${id}`);

            if (btn.dataset.translated === 'true') {
                titleEl.textContent = titleEl.dataset.original;
                titleEl.removeAttribute('dir');
                if (descEl) {
                    descEl.textContent = descEl.dataset.original;
                    descEl.removeAttribute('dir');
                }
                btn.textContent = '🌐';
                btn.title = 'Translate to Persian';
                btn.dataset.translated = 'false';
                return;
            }

            btn.disabled = true;
            btn.textContent = '…';

            fetch(`/articles/${id}/translate-card`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (!data.title) {
                        btn.textContent = '🌐';
                        alert(data.message || 'Translation failed.');
                        return;
                    }
                    titleEl.dataset.original = titleEl.textContent;
                    titleEl.textContent = data.title;
                    titleEl.setAttribute('dir', 'rtl');
                    if (descEl) {
                        descEl.dataset.original = descEl.textContent;
                        descEl.textContent = data.description || descEl.textContent;
                        descEl.setAttribute('dir', 'rtl');
                    }
                    btn.textContent = '↩';
                    btn.title = 'Show original';
                    btn.dataset.translated = 'true';
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = '🌐';
                    alert('Could not reach the translation service.');
                });
        }
    </script>
    @endauth
</body>
</html>
