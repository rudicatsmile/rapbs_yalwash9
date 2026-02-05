<?php

namespace App\Filament\Widgets;

use App\Models\FinancialRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class FinancialRecordsGridWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Ef-fin9 Overview';
    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = FinancialRecord::query()
                    ->with('department')
                    ->latest('record_date');

                /** @var \App\Models\User $user */
                $user = auth()->user();

                if (!$user) {
                    return $query->whereRaw('1 = 0');
                }

                // Admin, Super admin, and Editor see all records
                if ($user->hasAnyRole(['super_admin', 'admin', 'Admin', 'Super admin', 'editor', 'Editor'])) {
                    return $query;
                }

                // Regular users only see records from their department
                if ($user->department_id) {
                    return $query->where('department_id', $user->department_id);
                }

                // User with no role and no department sees nothing
                return $query->whereRaw('1 = 0');
            })
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('department.name')
                        ->icon('heroicon-m-building-office')
                        ->weight(FontWeight::Bold)
                        ->color('primary'),

                    Tables\Columns\TextColumn::make('record_name')
                        ->weight(FontWeight::Medium)
                        ->limit(30),

                    Tables\Columns\TextColumn::make('record_date')
                        ->date('d M Y')
                        ->color('gray')
                        ->icon('heroicon-m-calendar'),

                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('income_total')
                            ->money('Rp. ')
                            ->color('success')
                            ->prefix('Pemasukan: '),

                        Tables\Columns\TextColumn::make('total_expense')
                            ->money('Rp. ')
                            ->color('danger')
                            ->prefix('Pengeluaran: '),
                    ]),

                    Tables\Columns\TextColumn::make('balance')
                        ->state(fn(FinancialRecord $record): float => $record->income_total - $record->total_expense)
                        ->money('Rp. ')
                        ->badge()
                        ->color(fn(float $state): string => $state >= 0 ? 'success' : 'danger')
                        ->formatStateUsing(fn(float $state): string => ($state >= 0 ? '+' : '') . number_format($state, 2, ',', '.'))
                        ->icon(fn(float $state): string => $state >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),

                    Tables\Columns\Layout\Split::make([
                        //Tambah field 'status'. Jika nilai = 1 maka 'Disetujui', jika 0 maka 'Belum Disetujui'
                        Tables\Columns\TextColumn::make('status')
                            ->label('Status')
                            ->state(fn(FinancialRecord $record): string => $record->status == 1 ? 'Disetujui' : 'Belum Disetujui')
                            ->badge()
                            ->color(fn(string $state): string => $state == 'Disetujui' ? 'success' : 'danger')
                            ->weight(FontWeight::Medium),

                        //Tambah field 'status_realisasi'. Jika nilai = 1 maka 'Terlaporkan', jika 0 maka 'Belum Terlaporkan'
                        Tables\Columns\TextColumn::make('status_realisasi')
                            ->label('Status Realisasi')
                            ->state(fn(FinancialRecord $record): string => $record->status_realisasi == 1 ? 'Terlaporkan' : 'Belum Terlaporkan')
                            ->badge()
                            ->color(fn(string $state): string => $state == 'Terlaporkan' ? 'success' : 'danger')
                            ->weight(FontWeight::Medium)
                            ->alignment('end'),
                    ]),


                ])->space(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Departemen')
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginated([12, 24, 48])
            ->defaultPaginationPageOption(12)
            ->emptyStateHeading('No financial records found')
            ->emptyStateDescription('Create a new financial record to get started.')
            ->emptyStateIcon('heroicon-o-document-currency-dollar')
            ->actions([
                // Tables\Actions\Action::make('view')
                //     ->url(fn (FinancialRecord $record): string => route('filament.admin.resources.financial-records.edit', $record))
                //     ->icon('heroicon-m-eye')
                //     ->button()
                //     ->label('Detail'),
            ]);
    }
}
