<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleCluster;
use Illuminate\Support\Carbon;

class DuplicateDetectorService
{
    /**
     * Minimum word-overlap ratio (Jaccard on significant words) to consider two titles the same story.
     */
    private const SIMILARITY_THRESHOLD = 0.55;

    /**
     * Only compare against articles published within this many hours of each other.
     */
    private const TIME_WINDOW_HOURS = 48;

    private const STOP_WORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'of', 'in', 'on', 'at', 'to', 'for',
        'with', 'is', 'are', 'was', 'were', 'be', 'been', 'as', 'by', 'from', 'it',
        'its', 'his', 'her', 'their', 'this', 'that', 'after', 'over', 'into', 'amid',
        'says', 'said', 'new', 'news',
    ];

    public function normalize(string $title): string
    {
        $title = mb_strtolower($title);
        $title = preg_replace('/[^a-z0-9\s]/', ' ', $title);
        $words = preg_split('/\s+/', trim($title));
        $words = array_filter($words, fn ($w) => $w !== '' && ! in_array($w, self::STOP_WORDS, true));

        sort($words);

        return implode(' ', $words);
    }

    /**
     * Try to attach the article to an existing cluster of similar recent articles from OTHER feeds.
     * Creates a new cluster if none found. Updates sources_count / is_important.
     */
    public function assignCluster(Article $article): void
    {
        $wordsA = array_filter(explode(' ', $article->normalized_title));

        if (count($wordsA) < 2) {
            // Too short/generic a title to reliably match; give it its own cluster.
            $this->createNewCluster($article);

            return;
        }

        // "!= feed_id" isn't enough — the same outlet often runs the same story in two of
        // its own category feeds (e.g. Washington Examiner's politics AND economy feed).
        // That's not independent corroboration, so match by outlet NAME, not feed row.
        $candidates = Article::query()
            ->join('feeds', 'feeds.id', '=', 'articles.feed_id')
            ->whereNotNull('articles.article_cluster_id')
            ->where('feeds.name', '!=', $article->feed->name)
            ->where('articles.id', '!=', $article->id)
            ->when($article->published_at, function ($query) use ($article) {
                $query->whereBetween('articles.published_at', [
                    $article->published_at->copy()->subHours(self::TIME_WINDOW_HOURS),
                    $article->published_at->copy()->addHours(self::TIME_WINDOW_HOURS),
                ]);
            }, function ($query) {
                $query->where('articles.created_at', '>=', Carbon::now()->subHours(self::TIME_WINDOW_HOURS));
            })
            ->orderByDesc('articles.published_at')
            ->limit(300)
            ->get(['articles.id', 'articles.normalized_title', 'articles.article_cluster_id']);

        foreach ($candidates as $candidate) {
            $wordsB = array_filter(explode(' ', $candidate->normalized_title));

            if ($this->similarity($wordsA, $wordsB) >= self::SIMILARITY_THRESHOLD) {
                $this->attachToCluster($article, $candidate->article_cluster_id);

                return;
            }
        }

        $this->createNewCluster($article);
    }

    private function similarity(array $wordsA, array $wordsB): float
    {
        if (empty($wordsA) || empty($wordsB)) {
            return 0.0;
        }

        $intersection = count(array_intersect($wordsA, $wordsB));
        $union = count(array_unique(array_merge($wordsA, $wordsB)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    private function createNewCluster(Article $article): void
    {
        $cluster = ArticleCluster::create([
            'representative_title' => $article->title,
            'category' => $article->feed->category,
            'sources_count' => 1,
            'is_important' => false,
        ]);

        $article->update(['article_cluster_id' => $cluster->id]);
    }

    private function attachToCluster(Article $article, int $clusterId): void
    {
        $article->update(['article_cluster_id' => $clusterId]);

        $cluster = ArticleCluster::find($clusterId);
        $sourcesCount = Article::query()
            ->join('feeds', 'feeds.id', '=', 'articles.feed_id')
            ->where('articles.article_cluster_id', $clusterId)
            ->distinct('feeds.name')
            ->count('feeds.name');

        $cluster->update([
            'sources_count' => $sourcesCount,
            'is_important' => $sourcesCount >= 2,
        ]);
    }
}
