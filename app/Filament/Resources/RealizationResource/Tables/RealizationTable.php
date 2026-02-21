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
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

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
                TextColumn::make('media_count')
                    ->label('Lampiran')
                    ->badge()
                    ->state(fn($record) => method_exists($record, 'media') ? $record->media()->count() : 0)
                    ->formatStateUsing(fn($state) => $state && $state > 0 ? $state . ' file' : '-'),
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
                Action::make('download_excel')
                    ->label('Download Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Download Record Details')
                    ->iconButton()
                    ->action(function ($record) {
                        try {
                            return response()->streamDownload(function () use ($record) {
                                $writer = new Writer();
                                $writer->openToFile('php://output');

                                $headers = [
                                    'Tanggal',
                                    'Departemen',
                                    'Nama Record',
                                    'Total Anggaran',
                                    'Total Realisasi',
                                    'Sisa Saldo',
                                ];

                                $writer->addRow(Row::fromValues($headers));

                                $totalExpense = (float) $record->total_expense;
                                $totalRealization = (float) $record->total_realization;
                                $balance = $totalExpense - $totalRealization;

                                $row = [
                                    $record->record_date ? $record->record_date->format('d-m-Y') : '-',
                                    $record->department->name ?? '-',
                                    $record->record_name,
                                    number_format($totalExpense, 2, ',', '.'),
                                    number_format($totalRealization, 2, ',', '.'),
                                    number_format($balance, 2, ',', '.'),
                                ];

                                $writer->addRow(Row::fromValues($row));

                                $writer->close();
                            }, 'realisasi_' . $record->id . '_' . ($record->record_date ? $record->record_date->format('Y-m-d') : 'no-date') . '.xls');
                        } catch (\Throwable $e) {
                            Log::error('Gagal mengunduh Excel realisasi', [
                                'exception' => $e,
                                'record_id' => $record->id ?? null,
                            ]);

                            Notification::make()
                                ->title('Gagal mengunduh Excel')
                                ->body('Terjadi kesalahan saat menghasilkan file Excel. Silakan coba lagi.')
                                ->danger()
                                ->persistent()
                                ->send();

                            return null;
                        }
                    }),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->tooltip('Download PDF')
                    ->iconButton()
                    ->action(function ($record) {
                        try {
                            return response()->streamDownload(function () use ($record) {
                                echo Pdf::loadView('pdf.financial_record', ['record' => $record])->output();
                            }, 'realisasi_' . $record->id . '_' . ($record->record_date ? $record->record_date->format('Y-m-d') : 'no-date') . '.pdf');
                        } catch (\Throwable $e) {
                            Log::error('Gagal mengunduh PDF realisasi', [
                                'exception' => $e,
                                'record_id' => $record->id ?? null,
                            ]);

                            Notification::make()
                                ->title('Gagal mengunduh PDF')
                                ->body('Terjadi kesalahan saat menghasilkan file PDF. Silakan coba lagi.')
                                ->danger()
                                ->persistent()
                                ->send();

                            return null;
                        }
                    }),
            ])
            ->bulkActions([
                // No bulk actions for Realization typically, or keep delete?
                // User didn't specify. I'll remove bulk actions to be safe or just keep delete.
                // "Duplikasi dan modifikasi...". I'll keep it simple.
            ]);
    }
}
