<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Collection;
use App\Models\Publication;
use App\Models\Source;
use App\Services\BookloreService;
use App\Services\FeedService;
use App\Services\PublicationService;
use App\Services\ReadabilityService;
use App\Settings\ApplicationSettings;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateDailyPublications extends Command
{
    protected $signature = 'publications:generate {--limit=5 : Max articles to fetch per source run}';

    protected $description = 'Fetch, parse, publish, and optionally upload publications to Booklore for all enabled collections';

    public function __construct(
        public FeedService         $feedService,
        public ReadabilityService  $readabilityService,
        public PublicationService  $publicationService,
        public BookloreService     $bookloreService,
        public ApplicationSettings $settings,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $startedAt = Carbon::now();
        $limit = (int)$this->option('limit');

        $collections = $this->loadEnabledCollections();

        if ($collections->isEmpty()) {
            $this->info('No enabled collections found.');

            return self::SUCCESS;
        }

        foreach ($collections as $collection) {
            $this->processCollection($collection, $limit);
        }

        $this->info(sprintf('Done. (took %ss)', Carbon::now()->diffInSeconds($startedAt)));

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
        $this->info("Processing collection: {$collection->name}");

        $this->fetchAndStoreArticlesForSources($collection, $limit);

        $this->parsePendingArticlesForSources($collection);

        $publication = $this->createPublicationForCollection($collection);

        if (!$publication) {
            $this->line('No new articles to publish.');

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
                $this->line("Fetching articles for source #{$source->id} - {$source->name}");
                $this->feedService->storeArticlesFromSource($source, $limit);
                $count++;
            } catch (\Throwable $e) {
                Log::error('Error fetching articles', [
                    'collection_id' => $collection->id,
                    'source_id' => $source->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $this->line("Fetched for {$count} sources.");
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
                $this->line("Parsing article #{$article->id} - {$article->title}");
                $this->readabilityService->parseArticleContent($article);
                $parsed++;
            } catch (\Throwable $e) {
                Log::error('Error parsing article', [
                    'article_id' => $article->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $this->line("Parsed {$parsed} articles.");
    }

    /**
     * Create a publication for the collection including EPUB generation.
     */
    private function createPublicationForCollection(Collection $collection): ?Publication
    {
        try {
            $publication = $this->publicationService->createPublication($collection);
        } catch (\Throwable $e) {
            Log::error('Error creating publication', [
                'collection_id' => $collection->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($publication) {
            $this->info("  Created publication #{$publication->id}: {$publication->title}");
        }

        return $publication;
    }

    /**
     * Upload the publication to Booklore, if credentials are configured.
     */
    private function syncToBooklore(Publication $publication): void
    {
        $hasBooklore = !empty($this->settings->booklore_username)
            && !empty($this->settings->booklore_library_id)
            && !empty($publication->epub_file_path);

        if (!$hasBooklore) {
            $this->line('  Skipping Booklore upload (credentials not configured).');

            return;
        }

        try {
            $this->line('  Uploading publication to Booklore...');
            $this->bookloreService->uploadPublication($publication);
            $this->info('  Uploaded to Booklore successfully.');
        } catch (\Throwable $e) {
            Log::error('Error uploading publication to Booklore', [
                'publication_id' => $publication->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
