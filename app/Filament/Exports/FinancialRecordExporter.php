<?php

namespace App\Filament\Exports;

use App\Models\FinancialRecord;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class FinancialRecordExporter extends Exporter
{
    protected static ?string $model = FinancialRecord::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('record_date')
                ->label('Tanggal'),
            ExportColumn::make('record_name')
                ->label('Deskripsi'),
            ExportColumn::make('department.name')
                ->label('Departemen'),
            ExportColumn::make('income_amount')
                ->label('Jumlah Pemasukan'),
            ExportColumn::make('income_fixed')
                ->label('Total Pemasukan (Fixed)'),
            ExportColumn::make('total_expense')
                ->label('Total Pengeluaran'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export data financial records telah selesai. ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
