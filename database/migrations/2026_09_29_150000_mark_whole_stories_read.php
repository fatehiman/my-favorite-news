<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Read state became per story (see Article::markStoriesRead()). Before that,
 * only the shown copy of a story was marked read, so a story could have both
 * read and unread copies. Treat any story with a read copy as read.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('articles')
            ->where('is_read', false)
            ->whereIn('article_cluster_id', DB::table('articles')->where('is_read', true)->whereNotNull('article_cluster_id')->select('article_cluster_id'))
            ->update(['is_read' => true]);
    }

    public function down(): void
    {
        // Data-only change; the old per-copy state can't be restored.
    }
};
