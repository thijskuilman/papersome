<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),

            $this->getSaveFormAction()
                ->submit(null)
                ->action(fn () => $this->save()),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
