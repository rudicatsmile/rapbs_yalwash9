<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRealization extends EditRecord
{
    protected static string $resource = RealizationResource::class;

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
