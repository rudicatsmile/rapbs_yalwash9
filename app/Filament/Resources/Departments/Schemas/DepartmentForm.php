<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->required()
                    ->regex('/^(\+62|62|08)[0-9]+$/')
                    ->placeholder('+62xxx atau 08xxx')
                    ->helperText('Format: +62xxx atau 08xxx. Digunakan untuk notifikasi WhatsApp.')
                    ->maxLength(20),
                TextInput::make('urut')
                    ->numeric()
                    ->label('Urut (Sequence)'),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
