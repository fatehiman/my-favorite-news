<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The briefing query, shared by the web page (ArticleController) and the API
 * (Api\ArticleController) so both apply exactly the same rules for tabs and
 * toggles: one card per cluster, category / hidden categories, Favorites,
 * Important, Unread — plus an optional published_at date range.
 */
class ArticleFilter
{
    /**
     * @param  array{category?: ?string, favorites?: bool, important?: bool, unread?: bool, from?: ?CarbonInterface, to?: ?CarbonInterface}  $filters
     */
    public function query(array $filters): Builder
    {
        $hiddenCategories = Setting::getJson('hidden_categories', []);
        $includedTags = Setting::getJson('included_tags', []);
        $excludedTags = Setting::getJson('excluded_tags', []);
        $activeCategory = $filters['category'] ?? null;

        $query = Article::query()
            ->with(['feed', 'tags', 'cluster.articles.feed', 'cluster.articles.tags'])
            ->whereRaw($this->oneArticlePerClusterSql())
            ->whereHas('feed', function ($q) use ($hiddenCategories, $activeCategory) {
                if ($activeCategory) {
                    $q->where('category', $activeCategory);
                } elseif (! empty($hiddenCategories)) {
                    $q->whereNotIn('category', $hiddenCategories);
                }
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if (! empty($filters['favorites'])) {
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

        if (! empty($filters['important'])) {
            $query->whereHas('cluster', fn ($q) => $q->where('is_important', true));
        }

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        if (! empty($filters['from'])) {
            $query->where('published_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('published_at', '<=', $filters['to']);
        }

        return $query;
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
}
