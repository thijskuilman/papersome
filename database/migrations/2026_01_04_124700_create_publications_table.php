<?php

use App\Enums\DeliveryStatus;
use App\Models\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Collection::class, 'collection_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('epub_file_path')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('booklore_delivery_status')
                ->default(DeliveryStatus::Pending->value);
            $table->string('booklore_book_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
