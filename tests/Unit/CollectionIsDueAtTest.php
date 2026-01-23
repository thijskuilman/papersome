<?php

use App\Models\Collection;
use Illuminate\Support\Carbon;

it('returns true for daily schedules at the exact time', function (): void {
    $now = Carbon::create(2026, 1, 23, 12, 0, 0, 'UTC');
    Carbon::setTestNow($now);

    $collection = new Collection([
        'enabled' => true,
        'schedule' => [
            ['repeat_type' => 'daily', 'time' => '12:00'],
        ],
    ]);

    expect($collection->isDueAt($now))->toBeTrue();
});

it('returns false for daily schedules at a different time', function (): void {
    $now = Carbon::create(2026, 1, 23, 13, 0, 0, 'UTC');
    Carbon::setTestNow($now);

    $collection = new Collection([
        'enabled' => true,
        'schedule' => [
            ['repeat_type' => 'daily', 'time' => '12:00'],
        ],
    ]);

    expect($collection->isDueAt($now))->toBeFalse();
});

it('matches specific days when time and day align', function (): void {
    // 2026-01-26 is a Monday
    $now = Carbon::create(2026, 1, 26, 7, 0, 0, 'UTC');
    Carbon::setTestNow($now);

    $collection = new Collection([
        'enabled' => true,
        'schedule' => [
            ['repeat_type' => 'specific', 'scheduled_days' => ['mon', 'thu'], 'time' => '07:00'],
        ],
    ]);

    expect($collection->isDueAt($now))->toBeTrue();
});

it('does not match specific days when day differs', function (): void {
    // 2026-01-27 is a Tuesday
    $now = Carbon::create(2026, 1, 27, 7, 0, 0, 'UTC');
    Carbon::setTestNow($now);

    $collection = new Collection([
        'enabled' => true,
        'schedule' => [
            ['repeat_type' => 'specific', 'scheduled_days' => ['mon', 'thu'], 'time' => '07:00'],
        ],
    ]);

    expect($collection->isDueAt($now))->toBeFalse();
});

it('returns false when schedule is empty or invalid', function (): void {
    $now = Carbon::create(2026, 1, 23, 12, 0, 0, 'UTC');
    Carbon::setTestNow($now);

    $collectionA = new Collection([
        'enabled' => true,
        'schedule' => [],
    ]);

    $collectionB = new Collection([
        'enabled' => true,
        'schedule' => null,
    ]);

    expect($collectionA->isDueAt($now))->toBeFalse();
    expect($collectionB->isDueAt($now))->toBeFalse();
});
