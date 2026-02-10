<?php

namespace App\Filament\Resources\Sources\Pages;

use App\Filament\Resources\Sources\SourceResource;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSource extends EditRecord
{
    protected static string $resource = SourceResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),

            $this->getSaveFormAction()
                ->submit(null)
                ->action(fn () => $this->save()),

        ];
    }

    #[\Override]
    protected function getFormActions(): array
    {
        return [];
    }

    #[\Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $url = $data['url'];
        $scheme = parse_url((string) $url, PHP_URL_SCHEME);
        $host = parse_url((string) $url, PHP_URL_HOST);
        $base = $scheme && $host ? "$scheme://$host" : null;
        $data['icon'] = Favicon::fetch($base ?? $data['url'])?->getFaviconUrl();

        return $data;
    }
}
