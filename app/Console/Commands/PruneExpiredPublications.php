<?php

namespace App\Console\Commands;

use App\Models\Publication;
use App\Services\BookloreService;
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

    public function __construct(public BookloreService $bookloreService)
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

        $expired = $query->get()->filter(function (Publication $pub) use ($now) {
            $hours = (int) ($pub->collection->publication_retention_hours ?? 0);
            if ($hours <= 0) {
                return false;
            }

            return $pub->created_at?->addHours($hours)->lte($now) === true;
        });

        if ($expired->isEmpty()) {
            $this->info('No publications to prune.');

            return;
        }

        $count = 0;
        foreach ($expired as $record) {
            if (is_numeric($record->booklore_book_id)) {
                $this->bookloreService->unassignFromKoboShelves((int) $record->booklore_book_id);
                $this->bookloreService->scheduleBookDeletion((int) $record->booklore_book_id, 7);
            }

            $record->delete();
            $count++;
        }

        $this->info("Pruned {$count} expired publications.");
    }
}
