<?php

namespace App\Notifications;

use App\Models\Realization;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Actions\Action;

class RealizationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Realization $realization,
        public bool $isApproved
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->isApproved ? 'Disetujui' : 'Dibatalkan';
        $color = $this->isApproved ? 'success' : 'danger';
        $verb = $this->isApproved ? 'menyetujui' : 'membatalkan persetujuan';

        return (new MailMessage)
            ->subject("Status Realisasi {$status}: {$this->realization->record_name}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Status persetujuan untuk realisasi berikut telah diperbarui:")
            ->line("**Nama Realisasi:** {$this->realization->record_name}")
            ->line("**Tanggal:** " . ($this->realization->record_date ? $this->realization->record_date->format('d M Y') : '-'))
            ->line("**Status Baru:** {$status} oleh Bendahara")
            ->action('Lihat Detail Realisasi', \App\Filament\Resources\RealizationResource::getUrl('edit', ['record' => $this->realization]))
            ->line('Terima kasih telah menggunakan aplikasi kami.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $title = $this->isApproved ? 'Realisasi Disetujui' : 'Persetujuan Dibatalkan';
        $body = $this->isApproved 
            ? "Realisasi '{$this->realization->record_name}' telah disetujui oleh Bendahara." 
            : "Persetujuan realisasi '{$this->realization->record_name}' telah dibatalkan.";

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->status($this->isApproved ? 'success' : 'warning')
            ->actions([
                Action::make('view')
                    ->label('Lihat')
                    ->url(\App\Filament\Resources\RealizationResource::getUrl('edit', ['record' => $this->realization]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
