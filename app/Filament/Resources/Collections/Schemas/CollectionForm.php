<?php

namespace App\Filament\Resources\Collections\Schemas;

use App\Enums\CoverTemplate;
use App\Enums\ScheduledDay;
use App\Enums\ScheduleRepeatType;
use App\Models\Source;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Grid::make()->columns(7)->schema([
                    Tabs::make('Tabs')
                        ->columnSpan(5)
                        ->tabs([
                            Tab::make('General')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->maxLength(255),

                                    Repeater::make('collectionSources')
                                        ->label('Sources')
                                        ->relationship()
                                        ->compact()
                                        ->minItems(1)
                                        ->defaultItems(1)
                                        ->columns(3)
                                        ->reorderable()
                                        ->orderColumn()
                                        ->table([
                                            TableColumn::make('Source'),
                                            TableColumn::make('Article count'),
                                        ])
                                        ->schema([
                                            Select::make('source_id')
                                                ->label('Source')
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->relationship('source', 'name')
                                                ->getOptionLabelFromRecordUsing(fn(Source $record) =>
                                                $record->icon ? "<div class='flex gap-x-2 items-center'><img class='size-4' src='{$record->icon}'> {$record->name}</div>" : $record->name)
                                                ->preload()
                                                ->allowHtml()
                                                ->searchable()
                                                ->required(),

                                            TextInput::make('max_article_count')
                                                ->label('Article count')
                                                ->integer()
                                                ->default(5)
                                                ->minValue(1)
                                                ->maxValue(10)
                                                ->required(),

                                        ])
                                        ->addActionLabel('Add source')
                                        ->columnSpanFull(),

                                    Repeater::make('schedule')
                                        ->compact()
                                        ->minItems(1)
                                        ->defaultItems(1)
                                        ->label('Schedule')
                                        ->columns(3)
                                        ->reorderable(false)
                                        ->table([
                                            TableColumn::make('Repeat'),
                                            TableColumn::make('Days'),
                                            TableColumn::make('Time'),
                                        ])
                                        ->schema([
                                            Select::make('repeat_type')
                                                ->label('Repeat')
                                                ->options(ScheduleRepeatType::class)
                                                ->afterStateUpdated(function ($state, $get, $set): void {
                                                    if ($state === ScheduleRepeatType::Daily) {
                                                        $set('scheduled_days', []);
                                                    }
                                                })
                                                ->default(ScheduleRepeatType::Daily)
                                                ->reactive()
                                                ->required()
                                                ->columnSpan(1),

                                            Select::make('scheduled_days')
                                                ->multiple()
                                                ->placeholder(fn ($get): string => $get('repeat_type') === ScheduleRepeatType::Specific ? 'Pick days' : 'Not relevant')
                                                ->label('Days')
                                                ->requiredIf('repeat_type', ScheduleRepeatType::Specific)
                                                ->options(ScheduledDay::class)
                                                ->columns(7)
                                                ->disabled(fn ($get): bool => $get('repeat_type') !== ScheduleRepeatType::Specific),

                                            TimePicker::make('time')
                                                ->label('Time')
                                                ->seconds(false)
                                                ->required(),

                                        ])
                                        ->addActionLabel('Add schedule')
                                        ->columnSpanFull(),
                                ]),

                            Tab::make('Booklore')
                                ->schema([
                                    TextInput::make('booklore_retention_hours')
                                        ->label('Booklore retention')
                                        ->suffix('hours')
                                        ->default(8)
                                        ->helperText('After how many hours should a Booklore book be pruned?')
                                        ->integer()
                                        ->step(1)
                                        ->minValue(0),
                                ]),
                        ]),

                    Section::make()->columnSpan(2)->components([
                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true),

                        Radio::make('cover_template')
                            ->label('Cover style')
                            ->options(CoverTemplate::class),

                    ]),
                ])->columnSpanFull(),
            ]);
    }
}
