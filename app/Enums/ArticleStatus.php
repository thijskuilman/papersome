<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum ArticleStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Failed = 'failed';
    case Parsed = 'parsed';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Failed => 'Failed',
            self::Parsed => 'Parsed',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Failed => Heroicon::OutlinedExclamationCircle,
            self::Parsed => Heroicon::OutlinedCheckCircle,
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Failed => 'danger',
            self::Parsed => 'success',
        };
    }
}
