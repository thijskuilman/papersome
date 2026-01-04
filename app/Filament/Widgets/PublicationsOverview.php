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
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Publication::query())
            ->heading('Latest Publications')
            ->columns([
                Stack::make([
                    ImageColumn::make('cover_image_path')
                        ->extraImgAttributes(['style' => 'width: 100%; height: 250px; object-fit: cover; margin-bottom: 15px;'])
                        ->getStateUsing(function () {
                            $covers = [
                                'https://files.coverscdn.com/imgix-covers/retro-gamer-magazine-issue-280-362-cover.webp',
                                'https://files.coverscdn.com/imgix-covers/dungeon-masters-guide-magazine-game-masters-guide-volume-two-362-cover.webp',
                                'https://files.coverscdn.com/covers/290227/mid/0000.jpg',
                                'https://files.coverscdn.com/covers/288447/mid/0000.jpg',
                                'https://kagi.com/proxy/pdf_120d8fba-a16f-11ed-930a-318ed05ccad0.jpg?c=lytl_0lDwgEXQ-9v3kF1Mpvw0H3QmobApAdas7gjEmW-9XSnC-zX55gkdQdvy_dx7egFix7v_PSB3qXPDMXcvfAD_6geZdTZTRj8p8X8i5vujFcjQ3oYWZ4Xpv0yA3yhGMPadtfTUNUkB-IQ0pUSQl0440DxhR-rzLCch4gMcd1YD1j6mJhZcEDdxnP6bdHM',
                            ];

                            return $covers[array_rand($covers)];
                        }),

                    TextColumn::make('collection.name')
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold),

                    TextColumn::make('created_at')->date(),
                ])
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
