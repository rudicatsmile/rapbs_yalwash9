<?php

namespace App\Filament\Resources\FinancialRecords\Tables;

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
                TextColumn::make('income_details')
                    ->label('Rincian Pemasukan')
                    ->html()
                    ->state(function (FinancialRecord $record) {
                        $formatMoney = fn($amount) => 'Rp ' . number_format($amount, 0, ',', '.');

                        $html = '<div class="flex flex-col space-y-1">';

                        // Total Income
                        $html .= '<div class="font-bold text-success-600">';
                        $html .= $formatMoney($record->income_total);
                        $html .= '</div>';

                        // Details (Fixed & BOS)
                        $html .= '<div class="flex flex-col gap-1 text-[10px] opacity-80">';

                        if ($record->income_fixed > 0) {
                            $html .= '<div class="flex items-center gap-1">';
                            $html .= '<span class="px-1.5 py-0.5 rounded bg-info-50 text-info-700 border border-info-200">Mandiri : </span>';
                            $html .= '<span>' . $formatMoney($record->income_fixed) . '</span>';
                            $html .= '</div>';
                        }

                        if ($record->income_bos > 0) {
                            $html .= '<div class="flex items-center gap-1">';
                            $html .= '<span class="px-1.5 py-0.5 rounded bg-success-50 text-success-700 border border-success-200">BOS : </span>';
                            $html .= '<span>' . $formatMoney($record->income_bos) . '</span>';
                            $html .= '</div>';
                        }

                        $html .= '</div>'; // End Details
                        $html .= '</div>'; // End Container
            
                        return $html;
                    })
                    ->sortable(['income_total'])
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                return $query->selectRaw('sum(income_total) as total, sum(income_fixed) as fixed, sum(income_bos) as bos')->first();
                            })
                            ->formatStateUsing(function ($state) {
                                $formatMoney = fn($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
                                $html = '<div class="flex flex-col space-y-1">';
                                $html .= '<div class="font-bold text-success-600">' . $formatMoney($state->total) . '</div>';

                                if ($state->fixed > 0 || $state->bos > 0) {
                                    $html .= '<div class="flex flex-col gap-1 text-[10px] opacity-80">';
                                    if ($state->fixed > 0) {
                                        $html .= '<div class="flex items-center gap-1"><span class="px-1.5 py-0.5 rounded bg-info-50 text-info-700 border border-info-200">Mandiri : </span><span>' . $formatMoney($state->fixed) . '</span></div>';
                                    }
                                    if ($state->bos > 0) {
                                        $html .= '<div class="flex items-center gap-1"><span class="px-1.5 py-0.5 rounded bg-success-50 text-success-700 border border-success-200">BOS : </span><span>' . $formatMoney($state->bos) . '</span></div>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                return $html;
                            })
                    ),
                TextColumn::make('expense_details')
                    ->label('Rincian Pengeluaran')
                    ->html()
                    ->state(function (FinancialRecord $record) {
                        $formatMoney = fn($amount) => 'Rp ' . number_format($amount, 0, ',', '.');

                        $html = '<div class="flex flex-col space-y-1">';

                        // Total Expense
                        $html .= '<div class="font-bold text-danger-600">';
                        $html .= $formatMoney($record->total_expense);
                        $html .= '</div>';

                        // Details (Mandiri & BOS)
                        $html .= '<div class="flex flex-col gap-1 text-[10px] opacity-80">';

                        if ($record->mandiri_expense > 0) {
                            $html .= '<div class="flex items-center gap-1">';
                            $html .= '<span class="px-1.5 py-0.5 rounded bg-info-50 text-info-700 border border-info-200">Mandiri : </span>';
                            $html .= '<span>' . $formatMoney($record->mandiri_expense) . '</span>';
                            $html .= '</div>';
                        }

                        if ($record->bos_expense > 0) {
                            $html .= '<div class="flex items-center gap-1">';
                            $html .= '<span class="px-1.5 py-0.5 rounded bg-success-50 text-success-700 border border-success-200">BOS : </span>';
                            $html .= '<span>' . $formatMoney($record->bos_expense) . '</span>';
                            $html .= '</div>';
                        }

                        $html .= '</div>'; // End Details
                        $html .= '</div>'; // End Container
            
                        return $html;
                    })
                    ->sortable(['total_expense'])
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                $total = $query->sum('total_expense');
                                $mandiri = DB::table('expense_items')
                                    ->where('source_type', 'Mandiri')
                                    ->whereIn('financial_record_id', $query->clone()->reorder()->select('id'))
                                    ->sum('amount');
                                $bos = DB::table('expense_items')
                                    ->where('source_type', 'BOS')
                                    ->whereIn('financial_record_id', $query->clone()->reorder()->select('id'))
                                    ->sum('amount');
                                return (object) ['total' => $total, 'mandiri' => $mandiri, 'bos' => $bos];
                            })
                            ->formatStateUsing(function ($state) {
                                $formatMoney = fn($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
                                $html = '<div class="flex flex-col space-y-1">';
                                $html .= '<div class="font-bold text-danger-600">' . $formatMoney($state->total) . '</div>';

                                if ($state->mandiri > 0 || $state->bos > 0) {
                                    $html .= '<div class="flex flex-col gap-1 text-[10px] opacity-80">';
                                    if ($state->mandiri > 0) {
                                        $html .= '<div class="flex items-center gap-1"><span class="px-1.5 py-0.5 rounded bg-info-50 text-info-700 border border-info-200">Mandiri : </span><span>' . $formatMoney($state->mandiri) . '</span></div>';
                                    }
                                    if ($state->bos > 0) {
                                        $html .= '<div class="flex items-center gap-1"><span class="px-1.5 py-0.5 rounded bg-success-50 text-success-700 border border-success-200">BOS : </span><span>' . $formatMoney($state->bos) . '</span></div>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                return $html;
                            })
                    ),
                TextColumn::make('balance')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->state(function ($record) {
                        return $record->income_total - $record->total_expense;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn($query) => $query->sum('income_total') - $query->sum('total_expense'))
                            ->money('IDR')
                    ),
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
                Action::make('history')
                    ->label('History')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->modalContent(fn(FinancialRecord $record) => view('filament.tables.actions.history-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->iconButton()
                    ->tooltip('View History'),

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
                        }, "Effin9_" . ($record->department->name ?? 'Umum') . "_" . now()->format('Y-m-d') . ".xlsx");
                    })
                    ->iconButton(),

                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->tooltip('Download PDF')
                    ->action(function (FinancialRecord $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadView('pdf.financial_record', ['record' => $record])->output();
                        }, 'financial_record_' . $record->id . '_' . ($record->record_date ? $record->record_date->format('Y-m-d') : 'no-date') . '.pdf');
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
