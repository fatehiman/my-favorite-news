<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_cluster_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('normalized_title');
            $table->string('url', 2048);
            $table->string('guid')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->unique(['feed_id', 'url'], 'articles_feed_url_unique');
            $table->index('normalized_title');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
