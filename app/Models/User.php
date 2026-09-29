<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'api_key', 'api_key_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'api_key' => 'encrypted',
            'api_key_created_at' => 'datetime',
            'api_key_last_used_at' => 'datetime',
        ];
    }

    /**
     * Creates a new API key and replaces the old one — the old key stops
     * working immediately. Returns the new plain key.
     */
    public function rotateApiKey(): string
    {
        $key = 'nfp_'.Str::random(40);

        $this->forceFill([
            'api_key' => $key,
            'api_key_hash' => hash('sha256', $key),
            'api_key_created_at' => now(),
            'api_key_last_used_at' => null,
        ])->save();

        return $key;
    }

    public static function findByApiKey(string $key): ?self
    {
        return static::where('api_key_hash', hash('sha256', $key))->first();
    }
}
