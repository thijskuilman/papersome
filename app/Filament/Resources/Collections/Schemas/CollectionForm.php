<?php

namespace App\Filament\Resources\Collections\Schemas;

use App\Enums\CoverTemplate;
use App\Enums\ScheduledDay;
use App\Enums\ScheduleRepeatType;
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

                                    Select::make('sources')
                                        ->multiple()
                                        ->preload()
                                        ->relationship('sources', 'name'),

                                    Repeater::make('schedule')
                                        ->minItems(1)
                                        ->defaultItems(1)
                                        ->label('Schedule')
                                        ->columns(3)
                                        ->reorderable(false)
                                        ->helperText('Add one or more publish times. Leave days empty to run daily.')
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
                                                ->placeholder(fn ($get): string => $get('repeat_type') === ScheduleRepeatType::Specific ? 'Pick days' : 'Daily')
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
