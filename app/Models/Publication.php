<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Publication extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'booklore_delivery_status' => DeliveryStatus::class,
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'publication_article')
            ->withTimestamps();
    }

    public function download(): ?BinaryFileResponse
    {
        if (! is_string($this->epub_file_path)) {
            return null;
        }

        $path = Storage::disk('public')->path($this->epub_file_path);

        if (! file_exists($path)) {
            return null;
        }

        return response()->download($path, basename($this->epub_file_path));
    }
}
