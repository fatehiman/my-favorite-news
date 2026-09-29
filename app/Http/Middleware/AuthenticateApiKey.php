<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * API auth: the key comes in `Authorization: Bearer <key>` or `X-API-Key: <key>`.
 * Deliberately not accepted as a query-string parameter — URLs end up in
 * server and proxy logs.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->bearerToken() ?: $request->header('X-API-Key');
        $user = $key ? User::findByApiKey($key) : null;

        if (! $user) {
            return response()->json(['message' => 'Missing or invalid API key.'], 401);
        }

        Auth::setUser($user);

        // Only touch the row once a minute, not on every request.
        if (! $user->api_key_last_used_at || $user->api_key_last_used_at->lt(now()->subMinute())) {
            $user->forceFill(['api_key_last_used_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
