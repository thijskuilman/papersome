<?php

namespace App\Filament\Pages;

use App\Dto\BookloreConnection;
use App\Enums\BookloreConnectionStatus;
use App\Services\BookloreService;
use App\Settings\ApplicationSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = ApplicationSettings::class;

    protected ?string $heading = 'Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 2;

    /**
     * @throws \Exception
     */
    protected function afterSave(): void
    {
        try {
            app(BookloreService::class)->getAccessToken();

            Notification::make()
                ->success()
                ->title('Connected to Booklore')
                ->body('You can now use Booklore as a delivery channel')
                ->send();
        } catch (\Exception $exception) {
            Notification::make()
                ->danger()
                ->title('Connection error to Booklore')
                ->body($exception->getMessage())
                ->send();
        }
    }

    private function testBookloreConnection(?string $username, ?string $password, ?string $url): ?BookloreConnection
    {

        if (! $username || ! $password || ! $url) {
            return null;
        }

        try {
            app(BookloreService::class)->getAccessToken(
                storeToken: false,
                username: $username,
                password: $password,
                url: $url,
            );

            return new BookloreConnection(
                status: BookloreConnectionStatus::Success,
            );
        } catch (\Exception $exception) {
            return new BookloreConnection(
                status: BookloreConnectionStatus::Failed,
                message: $exception->getMessage(),
            );
        }
    }

    private function updateBookloreConnectionMessage(Get $get, Set $set): void
    {
        $connection = $this->testBookloreConnection(
            username: $get('booklore_username'),
            password: $get('booklore_password'),
            url: $get('booklore_url'),
        );

        if ($connection === null) {
            return;
        }

        if ($connection->status === BookloreConnectionStatus::Failed) {
            $set('booklore_connection_message', $connection->message);

            return;
        }

        $set('booklore_connection_message', 'Connection successful');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Booklore')
                            ->schema([
                                TextInput::make('booklore_url')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $this->updateBookloreConnectionMessage($get, $set);
                                    })
                                    ->requiredWith(['booklore_username', 'booklore_password']),

                                TextInput::make('booklore_username')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $this->updateBookloreConnectionMessage($get, $set);
                                    })
                                    ->requiredWith(['booklore_url', 'booklore_password']),

                                TextInput::make('booklore_password')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $this->updateBookloreConnectionMessage($get, $set);
                                    })
                                    ->requiredWith(['booklore_url', 'booklore_username'])
                                    ->password(),

                                TextEntry::make('booklore_connection_message')
                                    ->hiddenLabel()
                                    ->state('')
                                    ->color(function (Get $get): ?string {
                                        $message = $get('booklore_connection_message');

                                        if (! $message) {
                                            return null;
                                        }

                                        return $message === 'Connection successful' ? 'success' : 'danger';
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
