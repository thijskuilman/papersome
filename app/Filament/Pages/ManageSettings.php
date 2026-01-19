<?php

namespace App\Filament\Pages;

use App\Services\BookloreApiService;
use App\Settings\ApplicationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
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

    private ApplicationSettings $applicationSettings;

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

                                        try {
                                            app(BookloreApiService::class)->login(
                                                $data['booklore_username'],
                                                $data['booklore_password'],
                                                $data['booklore_url'],
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

                                TextEntry::make('booklore_connected_message')
                                    ->color('success')
                                    ->icon(Heroicon::OutlinedCheckCircle)
                                    ->iconColor('success')
                                    ->hiddenLabel()
                                    ->visible($this->applicationSettings->booklore_username !== null)
                                    ->html()
                                    ->state("The user {$this->applicationSettings->booklore_username} is connected to Booklore at <a target='_blank' href='{$this->applicationSettings->booklore_url}'>{$this->applicationSettings->booklore_url}</a>."),

                                Select::make('booklore_library_id')
                                    ->label('Booklore library')
                                    ->required()
                                    ->helperText('At which library should the articles be added?')
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

                                TextInput::make('booklore_deletion_retention_hours')
                                    ->label('Booklore retention hours')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('How long after requesting deletion should a Booklore book actually be deleted.')
                                    ->visible($this->applicationSettings->booklore_username !== null),

                                Action::make('disconnect_booklore')
                                    ->visible($this->applicationSettings->booklore_username !== null)
                                    ->label('Disconnect Booklore')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(function (): void {
                                        app(BookloreApiService::class)->disconnect();
                                        $this->js('window.location.reload()');
                                    }),

                            ]),
                        Tab::make('Instapaper')
                            ->schema([
                                // ...
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
