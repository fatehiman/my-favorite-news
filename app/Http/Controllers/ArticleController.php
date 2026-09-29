<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Setting;
use App\Services\ArticleFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request, ArticleFilter $filter): View
    {
        $hiddenCategories = Setting::getJson('hidden_categories', []);
        $includedTags = Setting::getJson('included_tags', []);
        $excludedTags = Setting::getJson('excluded_tags', []);
        $activeCategory = $request->query('category');
        $showFavoritesOnly = $request->boolean('favorites');
        $showImportantOnly = $request->boolean('important');
        $showUnreadOnly = $request->boolean('unread');

        $query = $filter->query([
            'category' => $activeCategory,
            'favorites' => $showFavoritesOnly,
            'important' => $showImportantOnly,
            'unread' => $showUnreadOnly,
        ]);

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
            'showUnreadOnly' => $showUnreadOnly,
            'lastFetchedAt' => ($max = Feed::max('last_fetched_at')) ? Carbon::parse($max) : null,
        ]);
    }

    public function markRead(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        $article->update(['is_read' => true]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    /**
     * "Mark all as read" under the last card: the page sends the ids of the cards
     * it's showing, so only that page is marked — not every article in the filter.
     */
    public function markManyRead(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ])['ids'];

        $count = Article::whereIn('id', $ids)->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['ok' => true, 'marked' => $count]);
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
