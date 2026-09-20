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
}
