<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dump_properties')
                ->label('Dump Properties')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    dump($this->record->properties);
                })
                ->tooltip('Dump properties to console'),
        ];
    }
}
