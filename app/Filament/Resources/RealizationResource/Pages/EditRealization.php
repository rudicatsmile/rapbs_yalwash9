<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use Filament\Actions;
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

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
