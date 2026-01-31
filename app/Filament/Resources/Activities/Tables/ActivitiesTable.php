<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'authentication' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('subject_type'),
                TextColumn::make('subject')
                    ->label('Subject')
                    ->getStateUsing(function ($record) {
                        if ($record->subject) {
                            return sprintf(
                                '%s #%s',
                                class_basename($record->subject_type),
                                $record->subject_id
                            );
                        }
                        return '-';
                    }),
                TextColumn::make('event'),
                TextColumn::make('causer.name')
                    ->label('Causer')
                    ->default('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginationMode(PaginationMode::Simple);
    }
}
