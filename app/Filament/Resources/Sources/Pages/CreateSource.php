<?php

namespace App\Filament\Resources\Sources\Pages;

use App\Filament\Resources\Sources\SourceResource;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateSource extends CreateRecord
{
    protected static string $resource = SourceResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('E.g., BBC News or Tech Blog')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->placeholder('Enter a RSS feed')
                    ->label('Feed URL')
                    ->url()
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
