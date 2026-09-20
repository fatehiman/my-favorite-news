<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationController extends Controller
{
    private const DAILY_LIMIT = 10;

    public function translate(Article $article): JsonResponse
    {
        if ($article->translation_fa) {
            return response()->json(['translation' => $article->translation_fa]);
        }

        $usageKey = 'translations_used_'.now()->toDateString();
        $used = Cache::get($usageKey, 0);

        if ($used >= self::DAILY_LIMIT) {
            return response()->json([
                'message' => 'Daily translation limit ('.self::DAILY_LIMIT.') reached. Try again after midnight.',
            ], 429);
        }

        $apiKey = config('services.deepseek.api_key');

        if (! $apiKey) {
            return response()->json(['message' => 'Translation is not configured (missing DEEPSEEK_API_KEY).'], 500);
        }

        $text = $article->content ?: $article->description;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional English-to-Persian news translator. Translate the given news title and text into fluent, natural Persian. Reply with only the translation, no notes or commentary.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $article->title."\n\n".$text,
                        ],
                    ],
                    'temperature' => 0.3,
                ]);

            if (! $response->successful()) {
                Log::warning('DeepSeek translation failed: '.$response->body());

                return response()->json(['message' => 'Translation service error. Try again later.'], 502);
            }

            $translation = trim($response->json('choices.0.message.content', ''));

            if ($translation === '') {
                return response()->json(['message' => 'Translation service returned an empty result.'], 502);
            }

            $article->update(['translation_fa' => $translation, 'translated_at' => now()]);

            Cache::put($usageKey, $used + 1, now()->endOfDay());

            return response()->json(['translation' => $translation]);
        } catch (\Throwable $e) {
            Log::error('DeepSeek translation exception: '.$e->getMessage());

            return response()->json(['message' => 'Could not reach the translation service.'], 502);
        }
    }
}
