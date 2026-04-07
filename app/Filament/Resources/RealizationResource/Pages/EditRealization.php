<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use App\Models\User;
use App\Services\WhatsAppService;
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
                ->keyBindings(['mod+s'])
                ->disabled(function () {
                    $data = $this->data ?? [];

                    if (!is_array($data)) {
                        return false;
                    }

                    $balance = isset($data['total_balance']) ? (float) $data['total_balance'] : 0;

                    return $balance < 0;
                }),
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
            'is_dirty' => $newValue !== $oldValue
        ]);

        if ($newValue !== $oldValue) {
            $this->shouldDispatchApprovalEvent = true;
            $this->approvalState = $newValue;
        }

        $items = $state['expenseItems'] ?? [];

        $totalExpense = 0.0;
        $totalRealization = 0.0;

        foreach ($items as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $realisasi = (float) ($item['realisasi'] ?? 0);

            if ($realisasi < 0) {
                $realisasi = 0;
            }

            $totalExpense += $amount;
            $totalRealization += $realisasi;
        }

        $totalBalance = $totalExpense - $totalRealization;

        $this->data['total_expense'] = $totalExpense;
        $this->data['total_realization'] = $totalRealization;
        $this->data['total_balance'] = $totalBalance;

        if ($totalBalance < 0) {
            throw ValidationException::withMessages([
                'data.total_balance' => 'Saldo akhir tidak boleh negatif.',
            ]);
        }
    }

    protected function afterSave(): void
    {
        Log::info('EditRealization: afterSave called', [
            'should_dispatch' => $this->shouldDispatchApprovalEvent,
            'approval_state' => $this->approvalState
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
        if (!$this->record) {
            return;
        }

        $user = Auth::user();

        if (!$user || !$user->can('update', $this->record)) {
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
                    . "Departemen: {$departmentName}\n"
                    . "Nama History: {$recordName}\n"
                    . "Bulan: {$monthName}";

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
