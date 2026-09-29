<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Feed;
use App\Services\ArticleFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * The same briefing as the web page, as JSON. Full reference: api-usage.md.
 */
class ArticleController extends Controller
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 100;

    public function index(Request $request, ArticleFilter $filter): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::in(Feed::CATEGORIES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $articles = $filter->query([
            'category' => $validated['category'] ?? null,
            'favorites' => $request->boolean('favorites'),
            'important' => $request->boolean('important'),
            'unread' => $request->boolean('unread'),
            'from' => $this->parseDate($validated['from'] ?? null, endOfDay: false),
            'to' => $this->parseDate($validated['to'] ?? null, endOfDay: true),
        ])->paginate((int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE))->withQueryString();

        // mark_read only touches the page being returned — with unread=1, the
        // next identical call (page 1 again) then returns the next batch.
        // The items in this response still show is_read as it was before.
        $marked = 0;
        if ($request->boolean('mark_read')) {
            $ids = $articles->getCollection()->where('is_read', false)->pluck('id');
            $marked = $ids->isEmpty() ? 0 : Article::whereIn('id', $ids)->update(['is_read' => true]);
        }

        return ArticleResource::collection($articles)->additional([
            'meta' => ['marked_read' => $marked],
        ]);
    }

    public function fetchNow(): JsonResponse
    {
        Cache::put('news_fetch_requested', true, now()->addMinutes(10));

        return response()->json([
            'queued' => true,
            'message' => 'Fetch queued. It starts within about 1 minute; poll /api/v1/status to see when it is done.',
            'last_fetched_at' => $this->lastFetchedAt(),
        ], 202);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'last_fetched_at' => $this->lastFetchedAt(),
            'fetch_pending' => Cache::has('news_fetch_requested'),
            'unread_count' => Article::where('is_read', false)->count(),
            'categories' => Feed::CATEGORIES,
        ]);
    }

    /**
     * A date-only value ("2026-09-29") means the start of that day for `from`
     * and the end of that day for `to`, so from=X&to=X means "that whole day".
     * Anything with a time/offset is used as given. Stored times are UTC.
     */
    private function parseDate(?string $value, bool $endOfDay): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = Carbon::parse($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $date = $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date->utc();
    }

    private function lastFetchedAt(): ?string
    {
        $max = Feed::max('last_fetched_at');

        return $max ? Carbon::parse($max)->toIso8601String() : null;
    }
}
