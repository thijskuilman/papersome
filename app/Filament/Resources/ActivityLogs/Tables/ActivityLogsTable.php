<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Enums\ActivityLogType;
use App\Models\ActivityLog;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->fontFamily(FontFamily::Mono)
                    ->time()
                    ->label('Timestamp')
                    ->color('gray'),

                TextColumn::make('message')
                    ->fontFamily(FontFamily::Mono)
                    ->color(fn(ActivityLog $record) => match ($record->type) {
                        ActivityLogType::Error => 'danger',
                        ActivityLogType::Warning => 'warning',
                        ActivityLogType::Success => 'success',
                        default => 'black',
                    })
                    ->grow(),

                TextColumn::make('channel')
                    ->badge()
                    ->fontFamily(FontFamily::Mono),
            ])
            ->defaultGroup('created_at')
            ->groupingSettingsHidden()
            ->groups([
                Group::make('created_at')
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('created_at', 'desc'))
                    ->date(),
            ])
            ->paginationPageOptions([50, 100])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
