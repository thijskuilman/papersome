<?php

namespace App\Filament\Resources\Collections\Schemas;

use App\Enums\CoverTemplate;
use App\Filament\Resources\Collections\CollectionResourceService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CollectionForm
{

    public static function configure(Schema $schema): Schema
    {
        $collectionResourceService = app(CollectionResourceService::class);

        return $schema
            ->components([

                Grid::make()->columns(7)->schema([
                    Tabs::make('Tabs')
                        ->columnSpan(5)
                        ->tabs([
                            Tab::make('General')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->maxLength(255),

                                    $collectionResourceService->getSourcesField(),

                                    $collectionResourceService->getScheduleField(),
                                ]),

                            Tab::make('Booklore')
                                ->schema([
                                    TextInput::make('booklore_retention_hours')
                                        ->label('Booklore retention')
                                        ->suffix('hours')
                                        ->default(8)
                                        ->helperText('After how many hours should a Booklore book be pruned?')
                                        ->integer()
                                        ->step(1)
                                        ->minValue(0),
                                ]),
                        ]),

                    Section::make()->columnSpan(2)->components([
                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true),

                        Radio::make('cover_template')
                            ->label('Cover style')
                            ->options(CoverTemplate::class),

                    ]),
                ])->columnSpanFull(),
            ]);
    }
}
