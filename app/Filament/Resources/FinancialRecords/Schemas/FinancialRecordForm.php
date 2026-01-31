<?php

namespace App\Filament\Resources\FinancialRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Components\Toggle;

class FinancialRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Header')
                    ->schema([
                        Toggle::make('status')
                            ->label('Status Aktif')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true)
                            ->visible(fn() => auth()->user() && !auth()->user()->hasRole('user'))
                            ->disabled(fn() => auth()->user() && auth()->user()->hasRole('user'))
                            ->columnSpanFull(),
                        Select::make('department_id')
                            ->relationship('department', 'name', modifyQueryUsing: function (Builder $query) {
                                $user = auth()->user();
                                if ($user && $user->hasRole('user') && !$user->hasRole(['super_admin', 'admin'])) {
                                    $query->where('id', $user->department_id);
                                }
                            })
                            ->label('Departemen')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => auth()->user() && auth()->user()->hasRole('user') && !auth()->user()->hasRole(['super_admin', 'admin']) ? auth()->user()->department_id : null)
                            ->disabled(fn() => auth()->user() && auth()->user()->hasRole('user') && !auth()->user()->hasRole(['super_admin', 'admin']))
                            ->dehydrated(),
                        DatePicker::make('record_date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),
                        TextInput::make('record_name')
                            ->label('Nama History')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Rencana Pemasukan')
                    ->schema([
                        TextInput::make('income_amount')
                            ->label('Pemasukan (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateIncomeFixed($get, $set);
                            }),
                        TextInput::make('income_percentage')
                            ->label('Persentase (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateIncomeFixed($get, $set);
                            }),
                        TextInput::make('income_fixed')
                            ->label('Fix (Pemasukan x Persentase)')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated(),
                    ])->columns(1),

                Section::make('Rencana Pengeluaran')
                    ->schema([
                        TextInput::make('total_expense')
                            ->label('Total Rencana Pengeluaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0),

                        Repeater::make('expenseItems')
                            ->relationship('expenseItems')
                            ->label('Daftar Pengeluaran')
                            ->schema([
                                TextInput::make('description')
                                    ->label('Keterangan')
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateTotalExpense($get, $set);
                                    }),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Pengeluaran')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateTotalExpense($get, $set);
                            }),
                    ]),
            ]);
    }

    protected static function calculateIncomeFixed(Get $get, Set $set): void
    {
        $amount = (float) $get('income_amount');
        $percentage = (float) $get('income_percentage');

        if ($amount < 0)
            $amount = 0;
        if ($percentage < 0)
            $percentage = 0;
        if ($percentage > 100)
            $percentage = 100; // Validation 0-100

        // Formula: Pemasukan - (Pemasukan x (persentase/100))
        $fixed = $amount - ($amount * ($percentage / 100));

        $set('income_fixed', round($fixed, 2));
    }

    protected static function calculateTotalExpense(Get $get, Set $set): void
    {
        // When inside repeater, we need to go up.
        // But simpler way: get the repeater state array.
        // If we are inside the repeater item, $get('../../expenseItems') works.
        // If we are on the repeater itself, $get('expenseItems') works.

        // Let's try to handle both or just rely on the path relative to the form.
        // Since we are in a static method context, we might not know where we are.
        // But the closure context has the right $get.

        // Strategy: Try to get 'expenseItems' directly (if we are at root)
        // or '../../expenseItems' (if we are in a field).

        $items = $get('expenseItems') ?? $get('../../expenseItems') ?? [];

        $total = collect($items)->sum(function ($item) {
            return (float) ($item['amount'] ?? 0);
        });

        // Set 'total_expense' (root level) or '../../total_expense'
        // If we found items at '../../expenseItems', then total_expense is at '../../total_expense'.

        if ($get('expenseItems') !== null) {
            $set('total_expense', $total);
        } else {
            $set('../../total_expense', $total);
        }
    }
}
