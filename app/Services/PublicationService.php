<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Publication;

class PublicationService
{
    public function __construct(private readonly EpubService $epubService) {}

    public function createPublication(Collection $collection): Publication {
        $epubPath = $this->epubService->createEpubFor($collection);

        return Publication::query()->create([
            'collection_id' => $collection->id,
            'epub_file_path' => $epubPath,
        ]);
    }
}
