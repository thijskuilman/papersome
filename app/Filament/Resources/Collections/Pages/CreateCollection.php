<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Enums\CoverTemplate;
use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\Collections\CollectionResourceService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

    #[\Override]
    protected function getFormActions(): array
    {
        return [];
    }

    #[\Override]
    public function form(Schema $schema): Schema
    {
        $collectionResourceService = app(CollectionResourceService::class);

        return $schema
            ->components([
                Wizard::make()
                    ->submitAction(new HtmlString(Blade::render('<x-filament::button type="submit">
                            Create
                        </x-filament::button>')))
                    ->columnSpanFull()
                    ->steps([
                        Wizard\Step::make('Basic')->schema([
                            TextInput::make('name')
                                ->label('Collection name')
                                ->placeholder("e.g. 'Daily News' or 'Movie Spotlights'")
                                ->required()
                                ->maxLength(255),

                            Radio::make('cover_template')
                                ->label('Cover style')
                                ->required()
                                ->options(CoverTemplate::class),
                        ]),

                        Wizard\Step::make('Sources')->schema([

                            TextEntry::make('sources_helper')
                                ->hiddenLabel()
                                ->size(TextSize::Large)
                                ->weight(FontWeight::Bold)
                                ->state('Determine the content of the publications'),

                            TextEntry::make('sources_helper')
                                ->hiddenLabel()
                                ->state('Choose sources for this collection, reorder them, and limit how many articles appear per source.'),

                            $collectionResourceService->getSourcesField()->hiddenLabel(),
                        ]),

                        Wizard\Step::make('Scheduling')->schema([

                            TextEntry::make('sources_helper')
                                ->hiddenLabel()
                                ->size(TextSize::Large)
                                ->weight(FontWeight::Bold)
                                ->state('Set up a schedule'),

                            TextEntry::make('sources_helper')
                                ->hiddenLabel()
                                ->state('Determine how often a new publication should be generated.'),

                            $collectionResourceService->getScheduleField()->hiddenLabel(),
                        ]),
                    ]),
            ]);
    }

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
