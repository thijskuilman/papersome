<?php

namespace App\Console\Commands;

use App\Services\BookloreApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessScheduledBookloreDeletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booklore:process-deletion-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all pending Booklore deletion requests';

    public function __construct(
        public BookloreApiService $bookloreApiService,
        public \App\Settings\ApplicationSettings $settings,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Processing Booklore deletion requests...');

        $overrideHours = config('newspaparr.booklore_retention_hours');
        $hours = is_null($overrideHours)
            ? $this->settings->booklore_retention_hours ?? 8
            : (int) $overrideHours;

        $threshold = now()->subHours($hours);

        $bookIdsToDelete = DB::table('booklore_deletion_requests')
            ->where('deletion_requested_at', '<=', $threshold)
            ->pluck('book_id')
            ->toArray();

        if (! $bookIdsToDelete) {
            $this->info('No deletion requests are due at this time.');

            return;
        }

        $this->bookloreApiService->deleteBooks($bookIdsToDelete);

        $this->info('Deleted '.count($bookIdsToDelete).' books from Booklore.');
    }
}
