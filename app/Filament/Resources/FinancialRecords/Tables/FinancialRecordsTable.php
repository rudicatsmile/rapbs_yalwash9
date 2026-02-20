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
use Filament\Forms\Components\FileUpload;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use App\Models\Department;
use App\Models\ExpenseItem;
use Carbon\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

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
                Action::make('download_import_template')
                    ->label('Download Template Import')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('secondary')
                    ->tooltip('Download template Excel untuk import RAPBS')
                    ->action(function () {
                        return response()->streamDownload(function () {
                            $writer = new Writer();
                            $writer->openToFile('php://output');

                            $headers = [
                                'department_id',
                                'record_date',
                                'record_name',
                                'income_amount',
                                'income_percentage',
                                'income_bos',
                                'income_bos_other',
                                'total_expense',
                            ];

                            $writer->addRow(Row::fromValues($headers));

                            $exampleRows = [
                                [
                                    1,
                                    now()->format('Y-m-d'),
                                    'RAPBS Tahun ' . now()->year,
                                    100000000,
                                    5000000,
                                    25000000,
                                    10000000,
                                    0,
                                ],
                                [
                                    2,
                                    now()->format('Y-m-d'),
                                    'RAPBS Ekstrakurikuler',
                                    50000000,
                                    0,
                                    10000000,
                                    5000000,
                                    0,
                                ],
                            ];

                            foreach ($exampleRows as $row) {
                                $writer->addRow(Row::fromValues($row));
                            }

                            $writer->addNewSheetAndMakeItCurrent();

                            $expenseHeaders = [
                                'department_id',
                                'record_date',
                                'record_name',
                                'description',
                                'amount',
                                'source_type',
                            ];

                            $writer->addRow(Row::fromValues($expenseHeaders));

                            $expenseExampleRows = [
                                [
                                    1,
                                    now()->format('Y-m-d'),
                                    'RAPBS Tahun ' . now()->year,
                                    'Pengadaan Buku',
                                    10000000,
                                    'Mandiri',
                                ],
                                [
                                    1,
                                    now()->format('Y-m-d'),
                                    'RAPBS Tahun ' . now()->year,
                                    'Renovasi Kelas',
                                    15000000,
                                    'BOS',
                                ],
                            ];

                            foreach ($expenseExampleRows as $row) {
                                $writer->addRow(Row::fromValues($row));
                            }

                            $writer->close();
                        }, 'template_import_rapbs_' . now()->format('Y-m-d') . '.xlsx');
                    })
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
                Action::make('import_excel')
                    ->label('Import Excel')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Import RAPBS dari Excel')
                    ->modalButton('Proses Import')
                    ->modalWidth('xl')
                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel (.xlsx / .xls)')
                            ->directory('imports/financial-records')
                            ->disk('local')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(10240)
                            ->helperText('Unggah file Excel dengan header kolom yang sesuai. Setiap baris merepresentasikan satu RAPBS Sekolah.')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $path = $data['file'] ?? null;

                        if (!$path) {
                            Notification::make()
                                ->title('File tidak ditemukan')
                                ->body('Silakan pilih file Excel terlebih dahulu.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $fullPath = storage_path('app/' . $path);

                        if (!file_exists($fullPath)) {
                            Notification::make()
                                ->title('File tidak ditemukan di server')
                                ->body('File pada server tidak ditemukan. Silakan unggah ulang.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $totalRows = 0;
                        $successRows = 0;
                        $totalExpenseRows = 0;
                        $successExpenseRows = 0;
                        $rowErrors = [];
                        $preview = [];

                        DB::beginTransaction();

                        try {
                            $reader = ReaderEntityFactory::createReaderFromFile($fullPath);
                            $reader->open($fullPath);

                            $recordsByKey = [];

                            foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
                                $header = [];

                                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                                    $cells = [];
                                    foreach ($row->getCells() as $cell) {
                                        $cells[] = $cell->getValue();
                                    }

                                    if ($rowIndex === 1) {
                                        $header = array_map(function ($value) {
                                            return is_string($value) ? trim($value) : $value;
                                        }, $cells);
                                        continue;
                                    }

                                    if (empty(array_filter($cells, fn($v) => $v !== null && $v !== ''))) {
                                        continue;
                                    }

                                    if ($sheetIndex === 0) {
                                        $totalRows++;

                                        $rowAssoc = [];
                                        foreach ($header as $idx => $name) {
                                            if ($name === null || $name === '') {
                                                continue;
                                            }
                                            $rowAssoc[$name] = $cells[$idx] ?? null;
                                        }

                                        $line = $rowIndex;

                                        $requiredColumns = [
                                            'department_id',
                                            'record_date',
                                            'record_name',
                                            'income_amount',
                                            'income_percentage',
                                            'income_bos',
                                            'income_bos_other',
                                        ];

                                        $rowErrorMessages = [];

                                        foreach ($requiredColumns as $col) {
                                            if (!array_key_exists($col, $rowAssoc)) {
                                                $rowErrorMessages[] = "Baris {$line}: kolom '{$col}' tidak ditemukan di header.";
                                            }
                                        }

                                        foreach ($requiredColumns as $col) {
                                            if (($rowAssoc[$col] ?? null) === null || $rowAssoc[$col] === '') {
                                                $rowErrorMessages[] = "Baris {$line}: nilai pada kolom '{$col}' wajib diisi.";
                                            }
                                        }

                                        $departmentId = (int) ($rowAssoc['department_id'] ?? 0);
                                        if (!Department::whereKey($departmentId)->exists()) {
                                            $rowErrorMessages[] = "Baris {$line}: Departemen dengan ID {$departmentId} tidak ditemukan.";
                                        }

                                        try {
                                            $recordDate = Carbon::parse($rowAssoc['record_date'] ?? null);
                                        } catch (\Throwable $e) {
                                            $rowErrorMessages[] = "Baris {$line}: format tanggal tidak valid.";
                                        }

                                        if (!empty($rowErrorMessages)) {
                                            $rowErrors = array_merge($rowErrors, $rowErrorMessages);
                                            continue;
                                        }

                                        $incomeAmount = (float) $rowAssoc['income_amount'];
                                        $riskAmount = (float) $rowAssoc['income_percentage'];
                                        $incomeBos = (float) $rowAssoc['income_bos'];
                                        $incomeBosOther = (float) $rowAssoc['income_bos_other'];
                                        $totalExpense = isset($rowAssoc['total_expense']) ? (float) $rowAssoc['total_expense'] : 0;

                                        if ($incomeAmount < 0 || $riskAmount < 0 || $incomeBos < 0 || $incomeBosOther < 0 || $totalExpense < 0) {
                                            $rowErrors[] = "Baris {$line}: nilai keuangan tidak boleh negatif.";
                                            continue;
                                        }

                                        if ($riskAmount > $incomeAmount) {
                                            $rowErrors[] = "Baris {$line}: Resiko tidak dibayar melebihi Pemasukan (Rp).";
                                            continue;
                                        }

                                        $incomeFixed = $incomeAmount - $riskAmount;
                                        $incomeTotal = $incomeFixed + $incomeBos + $incomeBosOther;

                                        $record = new FinancialRecord();
                                        $record->user_id = auth()->id();
                                        $record->department_id = $departmentId;
                                        $record->record_date = $recordDate;
                                        $record->month = $recordDate->month;
                                        $record->record_name = (string) ($rowAssoc['record_name'] ?? '');
                                        $record->income_amount = $incomeAmount;
                                        $record->income_percentage = $riskAmount;
                                        $record->income_fixed = $incomeFixed;
                                        $record->income_bos = $incomeBos;
                                        $record->income_bos_other = $incomeBosOther;
                                        $record->income_total = $incomeTotal;
                                        $record->total_expense = $totalExpense;
                                        $record->status = true;
                                        $record->status_realisasi = false;
                                        $record->save();

                                        $successRows++;

                                        $key = $departmentId . '|' . $recordDate->format('Y-m-d') . '|' . $record->record_name;
                                        $recordsByKey[$key] = $record;

                                        if (count($preview) < 5) {
                                            $preview[] = [
                                                'record_name' => $record->record_name,
                                                'department_id' => $record->department_id,
                                                'record_date' => $record->record_date ? $record->record_date->format('Y-m-d') : null,
                                                'income_total' => $record->income_total,
                                            ];
                                        }
                                    } elseif ($sheetIndex === 1) {
                                        $totalExpenseRows++;

                                        $rowAssoc = [];
                                        foreach ($header as $idx => $name) {
                                            if ($name === null || $name === '') {
                                                continue;
                                            }
                                            $rowAssoc[$name] = $cells[$idx] ?? null;
                                        }

                                        $line = $rowIndex;

                                        $requiredColumns = [
                                            'department_id',
                                            'record_date',
                                            'record_name',
                                            'description',
                                            'amount',
                                            'source_type',
                                        ];

                                        $rowErrorMessages = [];

                                        foreach ($requiredColumns as $col) {
                                            if (!array_key_exists($col, $rowAssoc)) {
                                                $rowErrorMessages[] = "Baris {$line} (sheet pengeluaran): kolom '{$col}' tidak ditemukan di header.";
                                            }
                                        }

                                        foreach ($requiredColumns as $col) {
                                            if (($rowAssoc[$col] ?? null) === null || $rowAssoc[$col] === '') {
                                                $rowErrorMessages[] = "Baris {$line} (sheet pengeluaran): nilai pada kolom '{$col}' wajib diisi.";
                                            }
                                        }

                                        $departmentId = (int) ($rowAssoc['department_id'] ?? 0);

                                        try {
                                            $recordDate = Carbon::parse($rowAssoc['record_date'] ?? null);
                                        } catch (\Throwable $e) {
                                            $rowErrorMessages[] = "Baris {$line} (sheet pengeluaran): format tanggal tidak valid.";
                                            $recordDate = null;
                                        }

                                        $recordName = (string) ($rowAssoc['record_name'] ?? '');

                                        if ($recordDate instanceof Carbon) {
                                            $key = $departmentId . '|' . $recordDate->format('Y-m-d') . '|' . $recordName;
                                        } else {
                                            $key = null;
                                        }

                                        if ($key === null || !array_key_exists($key, $recordsByKey)) {
                                            $rowErrorMessages[] = "Baris {$line} (sheet pengeluaran): RAPBS dengan kombinasi departemen, tanggal, dan nama tidak ditemukan pada sheet utama.";
                                        }

                                        if (!empty($rowErrorMessages)) {
                                            $rowErrors = array_merge($rowErrors, $rowErrorMessages);
                                            continue;
                                        }

                                        $amount = (float) $rowAssoc['amount'];
                                        if ($amount < 0) {
                                            $rowErrors[] = "Baris {$line} (sheet pengeluaran): jumlah pengeluaran tidak boleh negatif.";
                                            continue;
                                        }

                                        $sourceType = trim((string) $rowAssoc['source_type']);
                                        $allowedSourceTypes = ['Mandiri', 'BOS'];
                                        if (!in_array($sourceType, $allowedSourceTypes, true)) {
                                            $rowErrors[] = "Baris {$line} (sheet pengeluaran): source_type harus salah satu dari: Mandiri, BOS.";
                                            continue;
                                        }

                                        $record = $recordsByKey[$key];

                                        $expenseItem = new ExpenseItem();
                                        $expenseItem->financial_record_id = $record->id;
                                        $expenseItem->description = (string) $rowAssoc['description'];
                                        $expenseItem->amount = $amount;
                                        $expenseItem->source_type = $sourceType;
                                        $expenseItem->realisasi = 0;
                                        $expenseItem->saldo = 0;
                                        $expenseItem->save();

                                        $successExpenseRows++;
                                    }
                                }
                            }

                            $reader->close();

                            if (!empty($rowErrors)) {
                                DB::rollBack();

                                $message = "Import dibatalkan. Ditemukan kesalahan pada file Excel:\n";
                                foreach (array_slice($rowErrors, 0, 10) as $error) {
                                    $message .= "- {$error}\n";
                                }
                                if (count($rowErrors) > 10) {
                                    $message .= '... dan ' . (count($rowErrors) - 10) . ' error lainnya.';
                                }

                                Notification::make()
                                    ->title('Import Excel gagal')
                                    ->body($message)
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            DB::commit();

                            $body = "Berhasil mengimpor {$successRows} dari {$totalRows} baris RAPBS.";

                            if ($totalExpenseRows > 0) {
                                $body .= "\nBerhasil mengimpor {$successExpenseRows} dari {$totalExpenseRows} baris pengeluaran.";
                            }

                            if (!empty($preview)) {
                                $body .= "\nContoh data RAPBS yang diimpor:\n";
                                foreach ($preview as $row) {
                                    $body .= '- ' . $row['record_name'] . ' (' . $row['record_date'] . ') : Rp ' . number_format($row['income_total'], 0, ',', '.') . "\n";
                                }
                            }

                            Notification::make()
                                ->title('Import Excel berhasil')
                                ->body($body)
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            if (DB::transactionLevel() > 0) {
                                DB::rollBack();
                            }

                            Log::error('Gagal mengimpor RAPBS dari Excel', [
                                'exception' => $e,
                            ]);

                            Notification::make()
                                ->title('Terjadi kesalahan saat import')
                                ->body('Import dibatalkan. Silakan periksa format file dan coba lagi.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    })
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ]);
    }
}
