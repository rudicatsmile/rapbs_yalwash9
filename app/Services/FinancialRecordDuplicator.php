<?php

namespace App\Services;

use App\Models\FinancialRecord;
use App\Models\RealizationExpenseLine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FinancialRecordDuplicator
{
    public function duplicate(FinancialRecord $record): FinancialRecord
    {
        $record->loadMissing(['expenseItems', 'realizationExpenseLines']);

        $incomeTotalStored = (float) ($record->income_total ?? 0);
        $incomeTotalComputed = (float) (($record->income_fixed ?? 0) + ($record->income_bos ?? 0) + ($record->income_bos_other ?? 0));
        $incomeTotal = ($incomeTotalStored === 0.0 && $incomeTotalComputed > 0.0) ? $incomeTotalComputed : $incomeTotalStored;
        $totalExpense = (float) ($record->total_expense ?? 0);

        if ($incomeTotal < $totalExpense) {
            throw new \DomainException('Anggaran tidak mencukupi untuk diduplikasi (total pemasukan lebih kecil dari total pengeluaran).');
        }

        try {
            return DB::transaction(fn () => $this->duplicateWithinTransaction($record));
        } catch (QueryException $e) {
            throw $e;
        }
    }

    public function duplicateWithinTransaction(FinancialRecord $record): FinancialRecord
    {
        $newRecord = $record->replicate(['mandiri_expense', 'bos_expense']);
        $newRecord->status = false;
        $newRecord->status_realisasi = false;
        $newRecord->is_approved_by_bendahara = false;
        $newRecord->total_realization = (float) ($record->total_realization ?? 0);
        $newRecord->total_balance = (float) ($newRecord->total_expense ?? 0) - (float) ($newRecord->total_realization ?? 0);
        $newRecord->save();

        $expenseItemIdMap = [];

        foreach ($record->expenseItems as $item) {
            $newItem = $item->replicate();
            $newItem->financial_record_id = $newRecord->id;
            $newItem->allocated_amount = $item->allocated_amount ?? $item->amount ?? 0;
            $newItem->is_selected_for_realization = (bool) ($item->is_selected_for_realization ?? false);
            $newItem->save();

            $expenseItemIdMap[$item->id] = $newItem->id;
        }

        foreach ($record->realizationExpenseLines as $line) {
            $newExpenseItemId = $expenseItemIdMap[$line->expense_item_id] ?? null;

            if (! $newExpenseItemId) {
                throw new \RuntimeException('Duplikasi dibatalkan karena data referensi realisasi tidak konsisten.');
            }

            RealizationExpenseLine::create([
                'financial_record_id' => $newRecord->id,
                'expense_item_id' => $newExpenseItemId,
                'description' => (string) $line->description,
                'allocated_amount' => (float) ($line->allocated_amount ?? 0),
                'realisasi' => (float) ($line->realisasi ?? 0),
            ]);
        }

        return $newRecord;
    }
}
