<?php

namespace App\Filament\Resources\Sources\Pages;

use App\Filament\Resources\Sources\SourceResource;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSource extends EditRecord
{
    protected static string $resource = SourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['icon'] = Favicon::fetch($data['url'])?->getFaviconUrl();

        return $data;
    }
}
