<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('description');
            $table->longText('translation_fa')->nullable()->after('content');
            $table->timestamp('translated_at')->nullable()->after('translation_fa');
            $table->dropColumn(['is_favorite', 'is_deleted']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['content', 'translation_fa', 'translated_at']);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_deleted')->default(false);
        });
    }
};
