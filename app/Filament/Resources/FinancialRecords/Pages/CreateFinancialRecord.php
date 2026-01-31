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

        return $data;
    }
}
