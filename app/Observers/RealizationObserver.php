<?php

namespace App\Observers;

use App\Models\Realization;
use App\Models\RealizationTrack;
use Illuminate\Support\Facades\Auth;

class RealizationObserver
{
    /**
     * Handle the Realization "saved" event.
     */
    public function saved(Realization $realization): void
    {
        // 0. Refresh model
        $realization->refresh();

        // 1. Check status (Active/Final only)
        if ($realization->status != 1) {
            return;
        }

        // Load relations for snapshot
        $realization->load('expenseItems');
        $newData = $realization->toArray();

        // 2. Get latest track
        $lastTrack = RealizationTrack::where('financial_record_id', $realization->id)
            ->orderBy('version', 'desc')
            ->first();

        $actionType = $lastTrack ? 'UPDATE_REALIZATION' : 'INITIAL_REALIZATION';
        $changesSummary = null;

        // 3. Comparison Logic
        if ($lastTrack) {
            // Debounce / Merge Logic
            $isRecent = $lastTrack->created_at->diffInSeconds(now()) < 2;
            $isSameUser = $lastTrack->created_by == Auth::id();

            if ($isRecent && $isSameUser && $actionType === 'UPDATE_REALIZATION') {
                $previousTrack = RealizationTrack::where('financial_record_id', $realization->id)
                    ->where('version', '<', $lastTrack->version)
                    ->orderBy('version', 'desc')
                    ->first();

                $baselineData = $previousTrack ? $previousTrack->snapshot_data : null;

                if ($lastTrack->action_type === 'INITIAL_REALIZATION') {
                    $lastTrack->update(['snapshot_data' => $newData]);
                    return;
                }

                if ($baselineData) {
                    $changesSummary = $this->detectDiff($baselineData, $newData);
                    $lastTrack->update([
                        'snapshot_data' => $newData,
                        'changes_summary' => $changesSummary,
                        'updated_at' => now(),
                    ]);
                    return;
                }
            }

            $oldData = $lastTrack->snapshot_data;
            $changesSummary = $this->detectDiff($oldData, $newData);

            // If no changes, skip
            if (empty($changesSummary)) {
                return;
            }
        }

        // 4. Increment Version
        $nextVersion = ($lastTrack ? $lastTrack->version : 0) + 1;

        // 5. Save Track
        RealizationTrack::create([
            'financial_record_id' => $realization->id,
            'version' => $nextVersion,
            'snapshot_data' => $newData,
            'changes_summary' => $changesSummary,
            'action_type' => $actionType,
            'created_by' => Auth::id(),
        ]);
    }

    private function detectDiff(array $oldData, array $newData): array
    {
        $changes = [];

        // 1. Compare Fields
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
            'record_date',
            'status_realisasi'
        ];

        foreach ($fieldsToCheck as $field) {
            $oldVal = $oldData[$field] ?? null;
            $newVal = $newData[$field] ?? null;

            if ($this->isNumericField($field)) {
                if ((float) $oldVal !== (float) $newVal) {
                    $changes["field_{$field}"] = ['old' => $oldVal, 'new' => $newVal];
                }
            } else {
                if ((string) $oldVal !== (string) $newVal) {
                    $changes["field_{$field}"] = ['old' => $oldVal, 'new' => $newVal];
                }
            }
        }

        // 2. Compare Expense Items
        $oldItems = collect($oldData['expense_items'] ?? [])->keyBy('id');
        $newItems = collect($newData['expense_items'] ?? [])->keyBy('id');

        foreach ($oldItems as $id => $oldItem) {
            if (!$newItems->has($id)) {
                $changes["expense_item_{$id}_deleted"] = [
                    'old' => $oldItem['description'] ?? 'Unknown',
                    'new' => 'Deleted',
                ];
                continue;
            }

            $newItem = $newItems->get($id);

            if (($oldItem['description'] ?? '') !== ($newItem['description'] ?? '')) {
                $changes["expense_item_{$id}_description"] = [
                    'old' => $oldItem['description'],
                    'new' => $newItem['description'],
                ];
            }

            if (($oldItem['source_type'] ?? '') !== ($newItem['source_type'] ?? '')) {
                $changes["expense_item_{$id}_source_type"] = [
                    'old' => $oldItem['source_type'],
                    'new' => $newItem['source_type'],
                ];
            }

            if ((float) ($oldItem['amount'] ?? 0) !== (float) ($newItem['amount'] ?? 0)) {
                $changes["expense_item_{$id}_amount"] = [
                    'old' => $oldItem['amount'],
                    'new' => $newItem['amount'],
                ];
            }
        }

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
