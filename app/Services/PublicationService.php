<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Publication;
use Illuminate\Support\Collection as SupportCollection;

readonly class PublicationService
{
    public function __construct(private EpubService $epubService)
    {
    }

    public function createPublication(Collection $collection): ?Publication
    {
        $publication = Publication::create([
            'collection_id' => $collection->id,
        ]);

        // Attach articles
        $articles = $this->retrieveArticles($collection);
        if ($articles->isEmpty()) {
            return null;
        }
        $publication->articles()->sync($articles->pluck('id'));

        // Generate cover
        $publication->cover_image = app(CoverImageService::class)
            ->generateCoverImage(publication: $publication);
        $publication->saveQuietly();

        // Generate and attach EPUB
        $publication->epub_file_path = $this->epubService->createEpubFor(
            publication: $publication,
            articles: $articles
        );
        $publication->saveQuietly();

        return $publication;
    }


    private function retrieveArticles(Collection $collection): SupportCollection
    {
        // TODO: Algorithm to retrieve articles from sources
        $articles = [];
        foreach ($collection->sources as $source) {
            foreach ($source->articles as $article) {
                $articles[] = $article;
            }
        }
        return collect($articles);
    }
}
