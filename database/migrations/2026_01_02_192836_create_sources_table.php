<?php

use App\Enums\SourceType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->string('icon')->nullable();
            $table->string('name');
            $table->string('type')->default(SourceType::Rss->value);
            $table->string('url');
            $table->string('prefix_parse_url')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
