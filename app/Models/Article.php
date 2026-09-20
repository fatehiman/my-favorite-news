<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_id',
        'article_cluster_id',
        'title',
        'normalized_title',
        'url',
        'guid',
        'description',
        'published_at',
        'is_read',
        'is_favorite',
        'is_deleted',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_read' => 'boolean',
        'is_favorite' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(ArticleCluster::class, 'article_cluster_id');
    }
}
