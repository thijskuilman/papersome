<?php

namespace App\Console\Commands\Booklore;

use App\Enums\ActivityLogChannel;
use App\Models\User;
use App\Services\BookloreApiService;
use App\Services\LogService;
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

        $overrideHours = config('papersome.booklore_retention_hours');

        // Process per user to respect per-user credentials and retention
        $userIds = DB::table('booklore_deletion_requests')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->all();

        if (empty($userIds)) {
            $this->logService->info(
                message: 'No deletion requests are queued.',
                channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
                command: $this,
            );

            return;
        }

        $totalDeleted = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $hours = is_null($overrideHours)
                ? ($user->booklore_retention_hours ?? 8)
                : (int) $overrideHours;

            $threshold = now()->subHours((int) $hours);

            $bookIdsToDelete = DB::table('booklore_deletion_requests')
                ->where('user_id', $user->id)
                ->where('deletion_requested_at', '<=', $threshold)
                ->pluck('book_id')
                ->toArray();

            if (! $bookIdsToDelete) {
                continue;
            }

            try {
                $this->bookloreApiService->deleteBooks($user, $bookIdsToDelete);
                $totalDeleted += count($bookIdsToDelete);

                // Remove processed rows
                DB::table('booklore_deletion_requests')
                    ->where('user_id', $user->id)
                    ->whereIn('book_id', $bookIdsToDelete)
                    ->delete();
            } catch (\Exception $e) {
                $this->logService->error(
                    message: 'Error deleting books from Booklore: '.$e->getMessage().' (user '.$user->id.')',
                    channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
                    command: $this,
                    data: [
                        'user_id' => $user->id,
                        'book_ids_to_delete' => $bookIdsToDelete,
                    ]
                );
            }
        }

        if ($totalDeleted === 0) {
            $this->logService->info(
                message: 'No deletion requests are due at this time.',
                channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
                command: $this,
            );
        } else {
            $this->logService->info(
                message: 'Deleted '.$totalDeleted.' books from Booklore.',
                channel: ActivityLogChannel::ProcessScheduledBookloreDeletions,
                command: $this,
            );
        }
    }
}
