<?php

namespace App\Filament\Resources\FinancialRecords\Pages;

use App\Filament\Resources\FinancialRecords\FinancialRecordResource;
use App\Services\WhatsAppService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditFinancialRecord extends EditRecord
{
    protected static string $resource = FinancialRecordResource::class;

    protected bool $shouldSendInactiveStatusWhatsAppNotification = false;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $state = $this->form->getState();

        $newValue = (bool) ($state['status'] ?? false);
        $oldValue = (bool) $this->record->status;

        if (($newValue !== $oldValue) && ($newValue === false)) {
            $this->shouldSendInactiveStatusWhatsAppNotification = true;
        }
    }

    protected function afterSave(): void
    {
        if (! $this->shouldSendInactiveStatusWhatsAppNotification) {
            return;
        }

        $this->record->refresh();
        $department = $this->record->department;

        if (! $department) {
            Log::warning('WhatsApp notification skipped (edit): department not found', [
                'financial_record_id' => $this->record->id,
                'department_id' => $this->record->department_id,
                'user_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Departemen tidak ditemukan')
                ->body('Notifikasi WhatsApp tidak dapat dikirim karena departemen tidak valid.')
                ->danger()
                ->send();

            $this->shouldSendInactiveStatusWhatsAppNotification = false;

            return;
        }

        $phone = (string) ($department->phone ?? '');
        $waService = new WhatsAppService();

        if (! $waService->isValidPhone($phone)) {
            Log::warning('WhatsApp notification skipped (edit): invalid department phone', [
                'financial_record_id' => $this->record->id,
                'department_id' => $department->id,
                'phone' => $phone,
                'user_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Nomor WhatsApp departemen tidak valid')
                ->body('Periksa nomor telepon pada data departemen.')
                ->danger()
                ->send();

            $this->shouldSendInactiveStatusWhatsAppNotification = false;

            return;
        }

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

        $monthNumber = (int) ($this->record->month ?? 0);
        $monthLabel = $monthNames[$monthNumber] ?? '-';

        $recordDateFormatted = $this->record->record_date?->format('d-m-Y') ?? '-';
        $recordName = (string) ($this->record->record_name ?? '-');
        $incomeTotal = (float) ($this->record->income_total ?? 0);
        $timestamp = now()->format('d-m-Y H:i');
        $actorName = auth()->user()?->name ?? '-';

        $message = "*Ef-Fin9 Sistem*\n\n"
            . "Perubahan status Financial Record menjadi *TIDAK AKTIF*.\n\n"
            . "Departemen: {$department->name}\n"
            . "Nama History: {$recordName}\n"
            . "Tanggal: {$recordDateFormatted}\n"
            . "Bulan: {$monthLabel}\n"
            . "Total Pemasukan: Rp " . number_format($incomeTotal, 0, ',', '.') . "\n"
            . "Diubah oleh: {$actorName}\n"
            . "Waktu: {$timestamp}";

        Log::info('Attempting WhatsApp notification (edit: status inactive)', [
            'financial_record_id' => $this->record->id,
            'department_id' => $department->id,
            'phone' => $waService->normalizePhone($phone),
            'user_id' => auth()->id(),
        ]);

        $success = $waService->sendMessage($phone, $message);

        if ($success) {
            Notification::make()
                ->title('Notifikasi WhatsApp terkirim')
                ->body("Departemen {$department->name} telah menerima pemberitahuan status tidak aktif.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal mengirim WhatsApp')
                ->body('Perubahan berhasil disimpan, namun notifikasi WhatsApp gagal terkirim. Silakan coba lagi atau periksa koneksi / token WhatsApp.')
                ->danger()
                ->send();
        }

        $this->shouldSendInactiveStatusWhatsAppNotification = false;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
