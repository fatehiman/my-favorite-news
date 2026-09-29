<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // api_key is stored encrypted (so the API page can show it again);
            // api_key_hash is a sha256 of it, used to look the user up per request.
            $table->text('api_key')->nullable();
            $table->string('api_key_hash', 64)->nullable()->unique();
            $table->timestamp('api_key_created_at')->nullable();
            $table->timestamp('api_key_last_used_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['api_key_hash']);
            $table->dropColumn(['api_key', 'api_key_hash', 'api_key_created_at', 'api_key_last_used_at']);
        });
    }
};
