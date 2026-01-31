<?php

namespace App\Filament\Resources\Books\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Book Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('year')
                            ->required()
                            ->numeric()
                            ->minValue(1000)
                            ->maxValue(9999)
                            ->helperText('Enter a 4-digit year'),

                        Textarea::make('summary')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(3),
            ]);
    }
}
