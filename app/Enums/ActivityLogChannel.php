<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActivityLogChannel: string implements  HasLabel
{
    case GeneratePublications = 'generate_publications';
    case ProcessScheduledBookloreDeletions = 'process_scheduled_booklore_deletions';
    case PruneExpiredPublications = 'prune_expired_publications';
    case Booklore = 'booklore';

    public function getLabel(): string|null
    {
        return match ($this) {
            self::GeneratePublications => 'Generate Publications',
            self::ProcessScheduledBookloreDeletions => 'Process Scheduled Booklore Deletions',
            self::PruneExpiredPublications => 'Prune Expired Publications',
            self::Booklore => 'Booklore',
        };
    }
}
