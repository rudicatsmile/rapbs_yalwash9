<?php

namespace App\Filament\Exports;

use App\Models\Realization;

class RealizationExcelRowsBuilder
{
    public static function build(Realization $record): array
    {
        $record->loadMissing(['department', 'expenseItems', 'realizationExpenseLines']);

        $rows = [];

        $rows[] = ['Nama Record', (string) $record->record_name];
        $rows[] = ['Tanggal', $record->record_date ? $record->record_date->format('d-m-Y') : '-'];
        $rows[] = ['Departemen', (string) ($record->department->name ?? '-')];
        $rows[] = [];

        $incomeFixed = (float) ($record->income_fixed ?? 0);
        $incomeBos = (float) ($record->income_bos ?? 0);
        $incomeBosOther = (float) ($record->income_bos_other ?? 0);
        $incomeTotal = (float) ($record->income_total ?? ($incomeFixed + $incomeBos + $incomeBosOther));

        $rows[] = ['Pemasukan'];
        $rows[] = ['Sumber', 'Jumlah'];
        $rows[] = ['Fixed Income', self::rupiah($incomeFixed)];
        $rows[] = ['BOS', self::rupiah($incomeBos)];
        $rows[] = ['Lainnya', self::rupiah($incomeBosOther)];
        $rows[] = ['Total Pemasukan', self::rupiah($incomeTotal)];
        $rows[] = [];

        $lines = $record->realizationExpenseLines;
        $linesByExpense = $lines->groupBy('expense_item_id');

        $totalRealization = $lines->isNotEmpty()
            ? (float) $lines->sum('realisasi')
            : (float) ($record->total_realization ?? 0);

        $totalExpense = (float) ($record->total_expense ?? 0);
        $totalBalance = $totalExpense - $totalRealization;

        $rows[] = ['Pengeluaran'];
        $rows[] = ['No', 'Deskripsi', 'Anggaran', 'Realisasi', 'Sisa'];

        foreach ($record->expenseItems as $index => $item) {
            $itemLines = $linesByExpense->get($item->id) ?? collect();
            $aggRealisasi = $itemLines->isNotEmpty()
                ? (float) $itemLines->sum('realisasi')
                : (float) ($item->realisasi ?? 0);

            $amount = (float) ($item->amount ?? 0);
            $saldo = $amount - $aggRealisasi;

            $rows[] = [
                (string) ($index + 1),
                (string) $item->description,
                self::rupiah($amount),
                self::rupiah($aggRealisasi),
                self::rupiah($saldo),
            ];

            foreach ($itemLines as $line) {
                $rows[] = [
                    '',
                    (string) $line->description,
                    '',
                    self::rupiah((float) ($line->realisasi ?? 0)),
                    '',
                ];
            }
        }

        $rows[] = [
            '',
            'Total Pengeluaran',
            self::rupiah($totalExpense),
            self::rupiah($totalRealization),
            self::rupiah($totalBalance),
        ];

        $rows[] = [];

        $rows[] = [
            '',
            'SALDO AKHIR (BALANCE)',
            '',
            '',
            self::rupiah($incomeTotal - $totalRealization),
        ];

        return $rows;
    }

    private static function rupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
