<?php

namespace App\Console\Commands;

use App\Enums\ActivityLogChannel;
use App\Models\Publication;
use App\Services\BookloreService;
use App\Services\LogService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class PruneExpiredPublications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publications:prune-retention';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete publications that exceeded their collection retention window.';

    public function __construct(public BookloreService $bookloreService, public LogService $logService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $now = now();

        /** @var Builder $query */
        $query = Publication::query()
            ->with('collection')
            ->whereHas('collection', function ($q): void {
                $q->whereNotNull('publication_retention_hours')
                    ->where('publication_retention_hours', '>', 0);
            })
            ->where(function (Builder $q): void {
                $q->whereHas('collection', function (Builder $sub): void {
                    $sub->whereNotNull('publication_retention_hours');
                });
            });

        $expired = $query->get()->filter(function (Publication $pub) use ($now): bool {
            $hours = (int) ($pub->collection->publication_retention_hours ?? 0);
            if ($hours <= 0) {
                return false;
            }

            return $pub->created_at?->addHours($hours)->lte($now) === true;
        });

        if ($expired->isEmpty()) {
            return;
        }

        $count = 0;
        foreach ($expired as $record) {
            if (is_numeric($record->booklore_book_id)) {
                $this->bookloreService->unassignFromKoboShelves((int) $record->booklore_book_id);
                $this->bookloreService->requestBookDeletion((int) $record->booklore_book_id);
            }

            $record->delete();
            $count++;
        }

        if ($count > 0) {
            $this->logService->info(
                message: "Pruned {$count} expired publications.",
                channel: ActivityLogChannel::PruneExpiredPublications,
                command: $this,
            );
        }
    }
}
