<?php

namespace App\Filament\Resources\Sources\Tables;

use App\Models\Article;
use App\Models\Source;
use App\Services\FeedService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('type'),

                TextColumn::make('name')->label('Name'),

                TextColumn::make('articles_count')->counts('articles'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('feed')
                    ->action(fn (Source $source) => app(FeedService::class)->storeArticlesFromSource($source))
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
