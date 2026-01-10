<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SourceType: string implements HasIcon, HasLabel
{
    case RSS = 'rss';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::RSS => 'RSS',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::RSS => Heroicon::Rss,
        };
    }
}
