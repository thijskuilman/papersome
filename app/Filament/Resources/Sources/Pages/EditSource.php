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
        $url = $data['url'];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host   = parse_url($url, PHP_URL_HOST);
        $base = $scheme && $host ? "$scheme://$host" : null;
        $data['icon'] = Favicon::fetch($base ?? $data['url'])?->getFaviconUrl();
        return $data;
    }
}
