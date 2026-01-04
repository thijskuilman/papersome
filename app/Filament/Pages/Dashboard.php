<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Shelf';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBookOpen;
}
