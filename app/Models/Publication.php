<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Publication extends Model
{
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

    public function download(): ?BinaryFileResponse
    {
        $path = storage_path('app/'. $this->epub_file_path);

        if (! is_string($this->epub_file_path) || ! file_exists($path)) {
            return null;
        }

        return response()->download($path, basename($this->epub_file_path));
    }
}
