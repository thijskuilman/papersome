<?php

namespace App\Filament\Pages;

use App\Enums\Timezone;
use App\Settings\ApplicationSettings;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ManageSettings extends SettingsPage
{

    protected static string $settings = ApplicationSettings::class;

    protected ?string $heading = 'Settings';

    protected static bool $shouldRegisterNavigation = false;

    #[\Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Date & Time')
                            ->schema([
                                Select::make('timezone')
                                    ->label('Timezone')
                                    ->required()
                                    ->searchable()
                                    ->options(
                                        collect(Timezone::cases())
                                            ->mapWithKeys(fn (Timezone $tz): array => [$tz->value => $tz->value])
                                            ->toArray()
                                    )
                                    ->default(config('app.timezone')),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
