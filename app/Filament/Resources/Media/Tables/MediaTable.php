<?php

namespace App\Filament\Resources\Media\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->getStateUsing(function (Media $record) {
                        if ($record->hasGeneratedConversion('thumb')) {
                            return $record->getUrl('thumb');
                        }

                        return $record->mime_type && str_starts_with($record->mime_type, 'image/')
                            ? $record->getUrl()
                            : null;
                    })
                    ->square()
                    ->defaultImageUrl('/images/placeholder.jpg'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('collection_name')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mime_type')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2).' KB' : '-')
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('collection_name')
                    ->options([
                        'avatar' => 'Avatar',
                        'featured-image' => 'Featured Image',
                        'gallery' => 'Gallery',
                        'documents' => 'Documents',
                        'attachments' => 'Attachments',
                    ])
                    ->multiple(),

                Filter::make('images')
                    ->label('Images only')
                    ->query(fn ($query) => $query->where('mime_type', 'like', 'image/%')),

                Filter::make('documents')
                    ->label('Documents only')
                    ->query(fn ($query) => $query->where('mime_type', 'like', 'application/%')),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Media $record) => $record->getUrl(), shouldOpenInNewTab: true),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
