<?php

use App\Services\BookloreApiService;
use App\Services\BookloreService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('only deletes scheduled Booklore books after the configured delay', function (): void {
    $bookId = 123456;

    $apiMock = mock(BookloreApiService::class);

    app(BookloreService::class)->requestBookDeletion(bookId: $bookId);

    $apiMock->shouldNotReceive('deleteBooks');
    $this->artisan('booklore:process-deletion-requests')->assertSuccessful();

    $this->travel(7)->hours();
    $this->travel(59)->minutes();
    $apiMock->shouldNotReceive('deleteBooks');
    $this->artisan('booklore:process-deletion-requests')->assertSuccessful();

    $this->travel(2)->minutes();
    $apiMock->shouldReceive('deleteBooks')->once()->with([$bookId]);
    $this->artisan('booklore:process-deletion-requests')->assertSuccessful();
});
