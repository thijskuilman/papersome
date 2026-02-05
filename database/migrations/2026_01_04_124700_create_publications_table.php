<?php

use App\Models\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Collection::class, 'collection_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('epub_file_path')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('booklore_book_id')->nullable();
            $table->ulid('tag');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
