<?php

namespace App\Filament\Resources\Collections\Tables;

use App\Models\Source;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        $sourcesExist = Source::exists();
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sources.name')
                    ->badge()
                    ->sortable(),
                IconColumn::make('enabled')
                    ->boolean()
                    ->label('Enabled')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading($sourcesExist ? 'No collections yet' : 'A source is required')
            ->emptyStateDescription($sourcesExist
                ? 'Create your first collection to start generating publications.'
                : 'Create a source first so collections have content to pull from.'
            )
            ->emptyStateIcon($sourcesExist ? Heroicon::OutlinedNewspaper : Heroicon::OutlinedExclamationTriangle)
            ->emptyStateActions([
                $sourcesExist
                    ? CreateAction::make()
                    ->label('Create first collection')
                    ->icon('heroicon-o-plus')

                    : CreateAction::make()
                    ->label('Create first source')
                    ->url(route('filament.admin.resources.sources.create'))
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
