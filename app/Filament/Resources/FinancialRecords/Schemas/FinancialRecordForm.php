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
use Illuminate\Support\HtmlString;

class FinancialRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
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

                Section::make('Rencana Pemasukan Mandiri')
                    ->schema([
                        TextInput::make('income_amount')
                            ->label('Pemasukan (Rp)')
                            ->prefix('Rp')
                            ->default(0)
                            ->stripCharacters('.')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                // Standardize format to float
                                $floatValue = self::parseMoney($state);
                                $set('income_amount', number_format($floatValue, 0, ',', '.'));
                                self::calculateIncomeFixed($get, $set);
                            })
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'oninput' => "let v = this.value.replace(/[^0-9,]/g, ''); let p = v.split(','); p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.'); this.value = p.join(',');",
                            ])
                            ->columnSpanFull(),
                        TextInput::make('income_percentage')
                            ->label('Resiko tidak dibayar (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateIncomeFixed($get, $set);
                            })
                            ->columnSpanFull(),
                        TextInput::make('income_fixed')
                            ->label('Pemasukan Tetap (Rp)')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Rencana Pemasukan BOS')
                    ->schema([
                        TextInput::make('income_bos')
                            ->label('Pemasukan BOS (Rp)')
                            ->placeholder('Masukkan rencana pemasukan BOS')
                            ->prefix('Rp')
                            ->default(0)
                            ->stripCharacters('.')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $floatValue = self::parseMoney($state);
                                $set('income_bos', number_format($floatValue, 0, ',', '.'));
                                self::calculateTotalIncome($get, $set);
                            })
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'oninput' => "let v = this.value.replace(/[^0-9,]/g, ''); let p = v.split(','); p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.'); this.value = p.join(',');",
                            ])
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Total Pemasukan')
                    ->schema([
                        TextInput::make('income_total')
                            ->label('Total Pemasukan Keseluruhan (Fixed + BOS)')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->columnSpanFull()
                    ])->columns(1),

                Section::make('Rencana Pengeluaran')
                    ->schema([
                        Placeholder::make('horizontal_repeater_styles')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <style>
                                    @media (min-width: 768px) {
                                        .horizontal-repeater .fi-fo-repeater-item {
                                            display: flex !important;
                                            gap: 1rem;
                                            align-items: flex-start;
                                        }
                                        .horizontal-repeater .fi-fo-repeater-item-content {
                                            flex: 1;
                                            border: none !important;
                                            padding: 0 !important;
                                            order: 1;
                                        }
                                        .horizontal-repeater .fi-fo-repeater-item-header {
                                            width: auto !important;
                                            background: transparent !important;
                                            border: none !important;
                                            padding: 0 !important;
                                            margin-top: 2.3rem;
                                            order: 2;
                                        }
                                        .horizontal-repeater .fi-fo-repeater-item-header-label {
                                            display: none !important;
                                        }
                                    }
                                </style>
                            '))
                            ->columnSpanFull(),
                        TextInput::make('total_expense')
                            ->label('Total Rencana Pengeluaran')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state)),

                        Repeater::make('expenseItems')
                            ->relationship('expenseItems')
                            ->label('Daftar Pengeluaran')
                            ->extraAttributes(['class' => 'horizontal-repeater'])
                            ->itemLabel(null)
                            ->cloneable(false)
                            ->schema([
                                TextInput::make('description')
                                    ->label('Keterangan')
                                    ->required()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 5,
                                        'lg' => 5,
                                    ]),
                                Select::make('source_type')
                                    ->label('Sumber Dana')
                                    ->options([
                                        'Mandiri' => 'Mandiri',
                                        'BOS' => 'BOS',
                                    ])
                                    ->required()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 3,
                                        'lg' => 3,
                                    ]),
                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->stripCharacters('.')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        $floatValue = self::parseMoney($state);
                                        $set('amount', number_format($floatValue, 0, ',', '.'));
                                        self::calculateTotalExpense($get, $set);
                                    })
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'inputmode' => 'numeric',
                                        'oninput' => "let v = this.value.replace(/[^0-9,]/g, ''); let p = v.split(','); p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.'); this.value = p.join(',');",
                                    ])
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 4,
                                        'lg' => 4,
                                    ]),
                            ])
                            ->columns([
                                'default' => 12,
                            ])
                            ->reorderable(false)
                            ->collapsible(false)
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
        $amount = self::parseMoney($get('income_amount'));
        $percentage = (float) $get('income_percentage');

        if ($amount < 0)
            $amount = 0;
        if ($percentage < 0)
            $percentage = 0;
        if ($percentage > 100)
            $percentage = 100; // Validation 0-100

        // Formula: Pemasukan - (Pemasukan x (persentase/100))
        $fixed = $amount - ($amount * ($percentage / 100));

        $set('income_fixed', number_format($fixed, 0, ',', '.'));
        self::calculateTotalIncome($get, $set);
    }

    protected static function calculateTotalIncome($get, $set): void
    {
        $incomeFixed = self::parseMoney($get('income_fixed'));
        $incomeBos = self::parseMoney($get('income_bos'));

        $total = $incomeFixed + $incomeBos;

        $set('income_total', number_format($total, 0, ',', '.'));
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
            return self::parseMoney($item['amount'] ?? 0);
        });

        // Set 'total_expense' (root level) or '../../total_expense'
        // If we found items at '../../expenseItems', then total_expense is at '../../total_expense'.

        if ($get('expenseItems') !== null) {
            $set('total_expense', number_format($total, 0, ',', '.'));
        } else {
            $set('../../total_expense', number_format($total, 0, ',', '.'));
        }
    }

    protected static function parseMoney($value): float
    {
        if (empty($value)) {
            return 0;
        }

        // Remove thousands separators (dots)
        $cleanValue = str_replace('.', '', (string) $value);

        // Replace decimal separator (comma) with dot
        $cleanValue = str_replace(',', '.', $cleanValue);

        // Remove any other non-numeric characters (except dot and minus)
        $cleanValue = preg_replace('/[^0-9.\-]/', '', $cleanValue);

        return (float) $cleanValue;
    }
}
