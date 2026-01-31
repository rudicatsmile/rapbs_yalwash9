<?php

namespace App\Filament\Resources\ImpersonationLogs\Pages;

use App\Filament\Resources\ImpersonationLogs\ImpersonationLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateImpersonationLog extends CreateRecord
{
    protected static string $resource = ImpersonationLogResource::class;
}
