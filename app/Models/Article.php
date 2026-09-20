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
