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
                        Tab::make('Booklore')
                            ->schema([
                                Action::make('sign_into_booklore')
                                    ->visible($this->applicationSettings->booklore_username === null)
                                    ->label('Sign into Booklore')
                                    ->modal()
                                    ->color('gray')
                                    ->icon(Heroicon::OutlinedKey)
                                    ->fillForm(function (): void {
                                        $this->fill([
                                            'booklore_username' => $this->applicationSettings->booklore_username ?? '',
                                            'booklore_url' => $this->applicationSettings->booklore_url ?? '',
                                        ]);
                                    })
                                    ->action(function (array $data, ManageSettings $manageSettings): void {

                                        $bookloreUrl = rtrim((string) $data['booklore_url'], '/');

                                        try {
                                            app(BookloreApiService::class)->login(
                                                $data['booklore_username'],
                                                $data['booklore_password'],
                                                $bookloreUrl,
                                            );

                                            Notification::make()
                                                ->success()
                                                ->title('Connected to Booklore')
                                                ->body('You can now use Booklore as a delivery channel')
                                                ->send();

                                            // TODO: Graceful alternative to refreshing the page
                                            $this->js('window.location.reload()');
                                        } catch (\Exception $exception) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Connection error to Booklore')
                                                ->body($exception->getMessage())
                                                ->send();
                                            $this->halt();
                                        }
                                    })
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('booklore_url')
                                                ->label('Booklore URL')
                                                ->default($this->applicationSettings->booklore_url)
                                                ->placeholder('https://localhost:6060')
                                                ->requiredWith(['booklore_username', 'booklore_password']),

                                            TextInput::make('booklore_username')
                                                ->label('Username')
                                                ->placeholder('Booklore username')
                                                ->requiredWith(['booklore_url', 'booklore_password']),

                                            TextInput::make('booklore_password')
                                                ->label('Password')
                                                ->placeholder('Booklore password')
                                                ->requiredWith(['booklore_url', 'booklore_username'])
                                                ->password(),
                                        ]),
                                    ]),

                                FluxCalloutEntry::make('booklore_connected_message')
                                    ->color('emerald')
                                    ->icon('check-circle')
                                    ->hiddenLabel()
                                    ->visible($this->applicationSettings->booklore_username !== null)
                                    ->state(
                                        new HtmlString("The user {$this->applicationSettings->booklore_username} is connected to Booklore at
                                            <a target='_blank' href='{$this->applicationSettings->booklore_url}'>
                                                {$this->applicationSettings->booklore_url}
                                            </a>
                                        ")),

                                Select::make('booklore_library_id')
                                    ->label('Booklore library')
                                    ->required()
                                    ->live()
                                    ->helperText('Which library should be used for storing publications?')
                                    ->visible($this->applicationSettings->booklore_username !== null)
                                    ->options(function () {
                                        try {
                                            return collect(app(BookloreApiService::class)->getLibraries())
                                                ->mapWithKeys(fn ($library): array => [$library['id'] => $library['name']])
                                                ->toArray();
                                        } catch (\Exception) {
                                            //
                                        }
                                    }),

                                Select::make('booklore_path_id')
                                    ->label('Library path')
                                    ->required()
                                    ->visible(fn (Get $get): bool => $get('booklore_library_id') !== null)
                                    ->options(fn (Get $get): array => $this->getLibraryPathOptions(
                                        $get('booklore_library_id')
                                    )),

                                TextInput::make('booklore_retention_hours')
                                    ->label('Booklore retention hours')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('How long after requesting deletion should a Booklore book actually be deleted.')
                                    ->visible($this->applicationSettings->booklore_username !== null),

                                Action::make('disconnect_booklore')
                                    ->visible($this->applicationSettings->booklore_username !== null)
                                    ->label('Disconnect Booklore')
                                    ->link()
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(function (): void {
                                        app(BookloreApiService::class)->disconnect();
                                        $this->js('window.location.reload()');
                                    }),

                            ]),
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
        return collect(app(BookloreService::class)->getLibraryPaths(libraryId: $libraryId))
            ->mapWithKeys(fn ($library): array => [$library['id'] => $library['path']])
            ->toArray();
    }
}
