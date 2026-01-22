<?php

namespace App\Console\Commands;

use App\Enums\ActivityLogChannel;
use App\Services\BookloreApiService;
use App\Services\LogService;
use App\Settings\ApplicationSettings;
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
        public ApplicationSettings $settings,
        public LogService $logService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->logService->info(
            message: 'Processing Booklore deletion requests...',
            channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
            command: $this,
        );

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
            $this->logService->info(
                message: 'No deletion requests are due at this time.',
                channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
                command: $this,
            );

            return;
        }

        try {
            $this->bookloreApiService->deleteBooks($bookIdsToDelete);
        } catch (\Exception $e) {
            $this->logService->error(
                message: 'Something went wrong while deleting books from Booklore: '.$e->getMessage().'',
                channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
                command: $this,
                data: [
                    'book_ids_to_delete' => $bookIdsToDelete,
                ]
            );
        }

        $this->logService->info(
            message: 'Deleted '.count($bookIdsToDelete).' books from Booklore.',
            channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
            command: $this,
        );
    }
}
