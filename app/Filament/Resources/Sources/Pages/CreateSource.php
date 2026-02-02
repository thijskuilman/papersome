<?php

namespace App\Filament\Resources\Sources\Pages;

use App\Enums\SourceFormEvent;
use App\Filament\Resources\Sources\SourceResource;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class CreateSource extends CreateRecord
{
    protected static string $resource = SourceResource::class;

    protected static bool $canCreateAnother = false;

    protected $listeners = [
        'submit-form' => 'create',
    ];

    #[\Override]
    protected function getFormActions(): array
    {
        return [];
    }

    #[\Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make()
                            ->extraAttributes([
                                'class' => 'mx-auto max-w-lg w-full',
                            ])
                            ->columns(1)
                            ->schema([

                                Hidden::make('name_manually_set')
                                    ->default(false)
                                    ->dehydrated(false),

                                TextInput::make('url')
                                    ->label('Enter link to a RSS feed')
                                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                        if (filter_var($state, FILTER_VALIDATE_URL)) {
                                            $this->dispatch(
                                                event: SourceFormEvent::StartRssVerification->value,
                                                url: $state
                                            );

                                            if (! $get('name_manually_set')) {
                                                $set('name', $this->suggestRssFeedName(url: $state));
                                            }
                                        } else {
                                            $this->dispatch(event: SourceFormEvent::ResetRssVerification->value);
                                        }
                                    })
                                    ->placeholder('https://website.com/rss.xml')
                                    ->url()
                                    ->live(debounce: 400)
                                    ->required(),

                                TextInput::make('name')
                                    ->placeholder('Name of this feed')
                                    ->required()
                                    ->afterStateUpdated(function (callable $set): void {
                                        $set('name_manually_set', true);
                                    }),

                                View::make('filament.schemas.components.source.create-form'),
                            ]),

                    ]),
            ]);
    }

    private function suggestRssFeedName(string $url): string
    {
        $parsed = parse_url($url);

        if (! $parsed || empty($parsed['host'])) {
            return 'Unknown Feed';
        }

        $hostParts = explode('.', $parsed['host']);
        $publisher = ucfirst($hostParts[max(0, count($hostParts) - 2)]);

        $segments = array_values(array_filter(explode('/', trim($parsed['path'] ?? '', '/'))));
        $lastSegment = preg_replace('/\.(xml|rss|atom)$/i', '', end($segments) ?: '');

        $ignored = ['rss', 'feed', 'feeds', 'atom'];

        if (in_array(strtolower((string) $lastSegment), $ignored, true)) {
            $lastSegment = '';
        }

        if ($lastSegment !== '') {
            $lastSegment = ucwords(str_replace(['-', '_'], ' ', $lastSegment));

            return "{$publisher} - {$lastSegment}";
        }

        return $publisher;
    }

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $url = $data['url'];
        $scheme = parse_url((string) $url, PHP_URL_SCHEME);
        $host = parse_url((string) $url, PHP_URL_HOST);
        $base = $scheme && $host ? "$scheme://$host" : null;
        $data['icon'] = Favicon::fetch($base ?? $data['url'])?->getFaviconUrl();

        return $data;
    }
}
