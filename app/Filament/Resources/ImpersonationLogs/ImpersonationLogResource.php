<?php

namespace App\Filament\Resources\ImpersonationLogs;

use App\Filament\Resources\ImpersonationLogs\Pages\ListImpersonationLogs;
use App\Filament\Resources\ImpersonationLogs\Schemas\ImpersonationLogForm;
use App\Filament\Resources\ImpersonationLogs\Tables\ImpersonationLogsTable;
use App\Models\ImpersonationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ImpersonationLogResource extends Resource
{
    protected static ?string $model = ImpersonationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $navigationLabel = 'Impersonation Logs';
    protected static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $modelLabel = 'Impersonation Log';

    public static function form(Schema $schema): Schema
    {
        return ImpersonationLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImpersonationLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImpersonationLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
