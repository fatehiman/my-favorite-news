<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCluster;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupOldArticles extends Command
{
    protected $signature = 'news:cleanup';

    protected $description = 'Delete articles older than 10 days (by publish date) and any now-empty clusters';

    private const RETENTION_DAYS = 10;

    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays(self::RETENTION_DAYS);

        $deleted = Article::whereRaw('COALESCE(published_at, created_at) < ?', [$cutoff])->delete();

        $emptyClusters = ArticleCluster::whereDoesntHave('articles')->delete();

        $this->info("Deleted {$deleted} articles older than ".self::RETENTION_DAYS." days, and {$emptyClusters} empty clusters.");

        return self::SUCCESS;
    }
}
