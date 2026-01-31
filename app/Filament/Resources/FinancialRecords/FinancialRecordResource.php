<?php

namespace App\Filament\Resources\FinancialRecords;

use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use App\Filament\Resources\FinancialRecords\Pages\EditFinancialRecord;
use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Filament\Resources\FinancialRecords\Schemas\FinancialRecordForm;
use App\Filament\Resources\FinancialRecords\Tables\FinancialRecordsTable;
use App\Models\FinancialRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FinancialRecordResource extends Resource
{
    protected static ?string $model = FinancialRecord::class;

    protected static ?string $navigationLabel = 'RAPB Sekolah';
    protected static ?string $modelLabel = 'RAPB Sekolah';
    protected static ?string $pluralModelLabel = 'RAPB Sekolah';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('user') && !$user->hasRole(['super_admin', 'admin', 'Admin', 'Super admin', 'editor', 'Editor'])) {
            $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return FinancialRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialRecordsTable::configure($table);
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
            'index' => ListFinancialRecords::route('/'),
            'create' => CreateFinancialRecord::route('/create'),
            'edit' => EditFinancialRecord::route('/{record}/edit'),
        ];
    }
}
