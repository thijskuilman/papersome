<?php

namespace App\Filament\Resources\Collections\Schemas;

use App\Enums\DeliveryChannel;
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

                   Select::make('delivery_channel')
                       ->label('Delivery Channel')
                       ->options(DeliveryChannel::class)
                       ->required(),

                   Select::make('sources')
                       ->multiple()
                       ->preload()
                       ->relationship('sources', 'name'),
               ])->columnSpanFull()
            ]);
    }
}
