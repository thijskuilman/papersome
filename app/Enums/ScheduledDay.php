<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ScheduledDay: string implements HasLabel
{
    case Mon = 'mon';
    case Tue = 'tue';
    case Wed = 'wed';
    case Thu = 'thu';
    case Fri = 'fri';
    case Sat = 'sat';
    case Sun = 'sun';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Mon => 'Monday',
            self::Tue => 'Tuesday',
            self::Wed => 'Wednesday',
            self::Thu => 'Thursday',
            self::Fri => 'Friday',
            self::Sat => 'Saturday',
            self::Sun => 'Sunday',
        };
    }
}
