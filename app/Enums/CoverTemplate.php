<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CoverTemplate: string implements HasLabel
{
    case ClassicNewspaper = 'classic_newspaper';

    public function getView(): string
    {
        return match ($this) {
            self::ClassicNewspaper => 'newspaper.classic-newspaper',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ClassicNewspaper => 'Classic newspaper',
        };
    }

}
