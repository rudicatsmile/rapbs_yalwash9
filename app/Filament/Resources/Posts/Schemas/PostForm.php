<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Post Content')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        RichEditor::make('content')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ]),

                        DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->columnSpan(2),

                Section::make('Media')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured-image')
                            ->label('Featured Image')
                            ->collection('featured-image')
                            ->multiple()
                            ->maxFiles(1)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120),

                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('Gallery Images')
                            ->collection('gallery')
                            ->multiple()
                            ->maxFiles(1)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxFiles(10)
                            ->maxSize(5120),

                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'application/zip'])
                            ->maxSize(10240),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
