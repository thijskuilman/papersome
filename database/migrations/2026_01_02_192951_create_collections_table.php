<?php

use App\Enums\CoverTemplate;
use App\Enums\DeliveryChannel;
use App\Models\Collection;
use App\Models\Source;
use App\Models\User;
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
        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->string('cover_template')->default(CoverTemplate::ClassicNewspaper->value);
            $table->time('cron')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('collection_source', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Collection::class, 'collection_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Source::class, 'source_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['collection_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_source');
        Schema::dropIfExists('collections');
    }
};
