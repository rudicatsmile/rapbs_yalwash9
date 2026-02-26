<?php

namespace App\Filament\Resources\FinancialRecords\Pages;

use App\Filament\Resources\FinancialRecords\FinancialRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialRecord extends CreateRecord
{
    protected static string $resource = FinancialRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Ensure status is set to Active (1) for users who cannot see the toggle
        // The form defaults to true (Active), but since it's hidden for users, 
        // it falls back to DB default which might be 0 (Inactive).
        if (!isset($data['status']) && auth()->user()->hasRole('user')) {
            $data['status'] = true;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
