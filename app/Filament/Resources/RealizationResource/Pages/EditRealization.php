<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use App\Models\ExpenseItem;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EditRealization extends EditRecord
{
    protected static string $resource = RealizationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
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
                $amount = (float) ($item->amount ?? 0);
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
            $totalExpense = (float) ExpenseItem::query()
                ->where('financial_record_id', $this->record->id)
                ->sum('amount');

            $totalRealization = (float) ExpenseItem::query()
                ->where('financial_record_id', $this->record->id)
                ->sum('realisasi');

            $data['total_expense'] = $totalExpense;
            $data['total_realization'] = $totalRealization;
            $data['total_balance'] = $totalExpense - $totalRealization;
        } else {
            $data['total_expense'] = null;
            $data['total_realization'] = null;
            $data['total_balance'] = null;
        }

        return $data;
    }

    public function mount($record): void
    {
        parent::mount($record);
        if (Auth::check() && Auth::user()->hasRole('user') && $this->record?->status_realisasi == 1) {
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

        ExpenseItem::query()
            ->where('financial_record_id', $this->record->id)
            ->update([
                'realisasi' => 0,
                'saldo' => 0,
                'is_selected_for_realization' => false,
            ]);

        $expenseItemIds = collect(array_values($items))
            ->pluck('expense_item_id')
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (string) $id)
            ->all();

        $duplicateIds = collect($expenseItemIds)
            ->countBy()
            ->filter(fn ($count) => $count > 1)
            ->keys()
            ->all();

        foreach (array_values($items) as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $expenseItemId = $item['expense_item_id'] ?? null;
            $rawRealisasi = $item['realisasi'] ?? null;

            if ($description === '') {
                $errors["data.expenseItems.{$index}.description"] = 'Keterangan wajib diisi.';
            }

            if (! $expenseItemId) {
                $errors["data.expenseItems.{$index}.expense_item_id"] = 'Sumber anggaran wajib dipilih.';
            }

            if ($expenseItemId && in_array((string) $expenseItemId, $duplicateIds, true)) {
                $errors["data.expenseItems.{$index}.expense_item_id"] = 'Sumber anggaran tidak boleh duplikat.';
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

            $realisasi = (float) $this->parseMoney($rawRealisasi);

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

            $expenseItem->description = $description;
            $expenseItem->realisasi = $realisasi;
            $expenseItem->saldo = (float) $expenseItem->amount - $realisasi;
            $expenseItem->is_selected_for_realization = true;
            $expenseItem->save();

            Log::info('Realization expense item updated', [
                'realization_id' => $this->record->id,
                'expense_item_id' => (int) $expenseItem->id,
                'user_id' => auth()->id(),
                'realisasi' => $realisasi,
            ]);
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $this->record->refresh();

        $totalExpense = (float) $this->record->expenseItems()->sum('amount');
        $totalRealization = (float) $this->record->expenseItems()->sum('realisasi');
        $totalBalance = $totalExpense - $totalRealization;

        $this->data['total_expense'] = $totalExpense;
        $this->data['total_realization'] = $totalRealization;
        $this->data['total_balance'] = $totalBalance;
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
