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

    /**
     * Full-body translation, shown in the "Read here" modal.
     */
    public function translate(Article $article): JsonResponse
    {
        if ($article->translation_fa) {
            return response()->json(['translation' => $article->translation_fa]);
        }

        if ($error = $this->checkQuotaAndConfig()) {
            return $error;
        }

        $text = $article->content ?: $article->description;

        $translation = $this->callDeepSeek(
            'You are a professional English-to-Persian news translator. Translate the given news title and text into fluent, natural Persian. Reply with only the translation, no notes or commentary.',
            $article->title."\n\n".$text
        );

        if ($translation instanceof JsonResponse) {
            return $translation;
        }

        $article->update(['translation_fa' => $translation, 'translated_at' => now()]);
        $this->consumeQuota();

        return response()->json(['translation' => $translation]);
    }

    /**
     * Short title + description translation, shown in place on the card.
     */
    public function translateCard(Article $article): JsonResponse
    {
        if ($article->title_fa) {
            return response()->json(['title' => $article->title_fa, 'description' => $article->description_fa]);
        }

        if ($error = $this->checkQuotaAndConfig()) {
            return $error;
        }

        $raw = $this->callDeepSeek(
            'You are a professional English-to-Persian news translator. You will be given a JSON object with "title" and "description". '.
            'Translate both into fluent, natural Persian and reply with ONLY a valid JSON object of the same shape: {"title": "...", "description": "..."}. '.
            'No markdown, no code fences, no extra text.',
            json_encode(['title' => $article->title, 'description' => (string) $article->description], JSON_UNESCAPED_UNICODE)
        );

        if ($raw instanceof JsonResponse) {
            return $raw;
        }

        $clean = trim(preg_replace('/^```json|```$/m', '', $raw));
        $parsed = json_decode($clean, true);

        if (! is_array($parsed) || empty($parsed['title'])) {
            return response()->json(['message' => 'Translation service returned an unexpected format.'], 502);
        }

        $article->update([
            'title_fa' => $parsed['title'],
            'description_fa' => $parsed['description'] ?? null,
        ]);
        $this->consumeQuota();

        return response()->json(['title' => $parsed['title'], 'description' => $parsed['description'] ?? null]);
    }

    private function checkQuotaAndConfig(): ?JsonResponse
    {
        $used = Cache::get($this->usageKey(), 0);

        if ($used >= self::DAILY_LIMIT) {
            return response()->json([
                'message' => 'Daily translation limit ('.self::DAILY_LIMIT.') reached. Try again after midnight.',
            ], 429);
        }

        if (! config('services.deepseek.api_key')) {
            return response()->json(['message' => 'Translation is not configured (missing DEEPSEEK_API_KEY).'], 500);
        }

        return null;
    }

    private function consumeQuota(): void
    {
        Cache::put($this->usageKey(), Cache::get($this->usageKey(), 0) + 1, now()->endOfDay());
    }

    private function usageKey(): string
    {
        return 'translations_used_'.now()->toDateString();
    }

    /**
     * @return string|JsonResponse The translated text, or an error response to return as-is.
     */
    private function callDeepSeek(string $systemPrompt, string $userContent): string|JsonResponse
    {
        try {
            $response = Http::withToken(config('services.deepseek.api_key'))
                ->timeout(30)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'temperature' => 0.3,
                ]);

            if (! $response->successful()) {
                Log::warning('DeepSeek translation failed: '.$response->body());

                return response()->json(['message' => 'Translation service error. Try again later.'], 502);
            }

            $text = trim($response->json('choices.0.message.content', ''));

            if ($text === '') {
                return response()->json(['message' => 'Translation service returned an empty result.'], 502);
            }

            return $text;
        } catch (\Throwable $e) {
            Log::error('DeepSeek translation exception: '.$e->getMessage());

            return response()->json(['message' => 'Could not reach the translation service.'], 502);
        }
    }
}
