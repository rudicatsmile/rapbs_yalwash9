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
            ->recordUrl(fn($record) => ($record?->status_realisasi == 1 && auth()->user()?->hasRole('user'))
                ? null
                : \App\Filament\Resources\RealizationResource::getUrl('edit', ['record' => $record]))
            ->recordClasses(fn($record) => ($record?->status_realisasi == 1 && auth()->user()?->hasRole('user'))
                ? 'pointer-events-none opacity-60 hover:bg-transparent'
                : null)
            ->columns([
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->extraAttributes(fn($record) => ($record?->status_realisasi == 1 && auth()->user()?->hasRole('user')) ? ['title' => 'Access Denied'] : []),
                TextColumn::make('record_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable()
                    ->extraAttributes(fn($record) => ($record?->status_realisasi == 1 && auth()->user()?->hasRole('user')) ? ['title' => 'Access Denied'] : []),
                TextColumn::make('record_name')
                    ->label('Nama History')
                    ->searchable()
                    ->extraAttributes(fn($record) => ($record?->status_realisasi == 1 && auth()->user()?->hasRole('user')) ? ['title' => 'Access Denied'] : []),
                TextColumn::make('total_expense')
                    ->label('Total Anggaran')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                TextColumn::make('total_realization')
                    ->label('Total Realisasi')
                    ->sortable()
                    ->default(0)
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                TextColumn::make('total_balance')
                    ->label('Sisa Saldo')
                    ->sortable()
                    ->default(0)
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
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
                Action::make('history')
                    ->label('History')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->modalContent(fn($record) => view('filament.tables.actions.realization-history-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->iconButton()
                    ->tooltip('View History'),

                Action::make('edit')
                    ->label('Realisasi')
                    ->icon('heroicon-o-calculator')
                    ->url(fn($record) => \App\Filament\Resources\RealizationResource::getUrl('edit', ['record' => $record]))
                    ->iconButton()
                    ->color(fn($record) => $record?->status_realisasi == 1 && !auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor']) ? 'gray' : 'primary')
                    ->disabled(fn($record) => (!$record) || ($record->status_realisasi == 1 && !auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])))
                    ->tooltip(fn($record) => $record?->status_realisasi == 1 && !auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor']) ? 'Data dikunci (Final)' : 'Input Realisasi'),
            ])
            ->bulkActions([
                // No bulk actions for Realization typically, or keep delete?
                // User didn't specify. I'll remove bulk actions to be safe or just keep delete.
                // "Duplikasi dan modifikasi...". I'll keep it simple.
            ]);
    }
}
