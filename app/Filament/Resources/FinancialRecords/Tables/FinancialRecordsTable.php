<?php

namespace App\Filament\Resources\FinancialRecords\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinancialRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('record_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('record_name')
                    ->label('Nama History')
                    ->searchable(),
                TextColumn::make('income_fixed')
                    ->label('Total Pemasukan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_expense')
                    ->label('Total Pengeluaran')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->state(function ($record) {
                        return $record->income_fixed - $record->total_expense;
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('status')
                    ->label(fn($record) => $record->status ? 'Active' : 'Inactive')
                    ->icon(fn($record) => $record->status ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->color(fn($record) => $record->status ? 'success' : 'danger')
                    ->action(fn($record) => $record->update(['status' => !$record->status]))
                    ->disabled(fn() => auth()->user() && auth()->user()->hasRole('user')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
