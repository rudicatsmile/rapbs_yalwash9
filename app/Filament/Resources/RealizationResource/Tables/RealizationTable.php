<?php

namespace App\Filament\Resources\RealizationResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use App\Models\FinancialRecord;
use Filament\Actions\ExportAction;
use App\Filament\Exports\FinancialRecordExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class RealizationTable
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
                TextColumn::make('total_expense')
                    ->label('Total Anggaran')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                TextColumn::make('total_realization')
                    ->label('Total Realisasi')
                    ->sortable()
                    ->default(0)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                TextColumn::make('total_balance')
                    ->label('Sisa Saldo')
                    ->sortable()
                    ->default(0)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->state(function ($record) {
                         return $record->total_expense - $record->total_realization;
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Departemen')
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ])
            ->actions([
                EditAction::make()
                    ->label('Realisasi')
                    ->icon('heroicon-o-calculator')
                    ->iconButton()
                    ->tooltip('Input Realisasi'),
            ])
            ->bulkActions([
                // No bulk actions for Realization typically, or keep delete?
                // User didn't specify. I'll remove bulk actions to be safe or just keep delete.
                // "Duplikasi dan modifikasi...". I'll keep it simple.
            ]);
    }
}
