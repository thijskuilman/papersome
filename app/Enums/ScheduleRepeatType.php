<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ScheduleRepeatType: string implements HasLabel
{
    case Daily = 'daily';
    case Specific = 'specific';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Daily => 'Every day',
            self::Specific => 'Specific days',
        };
    }
}
