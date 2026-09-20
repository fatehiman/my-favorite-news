<?php

namespace App\Services;

use App\Models\Feed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

class RssFetcherService
{
    private const CONTENT_NS = 'http://purl.org/rss/1.0/modules/content/';

    /** Below this length, `content:encoded` is just a caption/teaser, not a real article body. */
    private const MIN_FULL_CONTENT_LENGTH = 800;

    /**
     * Fetch a feed and return a plain array of items: title, url, guid, description,
     * content (full body if the feed provides it, else null), tags, published_at.
     */
    public function fetch(Feed $feed): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'MyFavoriteNews/1.0'])
                ->get($feed->url);

            if (! $response->successful()) {
                throw new \RuntimeException('HTTP status '.$response->status());
            }

            $xml = new SimpleXMLElement($response->body());

            $feed->update(['last_error' => null]);

            return $this->extractItems($xml);
        } catch (Throwable $e) {
            Log::warning("Failed to fetch feed [{$feed->name}]: ".$e->getMessage());
            $feed->update(['last_error' => $e->getMessage()]);

            return [];
        }
    }

    private function extractItems(SimpleXMLElement $xml): array
    {
        $items = [];

        // RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $fullContent = null;
                $contentNode = $item->children(self::CONTENT_NS);
                if (isset($contentNode->encoded)) {
                    $cleaned = $this->cleanHtml((string) $contentNode->encoded, 20000);
                    $fullContent = mb_strlen($cleaned) >= self::MIN_FULL_CONTENT_LENGTH ? $cleaned : null;
                }

                $tags = [];
                foreach ($item->category ?? [] as $category) {
                    $tags[] = trim((string) $category);
                }

                $items[] = [
                    'title' => trim((string) $item->title),
                    'url' => trim((string) $item->link),
                    'guid' => trim((string) ($item->guid ?? $item->link)),
                    'description' => $this->cleanHtml((string) ($item->description ?? ''), 500),
                    'content' => $fullContent,
                    'tags' => array_values(array_filter($tags)),
                    'published_at' => $this->parseDate((string) ($item->pubDate ?? '')),
                ];
            }

            return $items;
        }

        // Atom
        if (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $link = '';
                if (isset($entry->link)) {
                    foreach ($entry->link as $l) {
                        $attrs = $l->attributes();
                        if (! isset($attrs['rel']) || (string) $attrs['rel'] === 'alternate') {
                            $link = (string) $attrs['href'];
                            break;
                        }
                    }
                }

                $tags = [];
                foreach ($entry->category ?? [] as $category) {
                    $attrs = $category->attributes();
                    $tags[] = trim((string) ($attrs['term'] ?? $category));
                }

                $summary = (string) ($entry->summary ?? '');
                $content = (string) ($entry->content ?? '');

                $items[] = [
                    'title' => trim((string) $entry->title),
                    'url' => trim($link),
                    'guid' => trim((string) ($entry->id ?? $link)),
                    'description' => $this->cleanHtml($summary ?: $content, 500),
                    'content' => (function () use ($content) {
                        $cleaned = $content !== '' ? $this->cleanHtml($content, 20000) : '';

                        return mb_strlen($cleaned) >= self::MIN_FULL_CONTENT_LENGTH ? $cleaned : null;
                    })(),
                    'tags' => array_values(array_filter($tags)),
                    'published_at' => $this->parseDate((string) ($entry->updated ?? $entry->published ?? '')),
                ];
            }
        }

        return $items;
    }

    private function cleanHtml(string $html, int $maxLength): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_substr($text, 0, $maxLength);
    }

    private function parseDate(string $date): ?Carbon
    {
        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }
}
