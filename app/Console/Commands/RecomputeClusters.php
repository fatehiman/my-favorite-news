<?php

namespace App\Console\Commands;

use App\Models\ArticleCluster;
use Illuminate\Console\Command;

class RecomputeClusters extends Command
{
    protected $signature = 'news:recompute-clusters';

    protected $description = 'Recompute sources_count/is_important for existing clusters by distinct outlet name';

    public function handle(): int
    {
        $count = 0;

        ArticleCluster::with('articles.feed')->get()->each(function ($cluster) use (&$count) {
            $sourcesCount = $cluster->articles->pluck('feed.name')->unique()->count();

            $cluster->update([
                'sources_count' => $sourcesCount,
                'is_important' => $sourcesCount >= 2,
            ]);

            $count++;
        });

        $this->info("Recomputed {$count} clusters.");

        return self::SUCCESS;
    }
}
