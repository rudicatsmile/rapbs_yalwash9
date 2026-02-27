<?php

namespace App\Listeners;

use App\Events\RealizationApproved;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWhatsAppRealizationApprovedNotification // implements ShouldQueue
{
    // use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RealizationApproved $event): void
    {
        Log::info('SendWhatsAppRealizationApprovedNotification: Listener triggered', [
            'realization_id' => $event->realization->id,
            'state' => $event->state
        ]);

        if (!$event->state) {
            Log::info('SendWhatsAppRealizationApprovedNotification: Skipped (Not approved)');
            return;
        }

        try {
            $record = $event->realization;
            $department = $record->department;

            if (!$department || !$department->phone) {
                Log::warning('WhatsApp notification failed: No phone number found for department', [
                    'department_id' => $record->department_id,
                    'realization_id' => $record->id,
                ]);
                return;
            }

            $phone = $department->phone;
            Log::info('SendWhatsAppRealizationApprovedNotification: Preparing to send message', [
                'phone' => $phone
            ]);

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
                12 => 'Desember'
            ];
            $monthLabel = $monthNames[$record->month] ?? '-';

            $message = "*Ef-Fin9 Sistem*\n\n" .
                "Pengajuan Realisasi Anda telah disetujui oleh Bendahara.\n\n" .
                "Detail:\n" .
                "Judul: {$record->record_name}\n" .
                "Bulan: {$monthLabel}\n" .
                "Lembaga: {$department->name}\n" .
                "Total Realisasi: Rp " . number_format($record->total_realization, 0, ',', '.') . "\n\n" .
                "Terima kasih.";

            $waService = new WhatsAppService();
            $success = $waService->sendMessage($phone, $message);

            if ($success) {
                Log::info('WhatsApp notification sent successfully', [
                    'realization_id' => $record->id,
                    'phone' => $phone,
                ]);
            } else {
                Log::error('WhatsApp notification failed to send', [
                    'realization_id' => $record->id,
                    'phone' => $phone,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in SendWhatsAppRealizationApprovedNotification: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
