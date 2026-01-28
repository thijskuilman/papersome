<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Enums\CoverTemplate;
use App\Filament\Resources\Collections\CollectionResource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

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
                Wizard::make()->columnSpanFull()->steps([
                    Wizard\Step::make('Basic')->schema([
                        TextInput::make('name')
                            ->label('Collection name')
                            ->placeholder("e.g. 'Daily News' or 'Movie Spotlights'")
                            ->required()
                            ->maxLength(255),

                        Radio::make('cover_template')
                            ->label('Cover style')
                            ->required()
                            ->options(CoverTemplate::class),

                        Repeater::make('qualifications')
                            ->schema([
                                TextEntry::make('page_number')
                                    ->hiddenLabel()
                                    ->state('Page 1')
                                    ->color('gray')
                                    ->size(TextSize::Small),

                            ])
                            ->grid(3),
                    ]),

                    Wizard\Step::make('Content')->schema([

                    ]),

                    Wizard\Step::make('Scheduling')->schema([

                    ]),
                ]),
            ]);
    }
}
