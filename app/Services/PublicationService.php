<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Collection;
use App\Models\Publication;
use Illuminate\Support\Collection as SupportCollection;

readonly class PublicationService
{

    private ?Publication $previousPublication;

    public function __construct(private EpubService $epubService)
    {
    }

    public function createPublication(Collection $collection): ?Publication
    {
        $this->previousPublication = $collection->publications()->latest()->first();

        $publication = Publication::create([
            'collection_id' => $collection->id,
            'title' => $collection->name . ' - ' . now()->toDateString()
        ]);

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
        $articlesQuery = Article::query()
            ->whereIn('source_id', $collection->sources->pluck('id'));

        if ($this->previousPublication) {
            $lastArticle = $this->previousPublication->articles()->latest()->first();
            if ($lastArticle) {
                $articlesQuery->where('created_at', '>', $lastArticle->created_at);
            }
        }

        return $articlesQuery->latest()->take(10)->get();
    }
}
