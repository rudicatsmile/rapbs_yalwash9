<?php

namespace App\Filament\Resources\RealizationResource\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
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

class RealizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Header')
                    ->schema([
                        Toggle::make('status_realisasi')
                            ->label('Status Pelaporan')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false)
                            ->visible(fn() => auth()->user() && !auth()->user()->hasRole('user'))
                            ->disabled(fn() => auth()->user() && auth()->user()->hasRole('user'))
                            ->columnSpanFull(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->label('Departemen')
                            ->disabled()
                            ->dehydrated(),
                        DatePicker::make('record_date')
                            ->label('Tanggal')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('record_name')
                            ->label('Nama History')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Rencana Pemasukan Mandiri')
                    ->schema([
                        TextInput::make('income_amount')
                            ->label('Pemasukan (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                        TextInput::make('income_percentage')
                            ->label('Resiko tidak dibayar (%)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                        TextInput::make('income_fixed')
                            ->label('Pemasukan Tetap (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Rencana Pemasukan BOS')
                    ->schema([
                        TextInput::make('income_bos')
                            ->label('Pemasukan BOS (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Total Pemasukan')
                    ->schema([
                        TextInput::make('income_total')
                            ->label('Total Pemasukan Keseluruhan (Fixed + BOS)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                            ->columnSpanFull()
                    ])->columns(1),

                Section::make('Rencana Pengeluaran')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('expenseItems')
                            ->relationship('expenseItems')
                            ->label('Daftar Pengeluaran & Realisasi')
                            ->itemLabel(null)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                TextInput::make('description')
                                    ->label('Keterangan')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 3,
                                    ]),
                                Select::make('source_type')
                                    ->label('Sumber')
                                    ->options([
                                        'Mandiri' => 'Mandiri',
                                        'BOS' => 'BOS',
                                    ])
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 2,
                                    ]),
                                TextInput::make('amount')
                                    ->label('Anggaran')
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 2,
                                    ]),
                                TextInput::make('realisasi')
                                    ->label('Realisasi')
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->stripCharacters('.')
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        $realisasi = self::parseMoney($state);
                                        $amount = self::parseMoney($get('amount'));

                                        $set('realisasi', number_format($realisasi, 0, ',', '.'));

                                        $saldo = $amount - $realisasi;
                                        $set('saldo', number_format($saldo, 0, ',', '.'));

                                        // Recalculate Totals
                                        $items = $get('../../expenseItems');
                                        $totalExpense = 0;
                                        $totalRealization = 0;
                                        $totalBalance = 0;

                                        foreach ($items as $item) {
                                            $itemAmount = self::parseMoney($item['amount'] ?? 0);
                                            $itemRealisasi = self::parseMoney($item['realisasi'] ?? 0);
                                            // Recalculate saldo locally to ensure consistency
                                            $itemSaldo = $itemAmount - $itemRealisasi;

                                            $totalExpense += $itemAmount;
                                            $totalRealization += $itemRealisasi;
                                            $totalBalance += $itemSaldo;
                                        }

                                        $set('../../total_expense', number_format($totalExpense, 0, ',', '.'));
                                        $set('../../total_realization', number_format($totalRealization, 0, ',', '.'));
                                        $set('../../total_balance', number_format($totalBalance, 0, ',', '.'));
                                    })
                                    ->rule(function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $realisasi = self::parseMoney($value);
                                            $amount = self::parseMoney($get('amount'));
                                            if ($realisasi > $amount) {
                                                $fail("Realisasi tidak boleh melebihi jumlah anggaran.");
                                            }
                                            if ($realisasi < 0) {
                                                $fail("Realisasi tidak boleh negatif.");
                                            }
                                        };
                                    })
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'inputmode' => 'numeric',
                                        'oninput' => "let v = this.value.replace(/[^0-9,]/g, ''); let p = v.split(','); p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.'); this.value = p.join(',');",
                                    ])
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 2,
                                    ]),
                                TextInput::make('saldo')
                                    ->label('Saldo')
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated() // Must be saved
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 3,
                                    ]),
                            ])
                            ->columns([
                                'default' => 12,
                            ]),

                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                TextInput::make('total_expense')
                                    ->label('Total Anggaran')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'style' => 'font-weight: bold',
                                        'title' => 'Total penjumlahan dari seluruh anggaran item pengeluaran'
                                    ]),
                                TextInput::make('total_realization')
                                    ->label('Total Realisasi')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'style' => 'font-weight: bold',
                                        'title' => 'Total akumulasi dari realisasi yang telah diinput'
                                    ]),
                                TextInput::make('total_balance')
                                    ->label('Total Saldo')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                                    ->dehydrateStateUsing(fn($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'style' => 'font-weight: bold',
                                        'title' => 'Selisih antara Total Anggaran dikurangi Total Realisasi'
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected static function parseMoney($value): string
    {
        if (empty($value)) {
            return '0';
        }
        $cleanValue = str_replace('.', '', (string) $value);
        $cleanValue = str_replace(',', '.', $cleanValue);
        $cleanValue = preg_replace('/[^0-9.\-]/', '', $cleanValue);
        return $cleanValue;
    }
}
