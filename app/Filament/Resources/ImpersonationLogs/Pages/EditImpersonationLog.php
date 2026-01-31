<?php

namespace App\Filament\Resources\ImpersonationLogs\Pages;

use App\Filament\Resources\ImpersonationLogs\ImpersonationLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImpersonationLog extends EditRecord
{
    protected static string $resource = ImpersonationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
