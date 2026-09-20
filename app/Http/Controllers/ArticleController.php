<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $hiddenCategories = Setting::getJson('hidden_categories', []);
        $includedTags = Setting::getJson('included_tags', []);
        $excludedTags = Setting::getJson('excluded_tags', []);
        $activeCategory = $request->query('category');
        $showFavoritesOnly = $request->boolean('favorites');
        $showImportantOnly = $request->boolean('important');

        $query = Article::query()
            ->with(['feed', 'tags', 'cluster.articles.feed'])
            ->whereHas('feed', function ($q) use ($hiddenCategories, $activeCategory) {
                if ($activeCategory) {
                    $q->where('category', $activeCategory);
                } elseif (! empty($hiddenCategories)) {
                    $q->whereNotIn('category', $hiddenCategories);
                }
            })
            ->orderByDesc('published_at');

        if ($showFavoritesOnly) {
            if (empty($includedTags)) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereHas('tags', fn ($q) => $q->whereIn('name', $includedTags));
            }

            if (! empty($excludedTags)) {
                $query->whereDoesntHave('tags', fn ($q) => $q->whereIn('name', $excludedTags));
            }
        }

        if ($showImportantOnly) {
            $query->whereHas('cluster', fn ($q) => $q->where('is_important', true));
        }

        $articles = $query->paginate(30)->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'categories' => Feed::CATEGORIES,
            'hiddenCategories' => $hiddenCategories,
            'includedTags' => $includedTags,
            'excludedTags' => $excludedTags,
            'activeCategory' => $activeCategory,
            'showFavoritesOnly' => $showFavoritesOnly,
            'showImportantOnly' => $showImportantOnly,
            'lastFetchedAt' => ($max = Feed::max('last_fetched_at')) ? \Illuminate\Support\Carbon::parse($max) : null,
        ]);
    }

    public function markRead(Article $article): RedirectResponse
    {
        $article->update(['is_read' => true]);

        return back();
    }

    public function content(Article $article): JsonResponse
    {
        $article->update(['is_read' => true]);

        if ($article->content) {
            return response()->json(['content' => $article->content]);
        }

        return response()->json([
            'content' => null,
            'message' => 'This source only provides a short summary in its feed, not the full article. Open the original link to read it there.',
        ]);
    }

    public function triggerFetch(): RedirectResponse
    {
        Cache::put('news_fetch_requested', true, now()->addMinutes(10));

        return back()->with('status', 'Fetch queued — check back in a couple of minutes.');
    }
}
