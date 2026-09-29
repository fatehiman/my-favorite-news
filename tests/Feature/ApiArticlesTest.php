<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCluster;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiArticlesTest extends TestCase
{
    use RefreshDatabase;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::forceCreate(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@example.com', 'password' => 'secret']);
        $this->key = $user->rotateApiKey();
    }

    private function makeArticle(Feed $feed, array $attrs = []): Article
    {
        static $n = 0;
        $n++;

        return Article::forceCreate($attrs + [
            'feed_id' => $feed->id,
            'title' => "Story $n",
            'normalized_title' => "story $n",
            'url' => "https://example.com/$n",
            'guid' => "guid-$n",
            'published_at' => now()->subMinutes($n),
        ]);
    }

    private function feed(string $category = 'politics', string $name = 'Outlet'): Feed
    {
        return Feed::forceCreate(['name' => $name, 'category' => $category, 'url' => "https://example.com/$name/$category.rss"]);
    }

    private function api(string $method, string $uri, array $data = [])
    {
        return $this->json($method, $uri, $data, ['X-API-Key' => $this->key]);
    }

    public function test_it_rejects_missing_or_wrong_key(): void
    {
        $this->getJson('/api/v1/articles')->assertUnauthorized();
        $this->getJson('/api/v1/articles', ['X-API-Key' => 'nfp_wrong'])->assertUnauthorized();
    }

    public function test_bearer_header_works_too(): void
    {
        $this->getJson('/api/v1/articles', ['Authorization' => "Bearer {$this->key}"])->assertOk();
    }

    public function test_rotating_the_key_disables_the_old_one(): void
    {
        $old = $this->key;
        User::first()->rotateApiKey();

        $this->getJson('/api/v1/articles', ['X-API-Key' => $old])->assertUnauthorized();
    }

    public function test_default_page_size_is_10(): void
    {
        $feed = $this->feed();
        foreach (range(1, 23) as $i) {
            $this->makeArticle($feed);
        }

        $this->api('GET', '/api/v1/articles')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 23)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_mark_read_with_unread_walks_through_the_list_page_by_page(): void
    {
        $politics = $this->feed('politics');
        $other = $this->feed('politics', 'Other outlet');
        foreach (range(1, 23) as $i) {
            $cluster = ArticleCluster::forceCreate(['representative_title' => "c$i", 'category' => 'politics', 'sources_count' => 2, 'is_important' => true]);
            $this->makeArticle($politics, ['article_cluster_id' => $cluster->id]);
        }
        $this->makeArticle($other); // not important — must never be returned or marked

        $seen = [];
        foreach ([10, 10, 3, 0] as $expected) {
            $response = $this->api('POST', '/api/v1/articles', ['important' => 1, 'unread' => 1, 'mark_read' => 1])
                ->assertOk()
                ->assertJsonCount($expected, 'data')
                ->assertJsonPath('meta.marked_read', $expected);

            foreach ($response->json('data') as $item) {
                $this->assertFalse($item['is_read']);
                $this->assertNotContains($item['id'], $seen);
                $seen[] = $item['id'];
            }
        }

        $this->assertSame(1, Article::where('is_read', false)->count());
    }

    public function test_mark_read_only_marks_the_returned_page(): void
    {
        $feed = $this->feed();
        foreach (range(1, 15) as $i) {
            $this->makeArticle($feed);
        }

        $this->api('GET', '/api/v1/articles?page=2&mark_read=1')->assertJsonPath('meta.marked_read', 5);

        $this->assertSame(10, Article::where('is_read', false)->count());
    }

    public function test_category_and_unread_combine(): void
    {
        $it = $this->feed('it');
        $economy = $this->feed('economy');
        $this->makeArticle($it);
        $this->makeArticle($it, ['is_read' => true]);
        $this->makeArticle($economy);

        $this->api('GET', '/api/v1/articles?category=it&unread=1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'it')
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_date_range_filter(): void
    {
        $feed = $this->feed();
        $this->makeArticle($feed, ['published_at' => '2026-09-20 10:00:00']);
        $this->makeArticle($feed, ['published_at' => '2026-09-25 23:30:00']);
        $this->makeArticle($feed, ['published_at' => '2026-09-28 08:00:00']);

        $this->api('GET', '/api/v1/articles?from=2026-09-21&to=2026-09-25')->assertJsonCount(1, 'data');
        $this->api('GET', '/api/v1/articles?from=2026-09-25')->assertJsonCount(2, 'data');
        $this->api('GET', '/api/v1/articles?to=2026-09-25T12:00:00Z')->assertJsonCount(1, 'data');
        $this->api('GET', '/api/v1/articles?from=2026-09-26&to=2026-09-25')->assertUnprocessable();
    }

    public function test_invalid_arguments_are_rejected(): void
    {
        $this->api('GET', '/api/v1/articles?category=sports')->assertUnprocessable();
        $this->api('GET', '/api/v1/articles?per_page=500')->assertUnprocessable();
    }

    public function test_content_only_included_when_asked(): void
    {
        $this->makeArticle($this->feed(), ['content' => 'Full body']);

        $this->api('GET', '/api/v1/articles')->assertJsonMissingPath('data.0.content');
        $this->api('GET', '/api/v1/articles?include_content=1')->assertJsonPath('data.0.content', 'Full body');
    }

    public function test_fetch_now_queues_a_fetch(): void
    {
        $this->api('POST', '/api/v1/fetch-now')->assertStatus(202)->assertJsonPath('queued', true);

        $this->assertTrue(Cache::has('news_fetch_requested'));
        $this->api('GET', '/api/v1/status')->assertOk()->assertJsonPath('fetch_pending', true);
    }
}
