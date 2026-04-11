<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use App\Models\ExpenseItem;
use App\Models\RealizationExpenseLine;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EditRealization extends EditRecord
{
    protected static string $resource = RealizationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $lines = $this->record->realizationExpenseLines()->orderBy('id')->get();

        if ($lines->isNotEmpty()) {
            $sourceCounts = $lines
                ->pluck('expense_item_id')
                ->map(fn ($id) => (string) $id)
                ->countBy()
                ->all();

            $runningSaldo = [];
            $expenseItems = [];

            foreach ($lines as $line) {
                $sourceId = (string) $line->expense_item_id;
                $amount = (float) ($line->allocated_amount ?? 0);
                $realisasi = (float) ($line->realisasi ?? 0);

                if (($sourceCounts[$sourceId] ?? 0) > 1) {
                    if (! array_key_exists($sourceId, $runningSaldo)) {
                        $displayAmount = $amount;
                        $saldo = $displayAmount - $realisasi;
                    } else {
                        $displayAmount = max(0, (float) $runningSaldo[$sourceId]);
                        $saldo = $displayAmount - $realisasi;
                    }

                    $runningSaldo[$sourceId] = $saldo;
                } else {
                    $displayAmount = $amount;
                    $saldo = $amount - $realisasi;
                }

                $expenseItems[] = [
                    'description' => (string) $line->description,
                    'expense_item_id' => (string) $line->expense_item_id,
                    'amount' => number_format($displayAmount, 0, ',', '.'),
                    'realisasi' => number_format($realisasi, 0, ',', '.'),
                    'saldo' => number_format($saldo, 0, ',', '.'),
                ];
            }

            $data['expenseItems'] = $expenseItems;

            $totalExpense = (float) $lines->sum('allocated_amount');
            $totalRealization = (float) $lines->sum('realisasi');

            $data['total_expense'] = $totalExpense;
            $data['total_realization'] = $totalRealization;
            $data['total_balance'] = $totalExpense - $totalRealization;
        } else {
            $items = ExpenseItem::query()
                ->where('financial_record_id', $this->record->id)
                ->where(function ($query) {
                    $query
                        ->where('is_selected_for_realization', true)
                        ->orWhere('realisasi', '<>', 0)
                        ->orWhere('saldo', '<>', 0);
                })
                ->orderBy('id')
                ->get();

            $data['expenseItems'] = $items
                ->map(function (ExpenseItem $item) {
                    $amount = (float) ($item->allocated_amount ?? $item->amount ?? 0);
                    $realisasi = (float) ($item->realisasi ?? 0);
                    $saldo = $amount - $realisasi;

                    return [
                        'description' => (string) $item->description,
                        'expense_item_id' => (string) $item->id,
                        'amount' => number_format($amount, 0, ',', '.'),
                        'realisasi' => number_format($realisasi, 0, ',', '.'),
                        'saldo' => number_format($saldo, 0, ',', '.'),
                    ];
                })
                ->values()
                ->all();

            if (! empty($data['expenseItems'])) {
                $totalExpense = 0.0;
                $totalRealization = 0.0;

                foreach ($items as $item) {
                    $totalExpense += (float) ($item->allocated_amount ?? $item->amount ?? 0);
                    $totalRealization += (float) ($item->realisasi ?? 0);
                }

                $data['total_expense'] = $totalExpense;
                $data['total_realization'] = $totalRealization;
                $data['total_balance'] = $totalExpense - $totalRealization;
            } else {
                $data['total_expense'] = null;
                $data['total_realization'] = null;
                $data['total_balance'] = null;
            }
        }

        return $data;
    }

    public function mount($record): void
    {
        parent::mount($record);
        if (Auth::check() && Auth::user()->hasRole('user') && $this->record?->is_approved_by_bendahara == 1) {
            abort(403, 'Forbidden');
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data berhasil disimpan';
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected $shouldDispatchApprovalEvent = false;

    protected $approvalState = false;

    protected function beforeSave(): void
    {
        $state = $this->form->getState();

        // Check for approval status change
        $newValue = (bool) ($state['is_approved_by_bendahara'] ?? false);
        $oldValue = (bool) $this->record->is_approved_by_bendahara;

        Log::info('EditRealization: beforeSave check', [
            'record_id' => $this->record->id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'is_dirty' => $newValue !== $oldValue,
        ]);

        if ($newValue !== $oldValue) {
            $this->shouldDispatchApprovalEvent = true;
            $this->approvalState = $newValue;
        }

        $items = $state['expenseItems'] ?? [];

        $errors = [];
        $normalizedItems = [];

        $sourceIds = [];

        foreach ($items as $item) {
            $id = data_get($item, 'expense_item_id');

            if (filled($id)) {
                $sourceIds[] = (string) $id;
            }
        }

        $sourceCounts = array_count_values($sourceIds);
        $budgets = [];
        $spent = [];
        $isFirstRowForSource = [];

        foreach (array_values($items) as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $expenseItemId = $item['expense_item_id'] ?? null;
            $rawAllocatedAmount = $item['amount'] ?? null;
            $rawRealisasi = $item['realisasi'] ?? null;

            if ($description === '') {
                $errors["data.expenseItems.{$index}.description"] = 'Keterangan wajib diisi.';
            }

            if (! $expenseItemId) {
                $errors["data.expenseItems.{$index}.expense_item_id"] = 'Sumber anggaran wajib dipilih.';
            }

            $isDuplicateSource = $expenseItemId && (($sourceCounts[(string) $expenseItemId] ?? 0) > 1);
            $sourceKey = $expenseItemId ? (string) $expenseItemId : null;

            if ($rawAllocatedAmount === null || $rawAllocatedAmount === '') {
                if (! $isDuplicateSource || ! $sourceKey || ! array_key_exists($sourceKey, $isFirstRowForSource)) {
                    $errors["data.expenseItems.{$index}.amount"] = 'Anggaran wajib diisi.';
                }
            }

            if ($rawRealisasi === null || $rawRealisasi === '') {
                $errors["data.expenseItems.{$index}.realisasi"] = 'Realisasi wajib diisi.';
            }

            if ($errors) {
                continue;
            }

            $expenseItem = $this->record->expenseItems()
                ->whereKey($expenseItemId)
                ->first();

            if (! $expenseItem) {
                $errors["data.expenseItems.{$index}.expense_item_id"] = 'Sumber anggaran tidak ditemukan pada RAPBS.';

                continue;
            }

            $allocatedAmount = (float) $this->parseMoney($rawAllocatedAmount);
            $realisasi = (float) $this->parseMoney($rawRealisasi);

            if ($isDuplicateSource && $sourceKey) {
                if (! array_key_exists($sourceKey, $isFirstRowForSource)) {
                    $isFirstRowForSource[$sourceKey] = true;
                    $budgets[$sourceKey] = $allocatedAmount;
                    $spent[$sourceKey] = 0.0;
                } else {
                    $allocatedAmount = 0.0;
                }

                $availableBefore = (float) ($budgets[$sourceKey] ?? 0) - (float) ($spent[$sourceKey] ?? 0);

                if ($realisasi > $availableBefore) {
                    $errors["data.expenseItems.{$index}.realisasi"] = 'Saldo sumber tidak cukup. Sisa saldo sebelum baris ini Rp '.number_format($availableBefore, 0, ',', '.').', realisasi yang dimasukkan Rp '.number_format($realisasi, 0, ',', '.').'.';

                    continue;
                }

                $spent[$sourceKey] = (float) ($spent[$sourceKey] ?? 0) + $realisasi;
            }

            if ($allocatedAmount < 0) {
                $errors["data.expenseItems.{$index}.amount"] = 'Anggaran harus bernilai positif.';

                continue;
            }

            if ($allocatedAmount > 2000000000) {
                $errors["data.expenseItems.{$index}.amount"] = 'Anggaran maksimal 2.000.000.000.';

                continue;
            }

            if (floor($allocatedAmount) !== $allocatedAmount) {
                $errors["data.expenseItems.{$index}.amount"] = 'Anggaran harus berupa angka bulat.';

                continue;
            }

            if ($realisasi < 0) {
                $errors["data.expenseItems.{$index}.realisasi"] = 'Realisasi harus bernilai positif.';

                continue;
            }

            if ($realisasi > 2000000000) {
                $errors["data.expenseItems.{$index}.realisasi"] = 'Realisasi maksimal 2.000.000.000.';

                continue;
            }

            if (floor($realisasi) !== $realisasi) {
                $errors["data.expenseItems.{$index}.realisasi"] = 'Realisasi harus berupa angka bulat.';

                continue;
            }

            $normalizedItems[] = [
                'expense_item_id' => (string) $expenseItemId,
                'description' => $description,
                'allocated_amount' => $allocatedAmount,
                'realisasi' => $realisasi,
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        RealizationExpenseLine::query()
            ->where('financial_record_id', $this->record->id)
            ->delete();

        foreach ($normalizedItems as $item) {
            RealizationExpenseLine::query()->create([
                'financial_record_id' => $this->record->id,
                'expense_item_id' => $item['expense_item_id'],
                'description' => $item['description'],
                'allocated_amount' => $item['allocated_amount'],
                'realisasi' => $item['realisasi'],
            ]);
        }

        $this->record->refresh();

        $lines = $this->record->realizationExpenseLines()->get();
        $totalExpense = (float) $lines->sum('allocated_amount');
        $totalRealization = (float) $lines->sum('realisasi');
        $totalBalance = $totalExpense - $totalRealization;

        $this->data['total_expense'] = $totalExpense;
        $this->data['total_realization'] = $totalRealization;
        $this->data['total_balance'] = $totalBalance;

        ExpenseItem::query()
            ->where('financial_record_id', $this->record->id)
            ->update([
                'realisasi' => 0,
                'saldo' => DB::raw('amount'),
                'allocated_amount' => 0,
                'is_selected_for_realization' => false,
            ]);

        $byExpenseItemId = [];

        foreach ($normalizedItems as $item) {
            $key = (string) $item['expense_item_id'];

            if (! array_key_exists($key, $byExpenseItemId)) {
                $byExpenseItemId[$key] = [
                    'sum_allocated' => 0.0,
                    'sum_realisasi' => 0.0,
                ];
            }

            $byExpenseItemId[$key]['sum_allocated'] += (float) $item['allocated_amount'];
            $byExpenseItemId[$key]['sum_realisasi'] += (float) $item['realisasi'];
        }

        foreach ($byExpenseItemId as $expenseItemId => $sums) {
            $expenseItem = $this->record->expenseItems()
                ->whereKey($expenseItemId)
                ->first();

            if (! $expenseItem) {
                continue;
            }

            $expenseItem->allocated_amount = $sums['sum_allocated'];
            $expenseItem->realisasi = $sums['sum_realisasi'];
            $expenseItem->saldo = (float) $expenseItem->amount - $sums['sum_realisasi'];
            $expenseItem->is_selected_for_realization = true;
            $expenseItem->save();

            Log::info('Realization expense item aggregated', [
                'realization_id' => $this->record->id,
                'expense_item_id' => (int) $expenseItem->id,
                'user_id' => auth()->id(),
                'allocated_amount' => $expenseItem->allocated_amount,
                'realisasi' => $expenseItem->realisasi,
            ]);
        }
    }

    protected function parseMoney($value): float
    {
        if (empty($value)) {
            return 0;
        }

        $cleanValue = str_replace('.', '', (string) $value);
        $cleanValue = str_replace(',', '.', $cleanValue);
        $cleanValue = preg_replace('/[^0-9.\-]/', '', $cleanValue);

        return (float) $cleanValue;
    }

    protected function afterSave(): void
    {
        Log::info('EditRealization: afterSave called', [
            'should_dispatch' => $this->shouldDispatchApprovalEvent,
            'approval_state' => $this->approvalState,
        ]);

        if ($this->shouldDispatchApprovalEvent) {
            $record = $this->record;
            $state = $this->approvalState;
            $user = auth()->user();

            // Dispatch Event (Approved or Unapproved)
            \App\Events\RealizationApproved::dispatch($record, $user, $state);

            // Audit Log
            if (function_exists('activity')) {
                activity()
                    ->performedOn($record)
                    ->causedBy($user)
                    ->withProperties(['is_approved_by_bendahara' => $state])
                    ->log($state ? 'approved_realization' : 'unapproved_realization');
            }

            // Notification
            Notification::make()
                ->title($state ? 'Realisasi Disetujui' : 'Persetujuan Dibatalkan')
                ->body($state ? 'Status telah diperbarui menjadi disetujui.' : 'Status telah diperbarui menjadi belum disetujui.')
                ->success()
                ->send();

            // Reset flags
            $this->shouldDispatchApprovalEvent = false;
        }
    }

    public function updatedDataStatusRealisasi($value): void
    {
        if (! $this->record) {
            return;
        }

        $user = Auth::user();

        if (! $user || ! $user->can('update', $this->record)) {
            $this->data['status_realisasi'] = (bool) $this->record->status_realisasi;

            Notification::make()
                ->title('Akses ditolak')
                ->body('Anda tidak memiliki izin untuk mengubah status pelaporan realisasi ini.')
                ->danger()
                ->send();

            return;
        }

        if ($value) {
            $this->record->refresh();

            if ((float) $this->record->total_realization <= 0) {
                $this->data['status_realisasi'] = false;

                Notification::make()
                    ->title('Data realisasi belum lengkap')
                    ->body('Input dan simpan realisasi terlebih dahulu sebelum menandai siap pelaporan.')
                    ->warning()
                    ->send();

                return;
            }
        }

        $this->record->status_realisasi = (bool) $value;
        $this->record->save();

        Notification::make()
            ->title('Status pelaporan diperbarui')
            ->body($value ? 'Realisasi ditandai siap pelaporan.' : 'Realisasi dikembalikan ke belum pelaporan.')
            ->success()
            ->send();

        if ($value) {
            $roles = ['super_admin', 'admin', 'editor'];
            $users = User::role($roles)->get();

            if ($users->isNotEmpty()) {
                $realizationId = $this->record->id;
                $departmentName = $this->record->department?->name ?? '-';
                $recordName = $this->record->record_name ?? '-';
                $monthNumber = (int) ($this->record->month ?? 0);

                $monthNames = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember',
                ];

                $monthName = $monthNames[$monthNumber] ?? '-';

                $body = "Realisasi #{$realizationId} siap untuk pelaporan.\n"
                    ."Departemen: {$departmentName}\n"
                    ."Nama History: {$recordName}\n"
                    ."Bulan: {$monthName}";

                $databaseNotification = Notification::make()
                    ->title('Realisasi siap pelaporan')
                    ->body($body)
                    ->info()
                    ->toDatabase();

                foreach ($users as $recipient) {
                    $recipient->notifyNow($databaseNotification);
                    event(new DatabaseNotificationsSent($recipient));
                }
            }
        }
    }
}
