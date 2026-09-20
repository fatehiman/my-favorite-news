<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCluster extends Model
{
    use HasFactory;

    protected $fillable = [
        'representative_title',
        'category',
        'sources_count',
        'is_important',
    ];

    protected $casts = [
        'is_important' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
