<?php

namespace App\Filament\Resources\Sources\Schemas;

use App\Enums\SourceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->components([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label('Type')
                        ->options(SourceType::class)
                        ->required(),
                ])->columnSpanFull()
            ]);
    }
}
