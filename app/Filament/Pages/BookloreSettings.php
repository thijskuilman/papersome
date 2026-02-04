<?php

namespace App\Filament\Pages;

use App\Filament\Infolists\Components\FluxCalloutEntry;
use App\Services\BookloreApiService;
use App\Services\BookloreService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class BookloreSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.booklore-settings';

    protected static string|null|\BackedEnum $navigationIcon = 'icon-booklore-gray';


protected static string | BackedEnum | null $activeNavigationIcon = 'icon-booklore';
    protected static string|null|\UnitEnum $navigationGroup = 'Delivery targets';

    protected static ?string $navigationLabel = 'Booklore';

    protected ?string $heading = 'Booklore';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'booklore_url' => $user->booklore_url,
            'booklore_username' => $user->booklore_username,
            'booklore_library_id' => $user->booklore_library_id,
            'booklore_path_id' => $user->booklore_path_id,
            'booklore_retention_hours' => $user->booklore_retention_hours,
        ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        auth()->user()->update($state);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->components([
                Section::make('Connection')
                    ->schema([
                        Action::make('sign_into_booklore')
                            ->visible(! $user?->hasBookloreConnection())
                            ->label('Sign into Booklore')
                            ->modal()
                            ->color('gray')
                            ->icon(Heroicon::OutlinedKey)
                            ->fillForm(function () use ($user): array {
                                return [
                                    'booklore_username' => $user?->booklore_username ?? '',
                                    'booklore_url' => $user?->booklore_url ?? '',
                                ];
                            })
                            ->action(function (array $data): void {
                                $user = Auth::user();
                                $bookloreUrl = rtrim((string) $data['booklore_url'], '/');

                                try {
                                    app(BookloreApiService::class)->login(
                                        user: $user,
                                        username: $data['booklore_username'],
                                        password: $data['booklore_password'],
                                        url: $bookloreUrl,
                                    );

                                    Notification::make()
                                        ->success()
                                        ->title('Connected to Booklore')
                                        ->body('You can now use Booklore as a delivery channel')
                                        ->send();

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
                            ->visible($user?->hasBookloreConnection() === true)
                            ->state(new HtmlString("The user {$user?->booklore_username} is connected to Booklore at
                                <a target='_blank' href='{$user?->booklore_url}'>
                                    {$user?->booklore_url}
                                </a>
                            ")),

                        Select::make('booklore_library_id')
                            ->label('Booklore library')
                            ->required()
                            ->live()
                            ->helperText('Which library should be used for storing publications?')
                            ->visible($user?->hasBookloreConnection() === true)
                            ->options(function () use ($user) {
                                try {
                                    return collect(app(BookloreApiService::class)->getLibraries($user))
                                        ->mapWithKeys(fn ($library): array => [$library['id'] => $library['name']])
                                        ->toArray();
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->afterStateUpdated(function ($state) use ($user): void {
                                if ($user) {
                                    $user->booklore_library_id = $state;
                                    $user->save();
                                }
                            }),

                        Select::make('booklore_path_id')
                            ->label('Library path')
                            ->required()
                            ->visible(fn (Get $get): bool => $get('booklore_library_id') !== null)
                            ->options(function (Get $get) use ($user): array {
                                $libraryId = (int) $get('booklore_library_id');
                                if (! $libraryId || ! $user) {
                                    return [];
                                }
                                try {
                                    return collect(app(BookloreService::class)->getLibraryPaths($user, $libraryId))
                                        ->mapWithKeys(fn ($library): array => [$library['id'] => $library['path']])
                                        ->toArray();
                                } catch (\Exception) {
                                    return [];
                                }
                            })
                            ->afterStateUpdated(function ($state) use ($user): void {
                                if ($user) {
                                    $user->booklore_path_id = $state;
                                    $user->save();
                                }
                            }),

                        TextInput::make('booklore_retention_hours')
                            ->label('Booklore retention hours')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('How long after requesting deletion should a Booklore book actually be deleted.')
                            ->visible($user?->hasBookloreConnection() === true)
                            ->afterStateUpdated(function ($state) use ($user): void {
                                if ($user) {
                                    $user->booklore_retention_hours = (int) $state;
                                    $user->save();
                                }
                            }),

                        Action::make('disconnect_booklore')
                            ->visible($user?->hasBookloreConnection() === true)
                            ->label('Disconnect Booklore')
                            ->link()
                            ->requiresConfirmation()
                            ->color('danger')
                            ->action(function (): void {
                                $user = Auth::user();
                                app(BookloreApiService::class)->disconnect($user);
                                if ($user) {
                                    $user->booklore_library_id = null;
                                    $user->booklore_path_id = null;
                                    $user->save();
                                }
                                $this->js('window.location.reload()');
                            }),
                    ]),
            ])
            ->statePath('data');
    }


}
