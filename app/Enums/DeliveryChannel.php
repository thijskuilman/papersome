<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum DeliveryChannel: string implements HasIcon, HasLabel
{
    case Booklore = 'booklore';
    case Instapaper = 'instapaper';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Booklore => 'Booklore',
            self::Instapaper => 'Instapaper',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Booklore => Heroicon::BookOpen,
            self::Instapaper => Heroicon::PaperAirplane,
        };
    }
}
