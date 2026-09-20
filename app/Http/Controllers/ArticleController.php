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
            ->whereRaw($this->oneArticlePerClusterSql())
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
                $query->where(function ($q) use ($includedTags) {
                    foreach ($includedTags as $term) {
                        $this->orMatchesTerm($q, $term);
                    }
                });
            }

            foreach ($excludedTags as $term) {
                $query->where(function ($q) use ($term) {
                    $this->whereDoesntMatchTerm($q, $term);
                });
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

    /**
     * The same story often gets fetched from 2+ feeds — that's exactly what the
     * "important" badge is for — but each of those rows is a separate Article, so
     * without this the briefing showed the same headline once per source. This
     * picks a single representative row per cluster (preferring one with full
     * content, then the most recent), and leaves standalone (non-clustered)
     * articles untouched by grouping each one under its own id.
     */
    private function oneArticlePerClusterSql(): string
    {
        return <<<'SQL'
            articles.id IN (
                SELECT id FROM (
                    SELECT
                        id,
                        ROW_NUMBER() OVER (
                            PARTITION BY COALESCE(article_cluster_id, -id)
                            ORDER BY (content IS NOT NULL) DESC, published_at DESC, id DESC
                        ) AS rn
                    FROM articles
                ) ranked
                WHERE rn = 1
            )
            SQL;
    }

    /**
     * A term matches an article if it's one of the article's real tags (from the
     * feed's <category> data), OR appears as text in the title/description — this
     * lets a manually-added keyword (a person's or country's name) work too.
     */
    private function orMatchesTerm($query, string $term): void
    {
        $query->orWhereHas('tags', fn ($q) => $q->where('name', $term))
            ->orWhere('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    private function whereDoesntMatchTerm($query, string $term): void
    {
        $query->whereDoesntHave('tags', fn ($q) => $q->where('name', $term))
            ->where('title', 'not like', "%{$term}%")
            ->where('description', 'not like', "%{$term}%");
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
