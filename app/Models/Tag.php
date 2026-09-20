<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public static function findOrCreateByName(string $name): self
    {
        $normalized = mb_strtolower(trim($name));

        return static::firstOrCreate(['name' => $normalized]);
    }
}
