<?php

namespace App\Filament\Resources\RealizationResource\Pages;

use App\Filament\Resources\RealizationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRealization extends CreateRecord
{
    protected static string $resource = RealizationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data berhasil disimpan';
    }

    // Typically you don't create Realization from scratch, but if the user wants "Create",
    // it would just be creating a FinancialRecord.
    // I'll leave it standard.
}
