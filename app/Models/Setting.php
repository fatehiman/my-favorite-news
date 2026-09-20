<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getJson(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();

        if (! $row || $row->value === null) {
            return $default;
        }

        return json_decode($row->value, true);
    }

    public static function setJson(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
    }
}
