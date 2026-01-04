<?php

namespace App\Filament\Resources\Collections\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'publications';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Published at'),

                TextColumn::make('booklore_delivery_status')
                    ->label('Booklore')
                    ->badge()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('download-epub')
                    ->label('Download .epub')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(function ($record): ?BinaryFileResponse {
                        $absolute = storage_path('app/'.$record->epub_file_path);

                        if (! is_string($record->epub_file_path) || ! file_exists($absolute)) {
                            return null;
                        }

                        return response()->download($absolute, basename($record->epub_file_path));
                    }),
            ])
            ->emptyStateHeading('No publications yet')
            ->emptyStateDescription('Generate an EPUB for this collection to see publications here.');
    }
}
