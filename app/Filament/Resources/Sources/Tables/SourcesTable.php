<?php

namespace App\Filament\Resources\Sources\Tables;

use App\Models\Source;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\View\View;

class SourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->formatStateUsing(fn(string $state, Source $record): View => view(
                            'source-column',
                            ['state' => $state, 'source' => $record],
                        ))->searchable(),
                ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->emptyStateHeading('No sources yet')
            ->emptyStateDescription('Create your first source to start fetching feeds.')
            ->emptyStateIcon('heroicon-o-rss')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create first source')
                    ->icon('heroicon-o-plus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
