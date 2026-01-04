<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum DeliveryStatus: string implements HasIcon, HasLabel
{
    case Pending = 'pending';
    case Failed = 'failed';
    case Delivered = 'delivered';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Failed => 'Failed',
            self::Delivered => 'Delivered',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Failed => Heroicon::ExclamationTriangle,
            self::Delivered => Heroicon::CheckBadge,
        };
    }
}
