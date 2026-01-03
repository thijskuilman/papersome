<?php

use App\Enums\ArticleStatus;
use App\Models\Collection;
use App\Models\Source;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Source::class, 'source_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('image')->nullable();
            $table->longText('excerpt');
            $table->longText('html_content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default(ArticleStatus::Pending->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
