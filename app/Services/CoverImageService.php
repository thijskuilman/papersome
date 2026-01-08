<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CoverImageService
{
    public function generateCoverImage(Publication $publication): ?string
    {
        $fileName = 'cover-' . Str::slug($publication->collection->name . '-' . $publication->id) . '.png';
        $relativePath = 'epubs/covers/' . $fileName;
        $fullPath = Storage::disk('public')->path($relativePath);

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $url = URL::route('cover.generate', [
            'publication' => $publication->id
        ]);

        try {
            Browsershot::url($url)
                ->windowSize(600, 800)
                ->waitUntilNetworkIdle()
                ->save($fullPath);

            return $relativePath;
        } catch (\Exception $e) {
            Log::error('Error while generating cover image with Browsershot: '.$e->getMessage());
            return null;
        }
    }
}
