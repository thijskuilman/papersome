<?php

namespace App\Models;

use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Publication extends Model
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    #[\Override]
    public function uniqueIds(): array
    {
        return ['tag'];
    }

    protected $guarded = [];

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
