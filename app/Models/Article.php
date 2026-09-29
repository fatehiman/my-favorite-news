<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_id',
        'article_cluster_id',
        'title',
        'title_fa',
        'normalized_title',
        'url',
        'guid',
        'description',
        'description_fa',
        'content',
        'translation_fa',
        'translated_at',
        'published_at',
        'is_read',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'translated_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(ArticleCluster::class, 'article_cluster_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Read state is per story, not per outlet: marking a card read marks every
     * article in its cluster. Otherwise, when another member of the cluster
     * later becomes the shown card, the story would look unread again.
     * (DuplicateDetectorService also marks a new article read when it joins
     * an already-read cluster.) Returns the number of rows changed.
     */
    public static function markStoriesRead(iterable $ids): int
    {
        $ids = collect($ids)->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        $clusterIds = static::whereIn('id', $ids)->whereNotNull('article_cluster_id')->pluck('article_cluster_id');

        return static::where('is_read', false)
            ->where(fn ($q) => $q->whereIn('id', $ids)->orWhereIn('article_cluster_id', $clusterIds))
            ->update(['is_read' => true]);
    }

    /**
     * Tags are attached per feed row, but only ~3 of our sources actually supply
     * RSS <category> data. When this article is the representative of a cluster,
     * show tags pooled from every member — a story a Fox feed tagged shouldn't
     * lose its tags just because the shown card happens to be the NBC copy.
     * Requires 'cluster.articles.tags' to be eager-loaded to avoid N+1 queries.
     */
    public function getAllTagsAttribute()
    {
        if (! $this->article_cluster_id || ! $this->relationLoaded('cluster') || ! $this->cluster) {
            return $this->tags;
        }

        return $this->cluster->articles
            ->flatMap(fn ($article) => $article->tags)
            ->unique('id')
            ->values();
    }
}
