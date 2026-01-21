<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Models\Publication;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class CoverImageService
{
    public function __construct(private readonly LogService $logService) {}

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
            $this->logService->info(
                message: 'Generating cover image',
                channel: ActivityLogChannel::CoverImage,
                data: [
                    'publication_id' => $publication->id,
                    'url' => $url,
                    'path' => $fullPath,
                ],
            );

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

            $this->logService->success(
                message: 'Cover image generated successfully',
                channel: ActivityLogChannel::CoverImage,
                data: [
                    'publication_id' => $publication->id,
                    'relative_path' => $relativePath,
                ],
            );

            return $relativePath;
        } catch (\Exception $e) {
            $this->logService->error(
                message: 'Error while generating cover image with Browsershot',
                channel: ActivityLogChannel::CoverImage,
                data: [
                    'publication_id' => $publication->id,
                    'error' => $e->getMessage(),
                ],
            );

            return null;
        }
    }
}
