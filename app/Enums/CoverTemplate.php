<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CoverTemplate: string implements HasLabel
{
    case ClassicNewspaper = 'classic_newspaper';
    case GamingMagazine = 'gaming_magazine';


    public function getView(): string
    {
        return match ($this) {
            self::ClassicNewspaper => 'newspaper.classic-newspaper',
            self::GamingMagazine => 'magazine.gaming',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ClassicNewspaper => 'Classic newspaper',
            self::GamingMagazine => 'Gaming magazine',
        };
    }

}
