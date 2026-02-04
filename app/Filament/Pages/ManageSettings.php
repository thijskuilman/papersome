<?php

namespace App\Filament\Pages;

use App\Enums\Timezone;
use App\Filament\Infolists\Components\FluxCalloutEntry;
use App\Services\BookloreApiService;
use App\Services\BookloreService;
use App\Settings\ApplicationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ManageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = ApplicationSettings::class;

    protected ?string $heading = 'Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 2;

    private ApplicationSettings $applicationSettings;

    #[\Override]
    public function form(Schema $schema): Schema
    {
        $this->applicationSettings = app(ApplicationSettings::class);

        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        // Booklore settings moved to per-user BookloreSettings page
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

    /**
     * @throws \Exception
     */
    private function getLibraryPathOptions(int $libraryId): array
    {
        return [];
    }
}
