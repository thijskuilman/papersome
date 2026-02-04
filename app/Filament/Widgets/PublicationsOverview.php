<?php

namespace App\Filament\Widgets;

use App\Models\Publication;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicationsOverview extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Publication::query()
                ->with('collection')
                ->whereHas('collection', function ($q): void {
                    $q->where('user_id', auth()->id());
                })
            )
            ->heading('Latest Publications')
            ->emptyStateHeading('There are no publications yet')
            ->columns([
                Stack::make([
                    ImageColumn::make('cover_image')
                        ->disk('public')
                        ->extraImgAttributes(['style' => 'width: 100%; height: 240px; object-fit: cover; margin-bottom: 15px;']),

                    TextColumn::make('collection.name')
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold),

                    TextColumn::make('created_at')->date(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->filters([
                //
            ])
            ->contentGrid([
                'md' => 4,
                'xl' => 5,
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('download-epub')
                    ->label('Download .epub')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(fn (Publication $record): ?BinaryFileResponse => $record->download()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
