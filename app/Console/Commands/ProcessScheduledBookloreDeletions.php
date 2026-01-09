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
    protected $signature = 'booklore:process-scheduled-deletions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all scheduled Booklore deletions';


    public function __construct(
        public BookloreApiService $bookloreApiService,
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Processing scheduled Booklore deletions...');

        $bookIdsToDelete = DB::table('scheduled_booklore_deletions')
            ->where('delete_at', '<=', now())
            ->pluck('book_id')
            ->toArray();

        if (!$bookIdsToDelete) {
            $this->info('No scheduled deletions at this time.');
            return;
        }

        $this->bookloreApiService->deleteBooks($bookIdsToDelete);

        $this->info("Deleted " . count($bookIdsToDelete) . " books from Booklore.");
    }
}
