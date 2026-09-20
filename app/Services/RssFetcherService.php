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
    /**
     * Fetch a feed and return a plain array of items: title, url, guid, description, published_at.
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
                $items[] = [
                    'title' => trim((string) $item->title),
                    'url' => trim((string) $item->link),
                    'guid' => trim((string) ($item->guid ?? $item->link)),
                    'description' => $this->cleanDescription((string) ($item->description ?? '')),
                    'published_at' => $this->parseDate((string) ($item->pubDate ?? '')),
                ];
            }

            return $items;
        }

        // Atom
        $namespaces = $xml->getNamespaces(true);
        if (isset($namespaces['']) && str_contains($namespaces[''], 'Atom')) {
            // fallthrough to generic atom handling below
        }

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

                $items[] = [
                    'title' => trim((string) $entry->title),
                    'url' => trim($link),
                    'guid' => trim((string) ($entry->id ?? $link)),
                    'description' => $this->cleanDescription((string) ($entry->summary ?? $entry->content ?? '')),
                    'published_at' => $this->parseDate((string) ($entry->updated ?? $entry->published ?? '')),
                ];
            }
        }

        return $items;
    }

    private function cleanDescription(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_substr($text, 0, 500);
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
