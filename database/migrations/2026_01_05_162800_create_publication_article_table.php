<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_article', function (Blueprint $table): void {
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['publication_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_article');
    }
};
