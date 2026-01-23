<?php

use App\Console\Commands\ScanAndDispatchPublications;
use App\Models\Collection;
use App\Settings\ApplicationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan as ArtisanFacade;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('dispatches generation only for due enabled collections', function (): void {
    // Set the application timezone for the dispatcher via settings binding.
    $settings = app(ApplicationSettings::class);
    $settings->timezone = 'UTC';
    app()->instance(ApplicationSettings::class, $settings);

    // Freeze time at 12:00 UTC on a Friday (2026-01-23)
    $now = Carbon::create(2026, 1, 23, 12, 0, 0, 'UTC');
    Carbon::setTestNow($now);

    // Due and enabled
    $due = Collection::factory()->create([
        'enabled' => true,
        'schedule' => [
            ['repeat_type' => 'daily', 'time' => '12:00'],
        ],
    ]);

    // Not due (different time)
    Collection::factory()->create([
        'enabled' => true,
        'schedule' => [
            ['repeat_type' => 'daily', 'time' => '13:00'],
        ],
    ]);

    // Due but disabled
    Collection::factory()->create([
        'enabled' => false,
        'schedule' => [
            ['repeat_type' => 'daily', 'time' => '12:00'],
        ],
    ]);

    // Ensure the cache lock always succeeds in tests
    Cache::shouldReceive('lock')->andReturn(new class
    {
        public function get(): bool
        {
            return true;
        }
    });

    // Expect Artisan::call to be invoked exactly once for the due collection
    ArtisanFacade::shouldReceive('call')
        ->once()
        ->with('publications:generate', ['--collection' => $due->id])
        ->andReturn(0);

    $exit = app(ScanAndDispatchPublications::class)->handle();
    expect($exit)->toBe(0);
});
