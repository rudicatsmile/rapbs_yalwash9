<?php

namespace App\Filament\Resources\SchoolOfficials\Pages;

use App\Filament\Resources\SchoolOfficials\SchoolOfficialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolOfficials extends ListRecords
{
    protected static string $resource = SchoolOfficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
