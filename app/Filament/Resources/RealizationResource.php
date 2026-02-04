<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RealizationResource\Pages\CreateRealization;
use App\Filament\Resources\RealizationResource\Pages\EditRealization;
use App\Filament\Resources\RealizationResource\Pages\ListRealizations;
use App\Filament\Resources\RealizationResource\Schemas\RealizationForm;
use App\Filament\Resources\RealizationResource\Tables\RealizationTable;
use App\Models\Realization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RealizationResource extends Resource
{
    protected static ?string $model = Realization::class;

    protected static ?string $navigationLabel = 'Realisasi';
    protected static ?string $modelLabel = 'Realisasi';
    protected static ?string $pluralModelLabel = 'Realisasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withSum([
            'expenseItems as mandiri_expense' => function ($query) {
                $query->where('source_type', 'Mandiri');
            }
        ], 'amount');

        $query->withSum([
            'expenseItems as bos_expense' => function ($query) {
                $query->where('source_type', 'BOS');
            }
        ], 'amount');

        $user = auth()->user();

        if ($user && $user->hasRole('user') && !$user->hasRole(['super_admin', 'admin', 'Admin', 'Super admin', 'editor', 'Editor'])) {
            $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return RealizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RealizationTable::configure($table);
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
            'index' => ListRealizations::route('/'),
            'create' => CreateRealization::route('/create'),
            'edit' => EditRealization::route('/{record}/edit'),
        ];
    }
}
