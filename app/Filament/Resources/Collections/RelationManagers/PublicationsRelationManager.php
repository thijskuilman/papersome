<?php

namespace App\Filament\Resources\Collections\RelationManagers;

use App\Models\Publication;
use App\Services\BookloreService;
use App\Services\PublicationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
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
                Stack::make([
                    ImageColumn::make('cover_image')
                        ->disk('public')
                        ->extraImgAttributes([
                            'style' => 'width: 100%; height: 310px; object-fit: cover; margin-bottom: 15px;',
                        ]),

                    TextColumn::make('collection.name')
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold),

                    TextColumn::make('created_at')
                        ->date()
                        ->extraAttributes(['style' => 'margin-bottom: 10px;']),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->contentGrid([
                'md' => 3,
                'xl' => 4,
            ])
            ->recordActions([
                Action::make('download-epub')
                    ->label('Download')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(fn (Publication $record): ?BinaryFileResponse => $record->download()),

                DeleteAction::make()
                    ->before(function (Publication $record): void {
                        $bookloreService = app(BookloreService::class);

                        if ($record->booklore_book_id) {
                            $bookloreService->unassignFromKoboShelves($record->booklore_book_id);
                            $bookloreService->requestBookDeletion($record->booklore_book_id);
                        }
                    }),
            ])
            ->emptyStateHeading('No publications yet')
            ->headerActions([
                Action::make('create-publication')
                    ->label('Create publication')
                    ->action(function () {
                        $publication = app(PublicationService::class)->createPublication(collection: $this->ownerRecord);

                        return $publication->download();
                    }),
            ])
            ->emptyStateDescription('Generate an EPUB for this collection to see publications here.');
    }
}
