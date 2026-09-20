<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('representative_title');
            $table->string('category');
            $table->unsignedInteger('sources_count')->default(1);
            $table->boolean('is_important')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_clusters');
    }
};
