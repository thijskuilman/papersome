<?php

namespace App\Filament\Pages;

use App\Settings\ApplicationSettings;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = ApplicationSettings::class;

    protected ?string $heading = 'Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 2;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Booklore')
                            ->schema([
                                TextInput::make('booklore_url'),
                                TextInput::make('booklore_username'),
                                TextInput::make('booklore_password')
                                    ->password(),

                                //                TextInput::make('booklore_access_token'),
                                //                TextInput::make('booklore_refresh_token'),
                                //                DateTimePicker::make('booklore_access_token_expires_at'),
                            ]),
                        Tab::make('Instapaper')
                            ->schema([
                                // ...
                            ]),
                    ])->columnSpanFull()
            ]);
    }
}
