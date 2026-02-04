<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'activity-logs:prune {--days=30 : Delete logs older than this many days}';

    /**
     * The console command description.
     */
    protected $description = 'Prune activity logs older than a specified number of days (default: 30).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('The --days option must be at least 1.');

            return \Symfony\Component\Console\Command\Command::INVALID;
        }

        $deleted = ActivityLog::query()
            ->where('created_at', '<', CarbonImmutable::now()->subDays($days))
            ->delete();

        $this->info("Pruned {$deleted} activity log(s) older than {$days} day(s).");

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
