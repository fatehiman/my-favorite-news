<?php

namespace Database\Seeders;

use App\Models\Feed;
use Illuminate\Database\Seeder;

/**
 * 7 US news sources x 3 categories = 21 RSS feeds.
 * English-language, non-British. Each feed was fetched and verified by hand
 * before being added here (valid RSS/Atom, on-topic, currently updating).
 */
class FeedSeeder extends Seeder
{
    public function run(): void
    {
        $feeds = [
            ['name' => 'NPR', 'category' => 'politics', 'url' => 'https://feeds.npr.org/1014/rss.xml'],
            ['name' => 'NPR', 'category' => 'economy', 'url' => 'https://feeds.npr.org/1017/rss.xml'],
            ['name' => 'NPR', 'category' => 'it', 'url' => 'https://feeds.npr.org/1019/rss.xml'],

            ['name' => 'NBC News', 'category' => 'politics', 'url' => 'https://feeds.nbcnews.com/nbcnews/public/politics'],
            ['name' => 'NBC News', 'category' => 'economy', 'url' => 'https://feeds.nbcnews.com/nbcnews/public/business'],
            ['name' => 'NBC News', 'category' => 'it', 'url' => 'https://feeds.nbcnews.com/nbcnews/public/tech'],

            ['name' => 'ABC News', 'category' => 'politics', 'url' => 'https://abcnews.com/abcnews/politicsheadlines'],
            ['name' => 'ABC News', 'category' => 'economy', 'url' => 'https://abcnews.com/abcnews/moneyheadlines'],
            ['name' => 'ABC News', 'category' => 'it', 'url' => 'https://abcnews.com/abcnews/technologyheadlines'],

            ['name' => 'Fox News', 'category' => 'politics', 'url' => 'https://moxie.foxnews.com/google-publisher/politics.xml'],
            ['name' => 'Fox Business', 'category' => 'economy', 'url' => 'https://moxie.foxbusiness.com/google-publisher/economy.xml'],
            ['name' => 'Fox News', 'category' => 'it', 'url' => 'https://moxie.foxnews.com/google-publisher/tech.xml'],

            ['name' => 'CBS News', 'category' => 'politics', 'url' => 'https://www.cbsnews.com/latest/rss/politics'],
            ['name' => 'CBS News', 'category' => 'economy', 'url' => 'https://www.cbsnews.com/latest/rss/moneywatch'],
            ['name' => 'CBS News', 'category' => 'it', 'url' => 'https://www.cbsnews.com/latest/rss/technology'],

            ['name' => 'The Hill', 'category' => 'politics', 'url' => 'https://thehill.com/homenews/feed/'],
            ['name' => 'The Hill', 'category' => 'economy', 'url' => 'https://thehill.com/business/feed/'],
            ['name' => 'The Hill', 'category' => 'it', 'url' => 'https://thehill.com/policy/technology/feed/'],

            ['name' => 'Washington Examiner', 'category' => 'politics', 'url' => 'https://www.washingtonexaminer.com/feed/'],
            ['name' => 'Washington Examiner', 'category' => 'economy', 'url' => 'https://www.washingtonexaminer.com/policy/economy/feed/'],
            ['name' => 'Washington Examiner', 'category' => 'it', 'url' => 'https://www.washingtonexaminer.com/policy/technology/feed/'],
        ];

        foreach ($feeds as $feed) {
            Feed::updateOrCreate(
                ['name' => $feed['name'], 'category' => $feed['category']],
                ['url' => $feed['url'], 'is_active' => true]
            );
        }
    }
}
