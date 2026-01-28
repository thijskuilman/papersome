<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Models\Article;
use App\Models\Collection;
use App\Models\Publication;
use Illuminate\Support\Collection as SupportCollection;

class PublicationService
{
    private ?Publication $previousPublication = null;

    public function __construct(
        private readonly EpubService $epubService,
        private readonly CoverImageService $coverImageService,
        private readonly LogService $logService,
    ) {}

    public function createPublication(Collection $collection): ?Publication
    {
        $this->previousPublication = $collection->publications()->latest()->first();

        $articles = $this->retrieveArticles($collection);

        if ($articles->isEmpty()) {
            $this->logService->info(
                message: 'No articles found for new publication',
                channel: ActivityLogChannel::Publication,
                data: [
                    'collection_id' => $collection->id,
                ],
            );

            return null;
        }

        $publication = Publication::create([
            'collection_id' => $collection->id,
            'title' => $collection->name.' - '.now()->toDateTimeString(),
        ]);

        $publication->articles()->sync($articles->pluck('id'));

        // Generate cover
        $this->logService->info(
            message: 'Generating cover for publication',
            channel: ActivityLogChannel::CoverImage,
            data: [
                'publication_id' => $publication->id,
            ],
        );

        $publication->cover_image = $this->coverImageService->generateCoverImage(publication: $publication);
        $publication->saveQuietly();

        // Generate and attach EPUB
        $this->logService->info(
            message: 'Generating EPUB for publication',
            channel: ActivityLogChannel::Epub,
            data: [
                'publication_id' => $publication->id,
            ],
        );
        $publication->epub_file_path = $this->epubService->createEpubFor(
            publication: $publication,
            articles: $articles
        );
        $publication->saveQuietly();

        $this->logService->success(
            message: 'Publication created',
            channel: ActivityLogChannel::Publication,
            data: [
                'publication_id' => $publication->id,
                'articles_count' => $articles->count(),
                'has_cover' => (bool) $publication->cover_image,
                'has_epub' => (bool) $publication->epub_file_path,
            ],
        );

        return $publication;
    }

    private function retrieveArticles(Collection $collection): SupportCollection
    {
        $lastPublicationTimestamp = $this->previousPublication?->created_at;

        $articles = collect();

        foreach ($collection->sources()->orderByPivot('sort')->get() as $source) {
            $articlesForSource = Article::query()
                ->where('source_id', $source->id)
                ->when($lastPublicationTimestamp, fn ($query) =>
                    $query->where('created_at', '>', $lastPublicationTimestamp)
                )
                ->latest()
                ->take($source->pivot->max_article_count)
                ->get();

            if ($articlesForSource->isNotEmpty()) {
                $articles = $articles->concat($articlesForSource);
            }
        }

        return $articles->unique('id')->values();
    }
}
