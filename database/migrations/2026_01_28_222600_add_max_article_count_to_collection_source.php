<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_source', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_article_count')->default(5)->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('collection_source', function (Blueprint $table): void {
            $table->dropColumn('max_article_count');
        });
    }
};
