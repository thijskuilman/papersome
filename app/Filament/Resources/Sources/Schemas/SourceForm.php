<?php

namespace App\Filament\Resources\Sources\Schemas;

use App\Enums\SourceFormEvent;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Basic')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([

                                Grid::make()->columns(2)->schema([
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('url')
                                        ->placeholder('Enter a RSS feed')
                                        ->label('Feed URL')
                                        ->url()
                                        ->required()
                                        ->maxLength(255),
                                ]),

                            ]),
                        Tab::make('Parsing settings')
                            ->icon(Heroicon::OutlinedCog)
                            ->schema([
                                TextInput::make('prefix_parse_url')
                                    ->url()
                                    ->maxLength(255),

                                Repeater::make('search_replace')
                                    ->label('Find & replace text in articles')
                                    ->addActionLabel('Add rule')
                                    ->table([
                                        TableColumn::make('Find'),
                                        TableColumn::make('Replace'),
                                    ])->schema([
                                        TextInput::make('find')
                                            ->required()
                                            ->placeholder('Add a term (case-insensitive)'),

                                        TextInput::make('replace')
                                            ->required()
                                            ->placeholder('Add replacement'),
                                    ]),
                            ]),
                        Tab::make('Layout settings')
                            ->icon(Heroicon::RectangleGroup)
                            ->schema([

                                Repeater::make('html_query_filters')
                                    ->afterStateUpdated(fn ($state, $livewire) => $livewire->dispatch(
                                        event: SourceFormEvent::HtmlQueryFiltersUpdated->value,
                                        filters: $state ?? []
                                    ))
                                    ->live()
                                    ->compact()
                                    ->label('Remove HTML elements')
                                    ->addActionLabel('Add filter')
                                    ->reorderable(false)
                                    ->table([
                                        TableColumn::make('Selector'),
                                        TableColumn::make('Query'),
                                    ])
                                    ->schema([
                                        Select::make('selector')
                                            ->label('Selector')
                                            ->columnSpan(1)
                                            ->default('all')
                                            ->options([
                                                'all' => 'Select all',
                                                'first' => 'Select first',
                                            ])
                                            ->required(),
                                        TextInput::make('query')
                                            ->label('Query')
                                            ->placeholder('CSS selector, for example .header, #id, div > p:first-child, [data-attribute="value"]')
                                            ->columnSpan(3)
                                            ->required(),

                                    ])
                                    ->columns(4),

                                View::make('filament.schemas.components.source.layout-settings'),

                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
