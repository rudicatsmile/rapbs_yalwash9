<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('log_name')
                                    ->label('Log Name')
                                    ->placeholder('-'),
                                TextEntry::make('event')
                                    ->label('Event')
                                    ->placeholder('-'),
                                TextEntry::make('subject_type')
                                    ->label('Subject Type')
                                    ->placeholder('-'),
                                TextEntry::make('subject_id')
                                    ->label('Subject ID')
                                    ->numeric()
                                    ->placeholder('-'),
                                TextEntry::make('causer_type')
                                    ->label('Causer Type')
                                    ->placeholder('-'),
                                TextEntry::make('causer_id')
                                    ->label('Causer ID')
                                    ->numeric()
                                    ->placeholder('-'),
                                TextEntry::make('batch_uuid')
                                    ->label('Batch UUID')
                                    ->columnSpan(2)
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Description')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
                Section::make('Changes')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        KeyValueEntry::make('oldProperties')
                            ->label('Old Changes'),
                        KeyValueEntry::make('attributesProperties')
                            ->label('New Changes'),
                    ])
            ]);
    }

    private static function formatValue($value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return (string) $value;
    }
}
