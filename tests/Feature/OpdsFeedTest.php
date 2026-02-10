<?php

use App\Models\Collection;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns an OPDS feed with user publications', function (): void {
    $user = User::factory()->create(['name' => 'Alice']);

    $collection = Collection::factory()->for($user)->create();

    $pubA = Publication::factory()->create([
        'collection_id' => $collection->id,
        'title' => 'Morning Digest',
        'epub_file_path' => 'epubs/morning.epub',
        'cover_image' => 'covers/morning.jpg',
    ]);

    $pubB = Publication::factory()->create([
        'collection_id' => $collection->id,
        'title' => 'Evening Digest',
        'epub_file_path' => 'epubs/evening.epub',
    ]);

    $response = $this->get(route('opds.user', ['user' => $user]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/atom+xml;profile=opds-catalog;kind=acquisition; charset=UTF-8');

    $response->assertSee('<feed', false)
        ->assertSee('Alice Publications', false)
        ->assertSee('<entry>', false)
        ->assertSee($pubA->title, false)
        ->assertSee($pubB->title, false)
        ->assertSee('application/epub+zip', false)
        ->assertSee('rel="http://opds-spec.org/image"', false)
        ->assertSee('image/jpeg', false);
});

it('returns an empty OPDS feed when user has no publications', function (): void {
    $user = User::factory()->create(['name' => 'Bob']);

    // Create a collection without publications for completeness
    Collection::factory()->for($user)->create();

    $response = $this->get(route('opds.user', ['user' => $user]));

    $response->assertOk();
    $response->assertSee('<feed', false)
        ->assertSee('Bob Publications', false)
        ->assertDontSee('<entry>', false);
});

it('returns 404 for a missing user', function (): void {
    $response = $this->get(route('opds.user', ['user' => '99999999']));

    $response->assertNotFound();
});
