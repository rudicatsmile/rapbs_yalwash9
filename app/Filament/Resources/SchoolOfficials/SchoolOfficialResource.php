<?php

namespace App\Filament\Resources\SchoolOfficials;

use App\Filament\Resources\SchoolOfficials\Pages\CreateSchoolOfficial;
use App\Filament\Resources\SchoolOfficials\Pages\EditSchoolOfficial;
use App\Filament\Resources\SchoolOfficials\Pages\ListSchoolOfficials;
use App\Filament\Resources\SchoolOfficials\Schemas\SchoolOfficialForm;
use App\Filament\Resources\SchoolOfficials\Tables\SchoolOfficialsTable;
use App\Models\SchoolOfficial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SchoolOfficialResource extends Resource
{
    protected static ?string $model = SchoolOfficial::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Otorisator';
    protected static ?string $modelLabel = 'Otorisator';
    protected static ?string $pluralModelLabel = 'Otorisator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return SchoolOfficialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolOfficialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'Admin', 'Super admin', 'editor', 'Editor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolOfficials::route('/'),
            'create' => CreateSchoolOfficial::route('/create'),
            'edit' => EditSchoolOfficial::route('/{record}/edit'),
        ];
    }
}
