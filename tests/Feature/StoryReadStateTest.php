<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use App\Services\DuplicateDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Read state is per story (cluster): reading one outlet's copy marks all
 * copies read, and a copy that arrives later from another outlet is read too.
 */
class StoryReadStateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::forceCreate(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@example.com', 'password' => 'secret']);
        $this->key = $this->user->rotateApiKey();
    }

    /** Creates an article the way RssFetcherService does, then clusters it. */
    private function fetched(string $outlet, string $title, string $publishedAt): Article
    {
        $feed = Feed::firstOrCreate(['name' => $outlet, 'category' => 'politics'], ['url' => "https://example.com/$outlet.rss"]);
        $detector = app(DuplicateDetectorService::class);

        $article = Article::create([
            'feed_id' => $feed->id,
            'title' => $title,
            'normalized_title' => $detector->normalize($title),
            'url' => 'https://example.com/'.md5($outlet.$title),
            'guid' => md5($outlet.$title),
            'published_at' => $publishedAt,
        ]);
        $detector->assignCluster($article);

        return $article->fresh();
    }

    private function unreadCards(): array
    {
        return $this->getJson('/api/v1/articles?unread=1', ['X-API-Key' => $this->key])->json('data');
    }

    public function test_story_read_on_web_stays_read_when_another_outlet_reports_it_later(): void
    {
        $nbc = $this->fetched('NBC News', 'Senate passes budget bill after long debate', '2026-09-29 08:00:00');
        $this->assertCount(1, $this->unreadCards());

        $this->actingAs($this->user)->postJson("/articles/{$nbc->id}/read")->assertOk();

        // Fox reports the same story later: it's newer, so it becomes the shown card.
        $fox = $this->fetched('Fox News', 'Senate passes budget bill after long debate', '2026-09-29 12:00:00');

        $this->assertSame($nbc->article_cluster_id, $fox->article_cluster_id);
        $this->assertTrue($fox->is_read);
        $this->assertSame([], $this->unreadCards());
    }

    public function test_marking_a_card_read_marks_every_copy_of_the_story(): void
    {
        $this->fetched('NBC News', 'Fed holds interest rates steady again', '2026-09-29 08:00:00');
        $this->fetched('Fox News', 'Fed holds interest rates steady again', '2026-09-29 09:00:00');
        $this->fetched('The Hill', 'Unrelated story about city council vote', '2026-09-29 10:00:00');

        $cards = $this->unreadCards();
        $this->assertCount(2, $cards);
        $fedCard = collect($cards)->firstWhere('sources_count', 2);

        $this->actingAs($this->user)->postJson('/articles/read-many', ['ids' => [$fedCard['id']]])->assertOk();

        $this->assertSame(1, Article::where('is_read', false)->count()); // only the unrelated story
    }

    public function test_api_mark_read_marks_whole_stories_and_counts_cards(): void
    {
        $this->fetched('NBC News', 'Fed holds interest rates steady again', '2026-09-29 08:00:00');
        $this->fetched('Fox News', 'Fed holds interest rates steady again', '2026-09-29 09:00:00');

        $this->postJson('/api/v1/articles', ['unread' => 1, 'mark_read' => 1], ['X-API-Key' => $this->key])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.marked_read', 1);

        $this->assertSame(0, Article::where('is_read', false)->count());
        $this->assertSame([], $this->unreadCards());
    }

    public function test_unread_story_still_gets_new_copies_as_unread(): void
    {
        $this->fetched('NBC News', 'Fed holds interest rates steady again', '2026-09-29 08:00:00');
        $fox = $this->fetched('Fox News', 'Fed holds interest rates steady again', '2026-09-29 09:00:00');

        $this->assertFalse($fox->is_read);
        $this->assertCount(1, $this->unreadCards());
    }
}
