<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class CoverImageService
{
    public function generateCoverImage(Publication $publication): ?string
    {
        $fileName = 'cover-'.Str::slug($publication->collection->name.'-'.$publication->id).'.png';
        $relativePath = 'epubs/covers/'.$fileName;
        $fullPath = Storage::disk('public')->path($relativePath);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $url = URL::route('cover.generate', [
            'publication' => $publication->id,
        ]);

        try {
            $browsershot = Browsershot::url($url)
                ->addChromiumArguments([
                    'no-sandbox',
                    'disable-setuid-sandbox',
                ])
                ->windowSize(600, 800)
                ->waitUntilNetworkIdle();

            if ($chromePath = config('browsershot.chrome_path')) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->save($fullPath);

            return $relativePath;
        } catch (\Exception $e) {
            Log::error('Error while generating cover image with Browsershot: '.$e->getMessage());

            return null;
        }
    }
}
