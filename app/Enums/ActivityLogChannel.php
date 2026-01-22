<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActivityLogChannel: string implements HasLabel
{
    case GeneratePublications = 'generate_publications';
    case ProcessScheduledBookloreDeletions = 'process_scheduled_booklore_deletions';
    case PruneExpiredPublications = 'prune_expired_publications';
    case Booklore = 'booklore';
    case Publication = 'publication';
    case Epub = 'epub';
    case CoverImage = 'cover_image';
    case Readability = 'readability';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GeneratePublications => 'Generate Publications',
            self::ProcessScheduledBookloreDeletions => 'Process Scheduled Booklore Deletions',
            self::PruneExpiredPublications => 'Prune Expired Publications',
            self::Booklore => 'Booklore',
            self::Publication => 'Publication',
            self::Epub => 'EPUB',
            self::CoverImage => 'Cover Image',
            self::Readability => 'Readability',
        };
    }
}
