<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $hiddenCategories = Setting::getJson('hidden_categories', []);
        $activeCategory = $request->query('category');
        $showFavoritesOnly = $request->boolean('favorites');
        $showImportantOnly = $request->boolean('important');

        $query = Article::query()
            ->with(['feed', 'cluster'])
            ->where('is_deleted', false)
            ->whereHas('feed', function ($q) use ($hiddenCategories, $activeCategory) {
                if ($activeCategory) {
                    $q->where('category', $activeCategory);
                } elseif (! empty($hiddenCategories)) {
                    $q->whereNotIn('category', $hiddenCategories);
                }
            })
            ->orderByDesc('published_at');

        if ($showFavoritesOnly) {
            $query->where('is_favorite', true);
        }

        if ($showImportantOnly) {
            $query->whereHas('cluster', fn ($q) => $q->where('is_important', true));
        }

        $articles = $query->paginate(30)->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'categories' => Feed::CATEGORIES,
            'hiddenCategories' => $hiddenCategories,
            'activeCategory' => $activeCategory,
            'showFavoritesOnly' => $showFavoritesOnly,
            'showImportantOnly' => $showImportantOnly,
        ]);
    }

    public function markRead(Article $article): RedirectResponse
    {
        $article->update(['is_read' => true]);

        return back();
    }

    public function toggleFavorite(Article $article): RedirectResponse
    {
        $article->update(['is_favorite' => ! $article->is_favorite]);

        return back();
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->update(['is_deleted' => true]);

        return back()->with('status', 'Article removed.');
    }
}
