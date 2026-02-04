<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum BookloreConnectionStatus implements HasColor, HasLabel
{
    case Connected;
    case IncompleteSetup;
    case NotConnected;

    public function getColor(): string|array|null
    {
        return match ($this) {
            BookloreConnectionStatus::NotConnected => 'gray',
            BookloreConnectionStatus::IncompleteSetup => 'warning',
            BookloreConnectionStatus::Connected => 'success',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            BookloreConnectionStatus::NotConnected => 'Not connected',
            BookloreConnectionStatus::IncompleteSetup => 'Incomplete setup',
            BookloreConnectionStatus::Connected => 'Connected',
        };
    }
}
