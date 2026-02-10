<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Support\Collection;

class FeedService
{
    public function __construct(private readonly SearchReplaceService $searchReplaceService) {}

    public function storeArticlesFromSource(Source $source, int $articleLimit = 5): void
    {
        $items = $this->fetchRssItems($source->url);

        foreach ($items->take(limit: $articleLimit) as $item) {

            $title = $source->search_replace ? $this->searchReplaceService->apply(
                rules: $source->search_replace,
                subject: $item['title'],
            ) : $item['title'];

            Article::firstOrCreate(
                ['url' => $item['permalink']],
                [
                    'source_id' => $source->id,
                    'title' => $title,
                    'excerpt' => $item['description'] ?? '',
                    'published_at' => $item['date'],
                ]
            );
        }
    }

    public function fetchRssItems(string $feedUrl): Collection
    {
        $feed = \Feeds::make(feedUrl: $feedUrl, limit: 5);

        return collect($feed->get_items())->map(fn ($item): array => [
            'permalink' => $item->get_permalink(),
            'title' => $item->get_title(),
            'description' => $item->get_description(),
            'date' => $item->get_date(),
        ]);
    }
}
