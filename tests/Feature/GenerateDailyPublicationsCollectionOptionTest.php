<?php

use App\Console\Commands\GenerateDailyPublications;
use App\Models\Collection;
use App\Models\Source;
use App\Services\BookloreService;
use App\Services\FeedService;
use App\Services\LogService;
use App\Services\PublicationService;
use App\Services\ReadabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('processes only the specified collection when using --collection', function (): void {
    $collectionA = Collection::factory()->create(['enabled' => true]);
    $collectionB = Collection::factory()->create(['enabled' => true]);

    $sourceA = Source::factory()->create();
    $sourceB = Source::factory()->create();

    $collectionA->sources()->attach($sourceA->id);
    $collectionB->sources()->attach($sourceB->id);

    // Mock dependencies to avoid heavy work and set expectations
    $feed = mock(FeedService::class);
    $feed->shouldReceive('storeArticlesFromSource')
        ->once()
        ->withArgs(fn (Source $source, int $limit): bool => $source->id === $sourceA->id && $limit === 5);

    $pub = mock(PublicationService::class);
    $pub->shouldReceive('createPublication')
        ->once()
        ->withArgs(fn (Collection $c): bool => $c->id === $collectionA->id)
        ->andReturnNull();

    // Other services are not important for this branch; ignore missing methods.
    mock(ReadabilityService::class)->shouldIgnoreMissing();
    mock(BookloreService::class)->shouldIgnoreMissing();
    mock(LogService::class)->shouldIgnoreMissing();

    artisan(GenerateDailyPublications::class, [
        '--collection' => (string) $collectionA->id,
    ])->assertSuccessful();
});
