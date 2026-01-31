<?php

namespace App\Filament\Resources\FinancialRecords\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class FinancialRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('record_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('record_name')
                    ->label('Nama History')
                    ->searchable(),
                TextColumn::make('income_fixed')
                    ->label('Total Pemasukan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_expense')
                    ->label('Total Pengeluaran')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->state(function ($record) {
                        return $record->income_fixed - $record->total_expense;
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                // Grouping actions horizontally using simple array structure (rendered inline by default).
                // Converted to Icon Buttons to save space and ensure responsiveness.
                // Tooltips added for better UX.
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Record'),

                ReplicateAction::make()
                    ->label('Duplicate')
                    ->modalHeading('Duplicate Record')
                    ->modalDescription('Are you sure you want to duplicate this record? This will create a new entry with the same values.')
                    ->modalSubmitActionLabel('Yes, Duplicate')
                    ->beforeReplicaSaved(function ($replica) {
                        $replica->status = true;
                    })
                    ->iconButton() // Render as icon button
                    ->tooltip('Duplicate Record'), // Add tooltip

                Action::make('status')
                    ->label(fn($record) => $record->status ? 'Active' : 'Inactive')
                    ->icon(fn($record) => $record->status ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->color(fn($record) => $record->status ? 'success' : 'danger')
                    ->action(fn($record) => $record->update(['status' => !$record->status]))
                    ->disabled(fn() => auth()->user() && auth()->user()->hasRole('user'))
                    ->iconButton() // Render as icon button
                    ->tooltip(fn($record) => $record->status ? 'Deactivate Record' : 'Activate Record'), // Dynamic tooltip
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('duplicate')
                        ->label('Duplicate Selected')
                        ->icon('heroicon-m-document-duplicate')
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Selected Records')
                        ->modalDescription('Are you sure you want to duplicate the selected records?')
                        ->modalSubmitActionLabel('Yes, Duplicate')
                        ->action(function (Collection $records) {
                            // Backend authorization check
                            if (! auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Access Denied')
                                    ->body('You do not have permission to perform this action.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($records as $record) {
                                $newRecord = $record->replicate();
                                $newRecord->status = true;
                                $newRecord->save();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ])
                ->visible(fn () => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ]);
    }
}
