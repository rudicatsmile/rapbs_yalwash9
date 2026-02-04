<?php

namespace App\Filament\Resources\RealizationResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use App\Models\FinancialRecord;
use Filament\Actions\ExportAction;
use App\Filament\Exports\FinancialRecordExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class RealizationTable
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
                TextColumn::make('income_details')
                    ->label('Rincian Pemasukan')
                    ->html()
                    ->state(function ($record) {
                        $formatMoney = fn($amount) => 'Rp ' . number_format($amount, 0, ',', '.');

                        $html = '<div class="flex flex-col space-y-1">';
                        $html .= '<div class="font-bold text-success-600">';
                        $html .= $formatMoney($record->income_total);
                        $html .= '</div>';
                        $html .= '</div>';
                        return $html;
                    })
                    ->sortable(['income_total']),
                TextColumn::make('expense_details')
                    ->label('Rincian Pengeluaran')
                    ->html()
                    ->state(function ($record) {
                        $formatMoney = fn($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
                        $html = '<div class="flex flex-col space-y-1">';
                        $html .= '<div class="font-bold text-danger-600">';
                        $html .= $formatMoney($record->total_expense);
                        $html .= '</div>';
                        $html .= '</div>';
                        return $html;
                    })
                    ->sortable(['total_expense']),
                TextColumn::make('balance')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->state(function ($record) {
                        return $record->income_total - $record->total_expense;
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Departemen')
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ])
            ->actions([
                EditAction::make()
                    ->label('Realisasi')
                    ->icon('heroicon-o-calculator')
                    ->iconButton()
                    ->tooltip('Input Realisasi'),
            ])
            ->bulkActions([
                // No bulk actions for Realization typically, or keep delete?
                // User didn't specify. I'll remove bulk actions to be safe or just keep delete.
                // "Duplikasi dan modifikasi...". I'll keep it simple.
            ]);
    }
}
