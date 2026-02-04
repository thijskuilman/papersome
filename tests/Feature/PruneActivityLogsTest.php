<?php

use App\Console\Commands\PruneActivityLogs;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('prunes activity logs older than 30 days', function (): void {
    $old = ActivityLog::query()->create([
        'type' => 'info',
        'channel' => 'publication',
        'message' => 'Old message',
        'data' => null,
        'created_at' => now()->subDays(31),
        'updated_at' => now()->subDays(31),
    ]);

    $recent = ActivityLog::query()->create([
        'type' => 'info',
        'channel' => 'publication',
        'message' => 'Recent message',
        'data' => null,
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    artisan(PruneActivityLogs::class)->assertSuccessful();

    expect(ActivityLog::query()->find($old->id))->toBeNull();
    expect(ActivityLog::query()->find($recent->id))->not()->toBeNull();
});

it('accepts a custom days threshold', function (): void {
    $older = ActivityLog::query()->create([
        'type' => 'info',
        'channel' => 'publication',
        'message' => 'Older than 7 days',
        'data' => null,
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $newer = ActivityLog::query()->create([
        'type' => 'info',
        'channel' => 'publication',
        'message' => 'Newer than 7 days',
        'data' => null,
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ]);

    artisan(PruneActivityLogs::class, ['--days' => 7])->assertSuccessful();

    expect(ActivityLog::query()->find($older->id))->toBeNull();
    expect(ActivityLog::query()->find($newer->id))->not()->toBeNull();
});
