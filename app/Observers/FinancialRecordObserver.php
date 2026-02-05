<?php

namespace App\Observers;

use App\Models\FinancialRecord;
use App\Models\FinancialRecordTrack;
use Illuminate\Support\Facades\Auth;

class FinancialRecordObserver
{
    /**
     * Handle the FinancialRecord "saved" event.
     * This covers both created and updated events.
     */
    public function saved(FinancialRecord $financialRecord): void
    {
        // 1. Cek apakah status saat ini adalah 'Final' (1 = Aktif/Final)
        if ($financialRecord->status != 1) {
            return;
        }

        // Load expense items for comprehensive snapshot
        // We reload to ensure we have the freshest state including any just-saved child items
        $financialRecord->unsetRelation('expenseItems');
        $financialRecord->load('expenseItems');
        $newData = $financialRecord->toArray();

        // 2. Get latest track to compare against
        $lastTrack = FinancialRecordTrack::where('financial_record_id', $financialRecord->id)
            ->orderBy('version', 'desc')
            ->first();

        $actionType = $lastTrack ? 'UPDATE_FINAL' : 'INITIAL_FINAL';
        $changesSummary = null;

        // 3. Comparison Logic (Only for updates)
        if ($lastTrack) {
            $oldData = $lastTrack->snapshot_data;
            $changesSummary = $this->detectDiff($oldData, $newData);

            // If no meaningful changes found, skip tracking
            if (empty($changesSummary)) {
                return;
            }
        }

        // 4. Hitung version (increment)
        $nextVersion = ($lastTrack ? $lastTrack->version : 0) + 1;

        // 5. Simpan ke financial_record_tracks
        FinancialRecordTrack::create([
            'financial_record_id' => $financialRecord->id,
            'version' => $nextVersion,
            'snapshot_data' => $newData,
            'changes_summary' => $changesSummary,
            'action_type' => $actionType,
            'created_by' => Auth::id(), // User yang melakukan aksi
        ]);
    }

    /**
     * Detect changes between old and new snapshot data.
     * Tracks changes in Numeric fields, Description, and Source of Fund.
     */
    private function detectDiff(array $oldData, array $newData): array
    {
        $changes = [];

        // 1. Compare Financial Record Fields (Numerics & Core Info)
        // Adjust these fields based on what constitutes a "Meaningful Change"
        $fieldsToCheck = [
            'income_amount',
            'income_percentage',
            'income_fixed',
            'income_bos',
            'income_total',
            'total_expense',
            'total_realization',
            'total_balance',
            'record_name',
            'record_date'
        ];

        foreach ($fieldsToCheck as $field) {
            $oldVal = $oldData[$field] ?? null;
            $newVal = $newData[$field] ?? null;

            // Handle numeric comparison safely
            if ($this->isNumericField($field)) {
                if ((float) $oldVal !== (float) $newVal) {
                    $changes["field_{$field}"] = [
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }
            } else {
                // Strict comparison for text (Case Sensitive as requested)
                if ((string) $oldVal !== (string) $newVal) {
                    $changes["field_{$field}"] = [
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }
            }
        }

        // 2. Compare Expense Items
        // Note: relation name 'expenseItems' becomes 'expense_items' in toArray()
        $oldItems = collect($oldData['expense_items'] ?? [])->keyBy('id');
        $newItems = collect($newData['expense_items'] ?? [])->keyBy('id');

        // Check for Updates and Deletions
        foreach ($oldItems as $id => $oldItem) {
            if (!$newItems->has($id)) {
                $changes["expense_item_{$id}_deleted"] = [
                    'old' => $oldItem['description'] ?? 'Unknown',
                    'new' => 'Deleted',
                ];
                continue;
            }

            $newItem = $newItems->get($id);

            // Check Description (Case Sensitive)
            if (($oldItem['description'] ?? '') !== ($newItem['description'] ?? '')) {
                $changes["expense_item_{$id}_description"] = [
                    'old' => $oldItem['description'],
                    'new' => $newItem['description'],
                ];
            }

            // Check Source Type (Dropdown)
            if (($oldItem['source_type'] ?? '') !== ($newItem['source_type'] ?? '')) {
                $changes["expense_item_{$id}_source_type"] = [
                    'old' => $oldItem['source_type'],
                    'new' => $newItem['source_type'],
                ];
            }

            // Check Amount
            if ((float) ($oldItem['amount'] ?? 0) !== (float) ($newItem['amount'] ?? 0)) {
                $changes["expense_item_{$id}_amount"] = [
                    'old' => $oldItem['amount'],
                    'new' => $newItem['amount'],
                ];
            }
        }

        // Check for Additions
        foreach ($newItems as $id => $newItem) {
            if (!$oldItems->has($id)) {
                $changes["expense_item_{$id}_added"] = [
                    'old' => null,
                    'new' => $newItem['description'] ?? 'New Item',
                ];
            }
        }

        return $changes;
    }

    private function isNumericField(string $field): bool
    {
        return in_array($field, [
            'income_amount',
            'income_percentage',
            'income_fixed',
            'income_bos',
            'income_total',
            'total_expense',
            'total_realization',
            'total_balance'
        ]);
    }
}
