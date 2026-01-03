<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use App\Models\Collection;
use App\Services\EpubService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download-epub')
                ->label('Download .epub')
                ->action(function (Collection $collection) {
                    $filePath = app(EpubService::class)->createEpubFor($collection);

                    if ($filePath) {
                        return response()->download($filePath);
                    }

                    return null;
                }),

            DeleteAction::make(),
        ];
    }
}
