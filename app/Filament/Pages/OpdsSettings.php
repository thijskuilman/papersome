<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class OpdsSettings extends Page
{
    protected string $view = 'filament.pages.opds-settings';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-code-bracket';

    protected static string|null|\UnitEnum $navigationGroup = 'Delivery channels';

    protected static ?string $navigationLabel = 'OPDS';

    protected ?string $heading = 'OPDS';

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Connected';
    }
}
