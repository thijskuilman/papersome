<?php

namespace App\Filament\Resources\Sources\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
                            ]),
                        Tab::make('Layout settings')
                            ->icon(Heroicon::RectangleGroup)
                            ->schema([

                                Repeater::make('html_query_filters')
                                    ->label('Remove HTML elements')
                                    ->addActionLabel('Add filter')
                                    ->reorderable(false)
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
                                    ->columns(4)

                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
