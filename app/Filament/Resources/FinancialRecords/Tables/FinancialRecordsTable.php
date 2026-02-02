<?php

namespace App\Filament\Resources\FinancialRecords\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use App\Models\FinancialRecord;
use Filament\Actions\ExportAction;
use App\Filament\Exports\FinancialRecordExporter;

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
                    ->preload()
                    ->placeholder('Semua Departemen')
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ])
            ->recordActions([
                // Grouping actions horizontally using simple array structure (rendered inline by default).
                // Converted to Icon Buttons to save space and ensure responsiveness.
                // Tooltips added for better UX.
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Record'),

                ReplicateAction::make()
                    ->label('Duplicate')
                    ->modalHeading('Duplicate Record')
                    ->modalDescription('Are you sure you want to duplicate this record? This will create a new entry with the same values.')
                    ->modalSubmitActionLabel('Yes, Duplicate')
                    ->action(function (FinancialRecord $record) {
                        DB::transaction(function () use ($record) {
                            $replica = $record->replicate();
                            $replica->status = true;
                            $replica->save();

                            foreach ($record->expenseItems as $item) {
                                $newItem = $item->replicate();
                                $newItem->financial_record_id = $replica->id;
                                $newItem->save();
                            }

                            Log::info("Replicated FinancialRecord {$record->id} to {$replica->id} with items.");

                            Notification::make()
                                ->title('Record Duplicated')
                                ->success()
                                ->send();
                        });
                    })
                    ->iconButton() // Render as icon button
                    ->tooltip('Duplicate Record'), // Add tooltip

                Action::make('status')
                    ->label(fn($record) => $record->status ? 'Active' : 'Inactive')
                    ->icon(fn($record) => $record->status ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->color(fn($record) => $record->status ? 'success' : 'danger')
                    ->action(fn($record) => $record->update(['status' => !$record->status]))
                    ->disabled(fn() => auth()->user() && auth()->user()->hasRole('user'))
                    ->iconButton() // Render as icon button
                    ->tooltip(fn($record) => $record->status ? 'Deactivate Record' : 'Activate Record'), // Dynamic tooltip

                Action::make('download_excel')
                    ->label('Download Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Download Record Details')
                    ->action(function (FinancialRecord $record) {
                        return response()->streamDownload(function () use ($record) {
                            $writer = new \OpenSpout\Writer\XLSX\Writer();
                            $writer->openToFile('php://output');

                            // 1. Structure changes
                            $headers = ['Tanggal Transaksi', 'Departemen', 'Deskripsi', 'Jumlah Nominal', 'Tipe', 'Saldo Akhir'];
                            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers));

                            $type = $record->income_fixed > 0 ? 'Pemasukan' : 'Pengeluaran';
                            $nominal = $record->income_fixed > 0 ? $record->income_fixed : $record->total_expense;
                            $balance = $record->income_fixed - $record->total_expense;

                            $row = [
                                $record->record_date ? $record->record_date->format('d-m-Y') : '-',
                                $record->department->name ?? '-',
                                $record->record_name,
                                number_format($nominal, 2, ',', '.'),
                                $type,
                                number_format($balance, 2, ',', '.')
                            ];
                            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($row));

                            // Spacer row
                            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
                            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

                            // 2. Expense Items Integration
                            $expenseItems = $record->expenseItems;
                            if ($expenseItems->isNotEmpty()) {
                                // Section Header
                                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Rincian Pengeluaran']));

                                // Table Headers
                                $itemHeaders = ['No', 'Deskripsi Item', 'Jumlah'];
                                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($itemHeaders));

                                $totalAmount = 0;
                                foreach ($expenseItems as $index => $item) {
                                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                                        $index + 1,
                                        $item->description,
                                        number_format($item->amount, 2, ',', '.')
                                    ]));
                                    $totalAmount += $item->amount;
                                }

                                // 3. Total Amount Row
                                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                                    '',
                                    'Total',
                                    number_format($totalAmount, 2, ',', '.')
                                ]));
                            }

                            $writer->close();
                        }, "RAPBS_" . ($record->department->name ?? 'Umum') . "_" . now()->format('Y-m-d') . ".xlsx");
                    })
                    ->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('duplicate')
                        ->label('Duplicate Selected')
                        ->icon('heroicon-m-document-duplicate')
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Selected Records')
                        ->modalDescription('Are you sure you want to duplicate the selected records?')
                        ->modalSubmitActionLabel('Yes, Duplicate')
                        ->action(function (Collection $records) {
                            // Backend authorization check
                            if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])) {
                                Notification::make()
                                    ->title('Access Denied')
                                    ->body('You do not have permission to perform this action.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    $newRecord = $record->replicate();
                                    $newRecord->status = true;
                                    $newRecord->save();

                                    foreach ($record->expenseItems as $item) {
                                        $newItem = $item->replicate();
                                        $newItem->financial_record_id = $newRecord->id;
                                        $newItem->save();
                                    }

                                    Log::info("Bulk Replicated FinancialRecord {$record->id} to {$newRecord->id} with items.");
                                }
                            });

                            Notification::make()
                                ->title('Records Duplicated')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ])
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(FinancialRecordExporter::class)
                    ->label('Export Excel')
                    ->tooltip('Export to Excel')
                    ->icon('heroicon-m-arrow-down-tray'),
            ]);
    }
}
