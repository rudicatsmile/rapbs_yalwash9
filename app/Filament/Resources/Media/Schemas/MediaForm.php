<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('file_name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                Select::make('collection_name')
                    ->options([
                        'avatar' => 'Avatar',
                        'featured-image' => 'Featured Image',
                        'gallery' => 'Gallery',
                        'documents' => 'Documents',
                        'attachments' => 'Attachments',
                    ])
                    ->searchable(),

                TextInput::make('mime_type')
                    ->disabled()
                    ->label('MIME Type'),

                TextInput::make('size')
                    ->disabled()
                    ->suffix('bytes')
                    ->label('File Size'),

                KeyValue::make('custom_properties')
                    ->keyLabel('Property')
                    ->valueLabel('Value'),
            ]);
    }
}
