<?php

namespace App\Filament\Resources\ImpersonationLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImpersonationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('impersonator.name')
                    ->label('Impersonator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('impersonated.name')
                    ->label('Impersonated User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Still active'),
                TextColumn::make('ip_address')
                    ->label('IP Address'),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                // View-only resource, no actions
            ])
            ->toolbarActions([
                // View-only resource, no bulk actions
            ]);
    }
}
