<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BriefingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_briefing_filters_and_shows_mark_all_button(): void
    {
        $user = User::forceCreate(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@example.com', 'password' => 'secret']);
        $it = Feed::forceCreate(['name' => 'Outlet', 'category' => 'it', 'url' => 'https://example.com/it.rss']);
        $economy = Feed::forceCreate(['name' => 'Outlet', 'category' => 'economy', 'url' => 'https://example.com/eco.rss']);
        foreach ([[$it, 'IT story'], [$economy, 'Economy story']] as [$feed, $title]) {
            Article::forceCreate(['feed_id' => $feed->id, 'title' => $title, 'normalized_title' => $title, 'url' => "https://example.com/$title", 'guid' => $title, 'published_at' => now()]);
        }

        $this->actingAs($user)->get('/articles?category=it')
            ->assertOk()
            ->assertSee('IT story')
            ->assertDontSee('Economy story')
            ->assertSee('Mark all as read');
    }

    public function test_api_page_creates_and_rotates_the_key(): void
    {
        $user = User::forceCreate(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@example.com', 'password' => 'secret']);

        $this->actingAs($user)->get('/api-key')->assertOk()->assertSee('Create API key');

        $this->actingAs($user)->post('/api-key/rotate')->assertRedirect('/api-key');
        $first = $user->fresh()->api_key;
        $this->assertStringStartsWith('nfp_', $first);

        $this->actingAs($user)->post('/api-key/rotate');
        $this->assertNotSame($first, $user->fresh()->api_key);
        $this->actingAs($user)->get('/api-key')->assertOk()->assertSee('Rotate key');
    }
}
