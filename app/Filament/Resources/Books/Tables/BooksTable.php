<?php

namespace App\Filament\Resources\Books\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->sortable(),
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('summary')
                    ->limit(50)
                    ->wrap(),
            ])
            ->filters([
                Filter::make('title')
                    ->label('Title')
                    ->schema([
                        TextInput::make('title')
                            ->placeholder('Search by title')
                            ->debounce(500)
                            ->columnSpanFull(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['title'],
                                fn(Builder $query, $title): Builder => $query->where('title', 'like', "%{$title}%"),
                            );
                    }),
                Filter::make('summary')
                    ->label('Summary')
                    ->schema([
                        TextInput::make('summary')
                            ->placeholder('Search by summary')
                            ->debounce(500)
                            ->columnSpanFull(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['summary'],
                                fn(Builder $query, $summary): Builder => $query->where('summary', 'like', "%{$summary}%"),
                            );
                    }),
                Filter::make('year')
                    ->label('Year Range')
                    ->schema([
                        TextInput::make('year_from')
                            ->label('From Year')
                            ->numeric()
                            ->placeholder('e.g., 2000')
                            ->columns(2),
                        TextInput::make('year_to')
                            ->label('To Year')
                            ->numeric()
                            ->placeholder('e.g., 2024')
                            ->columns(2),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year_from'],
                                fn(Builder $query, $year): Builder => $query->where('year', '>=', $year),
                            )
                            ->when(
                                $data['year_to'],
                                fn(Builder $query, $year): Builder => $query->where('year', '<=', $year),
                            );
                    }),
            ], FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
