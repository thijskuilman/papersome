<?php

namespace App\Console\Commands;

use App\Enums\ActivityLogChannel;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Collection;
use App\Models\Publication;
use App\Models\Source;
use App\Services\BookloreService;
use App\Services\FeedService;
use App\Services\LogService;
use App\Services\PublicationService;
use App\Services\ReadabilityService;
use App\Settings\ApplicationSettings;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

class GenerateDailyPublications extends Command
{
    protected $signature = 'publications:generate {--limit=5 : Max articles to fetch per source run}';

    protected $description = 'Fetch, parse, publish, and optionally upload publications to Booklore for all enabled collections';

    public function __construct(
        public FeedService $feedService,
        public ReadabilityService $readabilityService,
        public PublicationService $publicationService,
        public BookloreService $bookloreService,
        public ApplicationSettings $settings,
        public LogService $logService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $startedAt = Carbon::now();

        $limit = (int) $this->option('limit');

        $this->logService->info(
            message: 'Starting job for generating publications...',
            channel: ActivityLogChannel::GeneratePublications,
            command: $this,
        );

        $collections = $this->loadEnabledCollections();

        if ($collections->isEmpty()) {

            $this->logService->info(
                message: 'No enabled collections found.',
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
            );

            return self::SUCCESS;
        }

        foreach ($collections as $collection) {
            $this->processCollection($collection, $limit);
        }

        $this->logService->success(
            message: sprintf('Successfully generated publications. (took %ss)', Carbon::now()->diffInSeconds($startedAt)),
            channel: ActivityLogChannel::GeneratePublications,
            command: $this,
        );

        return self::SUCCESS;
    }

    /**
     * Load enabled collections with their sources eager-loaded.
     */
    private function loadEnabledCollections(): EloquentCollection
    {
        return Collection::query()
            ->where('enabled', true)
            ->with(['sources'])
            ->get();
    }

    /**
     * Orchestrates the processing for a single collection.
     */
    private function processCollection(Collection $collection, int $limit): void
    {
        $this->logService->info(
            message: "Processing collection: {$collection->name}",
            channel: ActivityLogChannel::GeneratePublications,
            command: $this,
        );

        $this->fetchAndStoreArticlesForSources($collection, $limit);

        $this->parsePendingArticlesForSources($collection);

        $publication = $this->createPublicationForCollection($collection);

        if (! $publication) {
            $this->logService->info(
                message: "No new articles to publish for {$collection->name}.",
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
            );

            return;
        }

        $this->syncToBooklore($publication);
    }

    /**
     * Fetch & store latest articles for each source in the collection.
     */
    private function fetchAndStoreArticlesForSources(Collection $collection, int $limit): void
    {
        $count = 0;
        /** @var Source $source */
        foreach ($collection->sources as $source) {
            try {
                $this->logService->info(
                    message: "Fetching articles for source #{$source->id} - {$source->name}",
                    channel: ActivityLogChannel::GeneratePublications,
                    command: $this,
                );
                $this->feedService->storeArticlesFromSource($source, $limit);
                $count++;
            } catch (\Throwable $e) {
                $this->logService->error(
                    message: "Fetching articles for source #{$source->id} - {$source->name}",
                    channel: ActivityLogChannel::GeneratePublications,
                    command: $this,
                    data: [
                        'collection_id' => $collection->id,
                        'source_id' => $source->id,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        $this->logService->info(
            message: "Fetched for {$count} sources.",
            channel: ActivityLogChannel::GeneratePublications,
            command: $this,
        );
    }

    /**
     * Parse pending articles belonging to the collection's sources.
     */
    private function parsePendingArticlesForSources(Collection $collection): void
    {
        $sourceIds = $collection->sources->pluck('id');
        $pendingArticles = Article::query()
            ->whereIn('source_id', $sourceIds)
            ->where('status', ArticleStatus::Pending)
            ->latest()
            ->get();

        $parsed = 0;
        foreach ($pendingArticles as $article) {
            try {
                $this->logService->info(
                    message: "Parsing article #{$article->id} - {$article->title}",
                    channel: ActivityLogChannel::GeneratePublications,
                    command: $this,
                );

                $this->readabilityService->parseArticleContent($article);
                $parsed++;
            } catch (\Throwable $e) {
                $this->logService->error(
                    message: 'Error parsing article',
                    channel: ActivityLogChannel::GeneratePublications,
                    command: $this,
                    data: [
                        'article_id' => $article->id,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
        $this->logService->info(
            message: "Parsed {$parsed} articles.",
            channel: ActivityLogChannel::GeneratePublications,
            command: $this,
        );
    }

    /**
     * Create a publication for the collection including EPUB generation.
     */
    private function createPublicationForCollection(Collection $collection): ?Publication
    {
        try {
            $publication = $this->publicationService->createPublication($collection);
        } catch (\Throwable $e) {
            $this->logService->error(
                message: "Error creating publication for collection $collection->name.",
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
                data: [
                    'collection_id' => $collection->id,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }

        if ($publication) {
            $this->logService->info(
                message: "  Created publication #{$publication->id}: {$publication->title}",
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
            );
        }

        return $publication;
    }

    /**
     * Upload the publication to Booklore, if credentials are configured.
     */
    private function syncToBooklore(Publication $publication): void
    {
        $hasBooklore = ! empty($this->settings->booklore_username)
            && ! empty($this->settings->booklore_library_id)
            && ! empty($publication->epub_file_path);

        if (! $hasBooklore) {
            $this->logService->info(
                message: 'Skipping Booklore upload (credentials not configured).',
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
            );

            return;
        }

        try {
            $this->logService->info(
                message: 'Uploading publication to Booklore...',
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
            );

            $this->bookloreService->uploadPublication($publication);

            $this->logService->info(
                message: 'Uploaded to Booklore successfully.',
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
            );

        } catch (\Throwable $e) {
            $this->logService->error(
                message: "Error uploading publication with id {$publication->id} to Booklore: ".$e->getMessage(),
                channel: ActivityLogChannel::GeneratePublications,
                command: $this,
                data: [
                    'publication_id' => $publication->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
