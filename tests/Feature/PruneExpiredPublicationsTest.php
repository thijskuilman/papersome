<?php

use App\Console\Commands\PruneExpiredPublications;
use App\Models\Collection;
use App\Models\Publication;
use App\Services\BookloreService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('prunes publications older than the collection retention window', function (): void {
    $collection = Collection::factory()->create([
        'publication_retention_hours' => 24,
    ]);

    $expired = Publication::query()->create([
        'collection_id' => $collection->id,
        'title' => 'Old Pub',
        'booklore_book_id' => '123',
        'created_at' => now()->subHours(25),
        'updated_at' => now()->subHours(25),
    ]);

    $fresh = Publication::query()->create([
        'collection_id' => $collection->id,
        'title' => 'Fresh Pub',
        'booklore_book_id' => '456',
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    $svc = mock(BookloreService::class);
    $svc->shouldReceive('unassignFromKoboShelves')->once()->with(123);
    $svc->shouldReceive('scheduleBookDeletion')->once()->with(123, 7);

    artisan(PruneExpiredPublications::class)->assertSuccessful();

    expect(Publication::query()->find($expired->id))->toBeNull();
    expect(Publication::query()->find($fresh->id))->not()->toBeNull();
});

it('does not prune when retention is null or zero', function (): void {
    $nullRetention = Collection::factory()->create([
        'publication_retention_hours' => null,
    ]);

    $zeroRetention = Collection::factory()->create([
        'publication_retention_hours' => 0,
    ]);

    $oldA = Publication::query()->create([
        'collection_id' => $nullRetention->id,
        'title' => 'Null Retention Old',
        'booklore_book_id' => '789',
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    $oldB = Publication::query()->create([
        'collection_id' => $zeroRetention->id,
        'title' => 'Zero Retention Old',
        'booklore_book_id' => '101',
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    $svc = mock(BookloreService::class);
    $svc->shouldNotReceive('unassignFromKoboShelves');
    $svc->shouldNotReceive('scheduleBookDeletion');

    artisan(PruneExpiredPublications::class)->assertSuccessful();

    expect(Publication::query()->find($oldA->id))->not()->toBeNull();
    expect(Publication::query()->find($oldB->id))->not()->toBeNull();
});
