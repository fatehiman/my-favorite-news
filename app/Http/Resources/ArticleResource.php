<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One briefing card as JSON. Expects the relations ArticleFilter eager-loads
 * (feed, tags, cluster.articles.feed, cluster.articles.tags).
 */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sources = $this->cluster
            ? $this->cluster->articles->pluck('feed.name')->unique()->values()
            : collect([$this->feed->name]);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'category' => $this->feed->category,
            'source' => $this->feed->name,
            'published_at' => $this->published_at?->toIso8601String(),
            'is_read' => $this->is_read,
            'is_important' => (bool) $this->cluster?->is_important,
            'sources_count' => $sources->count(),
            'sources' => $sources,
            'tags' => $this->all_tags->pluck('name')->values(),
            'title_fa' => $this->title_fa,
            'description_fa' => $this->description_fa,
            'has_full_content' => $this->content !== null,
            'content' => $this->when($request->boolean('include_content'), $this->content),
        ];
    }
}
