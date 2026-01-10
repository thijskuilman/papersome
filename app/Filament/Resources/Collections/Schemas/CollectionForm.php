<?php

namespace App\Filament\Resources\Collections\Schemas;

use App\Enums\CoverTemplate;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->components([

                    Toggle::make('enabled')
                        ->label('Enabled')
                        ->default(true),

                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Select::make('sources')
                        ->multiple()
                        ->preload()
                        ->relationship('sources', 'name'),

                    TextInput::make('publication_retention_hours')
                        ->label('Publication Retention Hours')
                        ->helperText('For how many hours should a publication be retained?')
                        ->integer()
                        ->step(1)
                        ->minValue(0),

                    Radio::make('cover_template')
                        ->label('Cover style')
                        ->options(CoverTemplate::class),
                ])->columnSpanFull(),
            ]);
    }
}
