<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

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
