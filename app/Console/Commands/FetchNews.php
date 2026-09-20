<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Feed;
use App\Models\Tag;
use App\Services\DuplicateDetectorService;
use App\Services\RssFetcherService;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    protected $signature = 'news:fetch';

    protected $description = 'Fetch all active RSS feeds, store new articles, and flag duplicates as important';

    public function handle(RssFetcherService $fetcher, DuplicateDetectorService $duplicates): int
    {
        $feeds = Feed::where('is_active', true)->get();

        $this->info("Fetching {$feeds->count()} active feeds...");

        $totalNew = 0;

        foreach ($feeds as $feed) {
            $items = $fetcher->fetch($feed);
            $newForFeed = 0;

            foreach ($items as $item) {
                if ($item['url'] === '' || $item['title'] === '') {
                    continue;
                }

                if (Article::where('feed_id', $feed->id)->where('url', $item['url'])->exists()) {
                    continue;
                }

                $article = Article::create([
                    'feed_id' => $feed->id,
                    'title' => $item['title'],
                    'normalized_title' => $duplicates->normalize($item['title']),
                    'url' => $item['url'],
                    'guid' => $item['guid'],
                    'description' => $item['description'],
                    'content' => $item['content'] ?? null,
                    'published_at' => $item['published_at'],
                ]);

                $duplicates->assignCluster($article);

                if (! empty($item['tags'])) {
                    $tagIds = collect($item['tags'])
                        ->map(fn ($name) => Tag::findOrCreateByName($name)->id);

                    $article->tags()->sync($tagIds);
                }

                $newForFeed++;
            }

            $feed->update(['last_fetched_at' => now()]);

            $this->line("  {$feed->name} ({$feed->category}): {$newForFeed} new");
            $totalNew += $newForFeed;
        }

        $this->info("Done. {$totalNew} new articles fetched.");

        $this->call('news:cleanup');

        return self::SUCCESS;
    }
}
