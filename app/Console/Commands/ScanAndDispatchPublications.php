<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Settings\ApplicationSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ScanAndDispatchPublications extends Command
{
    protected $signature = 'publications:scan-and-dispatch';

    protected $description = 'Scan collections and dispatch publication generation for those due right now based on their schedule';

    public function __construct(public ApplicationSettings $settings)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $timezone = $this->settings->timezone ?: config('app.timezone');
        $now = Carbon::now(timezone: $timezone);

        Collection::query()
            ->where('enabled', true)
            ->orderBy('id')
            ->chunkById(200, function ($collections) use ($now) {
                foreach ($collections as $collection) {
                    if (! $collection->isDueAt($now)) {
                        continue;
                    }

                    $lock = Cache::lock("publication-generation:{$collection->id}:{$now->format('YmdHi')}", 90);

                    if ($lock->get()) {
                        Artisan::call('publications:generate', [
                            '--collection' => $collection->id,
                        ]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
