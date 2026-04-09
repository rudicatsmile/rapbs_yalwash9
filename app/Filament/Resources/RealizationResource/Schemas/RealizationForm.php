<?php

namespace App\Filament\Resources\RealizationResource\Schemas;

use App\Models\ExpenseItem;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
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
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                        TextInput::make('income_percentage')
                            ->label('Resiko tidak dibayar')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                        TextInput::make('income_fixed')
                            ->label('Pemasukan (Rp) - Resiko tidak dibayar')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Rencana Pemasukan BOS dan Lainnya')
                    ->schema([
                        TextInput::make('income_bos')
                            ->label('Pemasukan BOS (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columns(1),
                        TextInput::make('income_bos_other')
                            ->label('Pemasukan lainnya (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columns(1),
                    ])->columns(2),

                Section::make('Total Pemasukan')
                    ->schema([
                        TextInput::make('income_total')
                            ->label('Total Pemasukan Keseluruhan')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Rencana Pengeluaran')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('rapbs_items_empty_warning')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (! $record || ! $record->exists) {
                                    return new HtmlString('');
                                }

                                $count = ExpenseItem::query()
                                    ->where('financial_record_id', $record->id)
                                    ->count();

                                if ($count > 0) {
                                    return new HtmlString('');
                                }

                                return new HtmlString('<div style="color:#dc2626;font-size:12px;">Data RAPBS (Daftar Pengeluaran) kosong atau belum tersedia untuk record ini.</div>');
                            })
                            ->columnSpanFull(),
                        Placeholder::make('realization_expense_repeater_styles')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <style>
                                    @media (min-width: 768px) {
                                        .realization-expense-repeater .fi-fo-repeater-item {
                                            align-items: flex-start;
                                            gap: 1rem;
                                        }

                                        .realization-expense-repeater .fi-fo-repeater-item-content {
                                            padding-top: 0 !important;
                                        }
                                    }

                                    .realization-expense-repeater .fi-fo-repeater-add {
                                        margin-top: 0.75rem;
                                    }

                                    .realization-expense-repeater .fi-fo-repeater-add .realization-add-btn {
                                        width: 100%;
                                        justify-content: center;
                                        border-radius: 0.85rem;
                                        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
                                        border: 1px solid rgba(255, 255, 255, 0.18);
                                        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.28), 0 2px 10px rgba(0, 0, 0, 0.08);
                                        color: #ffffff;
                                        font-weight: 700;
                                        letter-spacing: 0.2px;
                                        transition: transform 140ms ease, box-shadow 180ms ease, filter 180ms ease;
                                    }

                                    .realization-expense-repeater .fi-fo-repeater-add .realization-add-btn:hover {
                                        transform: translateY(-1px);
                                        box-shadow: 0 18px 36px rgba(37, 99, 235, 0.34), 0 3px 12px rgba(0, 0, 0, 0.10);
                                        filter: saturate(1.08);
                                    }

                                    .realization-expense-repeater .fi-fo-repeater-add .realization-add-btn:active {
                                        transform: translateY(0);
                                    }

                                    .realization-expense-repeater .fi-fo-repeater-add .realization-add-btn:focus-visible {
                                        outline: 2px solid rgba(59, 130, 246, 0.55);
                                        outline-offset: 2px;
                                    }
                                </style>
                            '))
                            ->columnSpanFull(),
                        Placeholder::make('realization_expense_draft_persist')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (! $record || ! $record->exists) {
                                    return new HtmlString('');
                                }

                                $recordId = (int) $record->id;

                                return new HtmlString('
                                    <script>
                                        (function () {
                                            const recordId = '.$recordId.';
                                            const key = "realization:expenseItems:draft:" + recordId;

                                            function findComponent() {
                                                const root = document.querySelector("[wire\\\\:id]");
                                                if (!root || !window.Livewire) return null;
                                                const id = root.getAttribute("wire:id");
                                                return window.Livewire.find(id);
                                            }

                                            function saveDraft() {
                                                const component = findComponent();
                                                if (!component) return;
                                                try {
                                                    const items = component.get("data.expenseItems");
                                                    localStorage.setItem(key, JSON.stringify(items ?? []));
                                                } catch (e) {}
                                            }

                                            function restoreDraftIfEmpty() {
                                                const component = findComponent();
                                                if (!component) return;
                                                const current = component.get("data.expenseItems");
                                                if (Array.isArray(current) && current.length) return;
                                                const raw = localStorage.getItem(key);
                                                if (!raw) return;
                                                try {
                                                    const items = JSON.parse(raw);
                                                    if (!Array.isArray(items)) return;
                                                    component.set("data.expenseItems", items);
                                                } catch (e) {}
                                            }

                                            window.addEventListener("load", function () {
                                                restoreDraftIfEmpty();
                                                const container = document.querySelector(".realization-expense-repeater");
                                                if (!container) return;
                                                container.addEventListener("input", function () { queueMicrotask(saveDraft); }, true);
                                                container.addEventListener("change", function () { queueMicrotask(saveDraft); }, true);
                                                container.addEventListener("click", function () { queueMicrotask(saveDraft); }, true);
                                            });
                                        })();
                                    </script>
                                ');
                            })
                            ->columnSpanFull(),
                        Repeater::make('expenseItems')
                            ->extraAttributes(['class' => 'realization-expense-repeater'])
                            ->label('Daftar Pengeluaran & Realisasi')
                            ->itemLabel(null)
                            ->addable()
                            ->deletable()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->minItems(1)
                            ->addActionLabel('Tambah Pengeluaran')
                            ->addAction(
                                fn (Action $action) => $action
                                    ->label('Tambah Pengeluaran')
                                    ->icon('heroicon-m-plus')
                                    ->color('info')
                                    ->size('lg')
                                    ->extraAttributes(['class' => 'realization-add-btn'])
                            )
                            ->schema([
                                Textarea::make('description')
                                    ->label('Keterangan')
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(1000)
                                    ->rows(2)
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 3,
                                    ]),
                                Select::make('expense_item_id')
                                    ->label('Sumber')
                                    ->required()
                                    ->placeholder('– Pilih Sumber Anggaran –')
                                    ->preload()
                                    ->in(function ($livewire): array {
                                        $record = $livewire->getRecord();

                                        return ExpenseItem::query()
                                            ->where('financial_record_id', $record->id)
                                            ->pluck('id')
                                            ->map(fn ($id) => (string) $id)
                                            ->all();
                                    })
                                    ->getOptionLabelUsing(function ($value, $livewire): ?string {
                                        if (! $value) {
                                            return null;
                                        }

                                        $record = $livewire->getRecord();
                                        $item = ExpenseItem::query()
                                            ->where('financial_record_id', $record->id)
                                            ->whereKey($value)
                                            ->first();

                                        if (! $item) {
                                            return null;
                                        }

                                        $amount = (float) ($item->amount ?? 0);
                                        $realisasi = (float) ($item->realisasi ?? 0);
                                        $available = $amount - $realisasi;

                                        return "{$item->description} • Anggaran: ".number_format($amount, 0, ',', '.').' • Tersedia: '.number_format($available, 0, ',', '.');
                                    })
                                    ->options(function ($livewire): array {
                                        $record = $livewire->getRecord();

                                        return ExpenseItem::query()
                                            ->where('financial_record_id', $record->id)
                                            ->orderBy('id')
                                            ->get()
                                            ->mapWithKeys(function (ExpenseItem $item) {
                                                $amount = (float) ($item->amount ?? 0);
                                                $realisasi = (float) ($item->realisasi ?? 0);
                                                $available = $amount - $realisasi;

                                                $label = "{$item->description} • Anggaran: ".number_format($amount, 0, ',', '.').' • Tersedia: '.number_format($available, 0, ',', '.');

                                                return [
                                                    (string) $item->id => $label,
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state, $livewire): void {
                                        $record = $livewire->getRecord();

                                        if (! $state) {
                                            $set('amount', null);
                                            $set('saldo', null);

                                            return;
                                        }

                                        $expenseItem = ExpenseItem::query()
                                            ->where('financial_record_id', $record->id)
                                            ->whereKey($state)
                                            ->first();

                                        if (! $expenseItem) {
                                            Notification::make()
                                                ->title('Sumber tidak valid')
                                                ->body('Sumber anggaran tidak ditemukan pada RAPBS.')
                                                ->danger()
                                                ->send();

                                            $set('expense_item_id', null);
                                            $set('amount', null);
                                            $set('saldo', null);

                                            return;
                                        }

                                        $budget = (float) ($expenseItem->amount ?? 0);
                                        $alreadyAllocated = (float) ($expenseItem->allocated_amount ?? 0);
                                        $remainingAllocation = max(0, $budget - $alreadyAllocated);

                                        $realisasi = (float) self::parseMoney($get('realisasi'));
                                        $saldo = $remainingAllocation - $realisasi;

                                        if (! $get('description')) {
                                            $set('description', (string) ($expenseItem->description ?? ''));
                                        }

                                        $set('amount', number_format($remainingAllocation, 0, ',', '.'));
                                        $set('saldo', number_format($saldo, 0, ',', '.'));

                                        $items = $get('../../expenseItems') ?? [];
                                        $items = array_values(is_array($items) ? $items : []);

                                        $sourceIds = array_map(
                                            fn ($item) => (string) ($item['expense_item_id'] ?? ''),
                                            $items
                                        );
                                        $sourceIds = array_filter($sourceIds, fn ($id) => $id !== '');
                                        $sourceCounts = array_count_values($sourceIds);

                                        $seenSources = [];
                                        $budgets = [];
                                        $remaining = [];
                                        $totalExpense = 0.0;
                                        $totalRealization = 0.0;

                                        foreach ($items as $index => $item) {
                                            $sourceId = (string) ($item['expense_item_id'] ?? '');
                                            $itemAmount = (float) self::parseMoney($item['amount'] ?? 0);
                                            $itemRealisasi = (float) self::parseMoney($item['realisasi'] ?? 0);

                                            $totalRealization += $itemRealisasi;

                                            if ($sourceId !== '' && ($sourceCounts[$sourceId] ?? 0) > 1) {
                                                if (! array_key_exists($sourceId, $budgets)) {
                                                    $budgets[$sourceId] = $itemAmount;
                                                    $saldoRow = $itemAmount - $itemRealisasi;
                                                    $remaining[$sourceId] = $saldoRow;
                                                    $items[$index]['saldo'] = number_format($saldoRow, 0, ',', '.');
                                                } else {
                                                    $displayAmount = max(0, (float) ($remaining[$sourceId] ?? 0));
                                                    $items[$index]['amount'] = number_format($displayAmount, 0, ',', '.');
                                                    $saldoRow = $displayAmount - $itemRealisasi;
                                                    $remaining[$sourceId] = $saldoRow;
                                                    $items[$index]['saldo'] = number_format($saldoRow, 0, ',', '.');
                                                }
                                            } else {
                                                $items[$index]['saldo'] = number_format($itemAmount - $itemRealisasi, 0, ',', '.');
                                            }

                                            if ($sourceId === '') {
                                                $totalExpense += $itemAmount;
                                            } elseif (! in_array($sourceId, $seenSources, true)) {
                                                $totalExpense += $itemAmount;
                                                $seenSources[] = $sourceId;
                                            }
                                        }

                                        $totalBalance = $totalExpense - $totalRealization;

                                        $set('../../expenseItems', $items);
                                        $set('../../total_expense', number_format($totalExpense, 0, ',', '.'));
                                        $set('../../total_realization', number_format($totalRealization, 0, ',', '.'));
                                        $set('../../total_balance', number_format($totalBalance, 0, ',', '.'));

                                        Log::info('Realization source selected', [
                                            'realization_id' => $record->id,
                                            'expense_item_id' => (int) $expenseItem->id,
                                            'user_id' => auth()->id(),
                                        ]);
                                    })
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 2,
                                    ]),
                                TextInput::make('amount')
                                    ->label('Anggaran')
                                    ->disabled()
                                    ->dehydrated()
                                    ->stripCharacters('.')
                                    ->required()
                                    ->formatStateUsing(fn ($state) => blank($state) ? '' : number_format((float) self::parseMoney($state), 0, ',', '.'))
                                    ->rule(['integer', 'min:0', 'max:2000000000'])
                                    ->hint(function (TextInput $component) {
                                        $target = $component->getStatePath();

                                        return new HtmlString('<span wire:loading wire:target="'.e($target).'" style="font-size:12px;opacity:.75;">Menghitung...</span>');
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        $items = $get('../../expenseItems') ?? [];
                                        $items = array_values(is_array($items) ? $items : []);

                                        $sourceIds = array_map(
                                            fn ($item) => (string) ($item['expense_item_id'] ?? ''),
                                            $items
                                        );
                                        $sourceIds = array_filter($sourceIds, fn ($id) => $id !== '');
                                        $sourceCounts = array_count_values($sourceIds);

                                        $seenSources = [];
                                        $budgets = [];
                                        $remaining = [];
                                        $firstInsufficient = null;

                                        $totalExpense = 0.0;
                                        $totalRealization = 0.0;

                                        foreach ($items as $index => $item) {
                                            $sourceId = (string) ($item['expense_item_id'] ?? '');
                                            $itemAmount = (float) self::parseMoney($item['amount'] ?? 0);
                                            $itemRealisasi = (float) self::parseMoney($item['realisasi'] ?? 0);

                                            $totalRealization += $itemRealisasi;

                                            if ($sourceId !== '' && ($sourceCounts[$sourceId] ?? 0) > 1) {
                                                if (! array_key_exists($sourceId, $budgets)) {
                                                    $budgets[$sourceId] = $itemAmount;
                                                    $availableBefore = $itemAmount;
                                                    $saldoRow = $itemAmount - $itemRealisasi;
                                                    $remaining[$sourceId] = $saldoRow;
                                                    $items[$index]['saldo'] = number_format($saldoRow, 0, ',', '.');
                                                } else {
                                                    $displayAmount = max(0, (float) ($remaining[$sourceId] ?? 0));
                                                    $items[$index]['amount'] = number_format($displayAmount, 0, ',', '.');
                                                    $availableBefore = $displayAmount;
                                                    $saldoRow = $displayAmount - $itemRealisasi;
                                                    $remaining[$sourceId] = $saldoRow;
                                                    $items[$index]['saldo'] = number_format($saldoRow, 0, ',', '.');
                                                }

                                                if ($firstInsufficient === null && $itemRealisasi > $availableBefore) {
                                                    $firstInsufficient = [
                                                        'index' => $index,
                                                        'source_id' => $sourceId,
                                                        'available' => $availableBefore,
                                                        'realisasi' => $itemRealisasi,
                                                    ];
                                                }
                                            } else {
                                                $items[$index]['saldo'] = number_format($itemAmount - $itemRealisasi, 0, ',', '.');
                                            }

                                            if ($sourceId === '') {
                                                $totalExpense += $itemAmount;
                                            } elseif (! in_array($sourceId, $seenSources, true)) {
                                                $totalExpense += $itemAmount;
                                                $seenSources[] = $sourceId;
                                            }
                                        }

                                        $totalBalance = $totalExpense - $totalRealization;

                                        $set('../../expenseItems', $items);
                                        $set('../../total_expense', number_format($totalExpense, 0, ',', '.'));
                                        $set('../../total_realization', number_format($totalRealization, 0, ',', '.'));
                                        $set('../../total_balance', number_format($totalBalance, 0, ',', '.'));

                                        if ($firstInsufficient !== null) {
                                            Notification::make()
                                                ->title('Saldo sumber tidak cukup')
                                                ->body('Terdeteksi duplikasi sumber. Sisa saldo sebelum baris ke-'.($firstInsufficient['index'] + 1).' adalah Rp '.number_format((float) $firstInsufficient['available'], 0, ',', '.').', namun realisasi pada baris tersebut Rp '.number_format((float) $firstInsufficient['realisasi'], 0, ',', '.').'.')
                                                ->warning()
                                                ->send();
                                        }
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'inputmode' => 'numeric',
                                        'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                                    ])
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 2,
                                    ]),
                                TextInput::make('realisasi')
                                    ->label('Realisasi')
                                    ->stripCharacters('.')
                                    ->required()
                                    ->formatStateUsing(fn ($state) => blank($state) ? '' : number_format((float) self::parseMoney($state), 0, ',', '.'))
                                    ->rule(['integer', 'min:0', 'max:2000000000'])
                                    ->rule(function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get): void {
                                            if (! preg_match('/expenseItems\.(\d+)\.realisasi$/', $attribute, $matches)) {
                                                return;
                                            }

                                            $index = (int) $matches[1];
                                            $items = $get('../../expenseItems') ?? [];
                                            $items = array_values(is_array($items) ? $items : []);

                                            $sourceIds = array_map(
                                                fn ($item) => (string) ($item['expense_item_id'] ?? ''),
                                                $items
                                            );
                                            $sourceIds = array_filter($sourceIds, fn ($id) => $id !== '');
                                            $sourceCounts = array_count_values($sourceIds);

                                            $sourceId = (string) ($items[$index]['expense_item_id'] ?? '');

                                            if ($sourceId === '' || ($sourceCounts[$sourceId] ?? 0) < 2) {
                                                return;
                                            }

                                            $runningRealisasiBefore = 0.0;
                                            $budget = null;

                                            for ($i = 0; $i < $index; $i++) {
                                                $row = $items[$i] ?? [];

                                                if ((string) ($row['expense_item_id'] ?? '') !== $sourceId) {
                                                    continue;
                                                }

                                                $runningRealisasiBefore += (float) self::parseMoney($row['realisasi'] ?? 0);

                                                if ($budget === null) {
                                                    $budget = (float) self::parseMoney($row['amount'] ?? 0);
                                                }
                                            }

                                            if ($budget === null) {
                                                $budget = (float) self::parseMoney($items[$index]['amount'] ?? 0);
                                            }

                                            $realisasiNow = (float) self::parseMoney($value);
                                            $availableBefore = $budget - $runningRealisasiBefore;

                                            if ($realisasiNow > $availableBefore) {
                                                $fail('Saldo sumber tidak cukup. Sisa saldo sebelum baris ini Rp '.number_format($availableBefore, 0, ',', '.').', realisasi yang dimasukkan Rp '.number_format($realisasiNow, 0, ',', '.').'.');
                                            }
                                        };
                                    })
                                    ->hint(function (TextInput $component) {
                                        $target = $component->getStatePath();

                                        return new HtmlString('<span wire:loading wire:target="'.e($target).'" style="font-size:12px;opacity:.75;">Menghitung...</span>');
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        $items = $get('../../expenseItems') ?? [];
                                        $items = array_values(is_array($items) ? $items : []);

                                        $sourceIds = array_map(
                                            fn ($item) => (string) ($item['expense_item_id'] ?? ''),
                                            $items
                                        );
                                        $sourceIds = array_filter($sourceIds, fn ($id) => $id !== '');
                                        $sourceCounts = array_count_values($sourceIds);

                                        $seenSources = [];
                                        $budgets = [];
                                        $remaining = [];
                                        $firstInsufficient = null;

                                        $totalExpense = 0.0;
                                        $totalRealization = 0.0;

                                        foreach ($items as $index => $item) {
                                            $sourceId = (string) ($item['expense_item_id'] ?? '');
                                            $itemAmount = (float) self::parseMoney($item['amount'] ?? 0);
                                            $itemRealisasi = (float) self::parseMoney($item['realisasi'] ?? 0);

                                            $totalRealization += $itemRealisasi;

                                            if ($sourceId !== '' && ($sourceCounts[$sourceId] ?? 0) > 1) {
                                                if (! array_key_exists($sourceId, $budgets)) {
                                                    $budgets[$sourceId] = $itemAmount;
                                                    $availableBefore = $itemAmount;
                                                    $saldoRow = $itemAmount - $itemRealisasi;
                                                    $remaining[$sourceId] = $saldoRow;
                                                    $items[$index]['saldo'] = number_format($saldoRow, 0, ',', '.');
                                                } else {
                                                    $displayAmount = max(0, (float) ($remaining[$sourceId] ?? 0));
                                                    $items[$index]['amount'] = number_format($displayAmount, 0, ',', '.');
                                                    $availableBefore = $displayAmount;
                                                    $saldoRow = $displayAmount - $itemRealisasi;
                                                    $remaining[$sourceId] = $saldoRow;
                                                    $items[$index]['saldo'] = number_format($saldoRow, 0, ',', '.');
                                                }

                                                if ($firstInsufficient === null && $itemRealisasi > $availableBefore) {
                                                    $firstInsufficient = [
                                                        'index' => $index,
                                                        'source_id' => $sourceId,
                                                        'available' => $availableBefore,
                                                        'realisasi' => $itemRealisasi,
                                                    ];
                                                }
                                            } else {
                                                $items[$index]['saldo'] = number_format($itemAmount - $itemRealisasi, 0, ',', '.');
                                            }

                                            if ($sourceId === '') {
                                                $totalExpense += $itemAmount;
                                            } elseif (! in_array($sourceId, $seenSources, true)) {
                                                $totalExpense += $itemAmount;
                                                $seenSources[] = $sourceId;
                                            }
                                        }

                                        $totalBalance = $totalExpense - $totalRealization;

                                        $set('../../expenseItems', $items);
                                        $set('../../total_expense', number_format($totalExpense, 0, ',', '.'));
                                        $set('../../total_realization', number_format($totalRealization, 0, ',', '.'));
                                        $set('../../total_balance', number_format($totalBalance, 0, ',', '.'));

                                        if ($firstInsufficient !== null) {
                                            Notification::make()
                                                ->title('Saldo sumber tidak cukup')
                                                ->body('Terdeteksi duplikasi sumber. Sisa saldo sebelum baris ke-'.($firstInsufficient['index'] + 1).' adalah Rp '.number_format((float) $firstInsufficient['available'], 0, ',', '.').', namun realisasi pada baris tersebut Rp '.number_format((float) $firstInsufficient['realisasi'], 0, ',', '.').'.')
                                                ->warning()
                                                ->send();
                                        }
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'inputmode' => 'numeric',
                                        'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                                    ])
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 2,
                                    ]),
                                TextInput::make('saldo')
                                    ->label('Saldo')
                                    ->readOnly()
                                    ->dehydrated() // Must be saved
                                    ->formatStateUsing(fn ($state) => blank($state) ? '' : number_format((float) self::parseMoney($state), 0, ',', '.'))
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 3,
                                    ]),
                                Placeholder::make('source_item_detail')
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $id = $get('expense_item_id');

                                        if (! $id) {
                                            return new HtmlString('');
                                        }

                                        $item = ExpenseItem::query()->find($id);

                                        if (! $item) {
                                            return new HtmlString('<div style="color:#dc2626;font-size:12px;margin-top:6px;">Detail sumber tidak ditemukan.</div>');
                                        }

                                        $amount = (float) ($item->amount ?? 0);
                                        $realisasi = (float) ($item->realisasi ?? 0);
                                        $available = $amount - $realisasi;

                                        $html = '<div style="font-size:12px;opacity:.85;margin-top:6px;">';
                                        $html .= '<div><strong>Nama Akun</strong>: '.e((string) $item->description).'</div>';
                                        $html .= '<div><strong>Anggaran</strong>: '.e(number_format($amount, 0, ',', '.')).'</div>';
                                        $html .= '<div><strong>Tersedia</strong>: '.e(number_format($available, 0, ',', '.')).'</div>';
                                        $html .= '</div>';

                                        return new HtmlString($html);
                                    })
                                    ->columnSpan([
                                        'default' => 12,
                                    ]),
                                Placeholder::make('realisasi_over_budget_warning')
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $amount = (float) self::parseMoney($get('amount'));
                                        $realisasi = (float) self::parseMoney($get('realisasi'));

                                        if ($amount > 0 && $realisasi > $amount) {
                                            return new HtmlString('<div style="color:#dc2626;font-size:12px;margin-top:6px;">Realisasi melebihi anggaran!</div>');
                                        }

                                        return new HtmlString('');
                                    })
                                    ->columnSpan([
                                        'default' => 12,
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
                                    ->formatStateUsing(fn ($state) => blank($state) ? '' : number_format((float) self::parseMoney($state), 0, ',', '.'))
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'style' => 'font-weight: bold',
                                        'title' => 'Total penjumlahan dari seluruh anggaran item pengeluaran',
                                    ]),
                                TextInput::make('total_realization')
                                    ->label('Total Realisasi')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => blank($state) ? '' : number_format((float) self::parseMoney($state), 0, ',', '.'))
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'style' => 'font-weight: bold',
                                        'title' => 'Total akumulasi dari realisasi yang telah diinput',
                                    ]),
                                TextInput::make('total_balance')
                                    ->label('Total Saldo')
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => blank($state) ? '' : number_format((float) self::parseMoney($state), 0, ',', '.'))
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'style' => 'font-weight: bold',
                                        'title' => 'Selisih antara Total Anggaran dikurangi Total Realisasi',
                                    ]),
                            ]),
                    ]),

                Section::make('Persetujuan Bendahara')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record && $record->exists)
                    ->schema([
                        Toggle::make('is_approved_by_bendahara')
                            ->label('Disetujui oleh Bendahara')
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-check-badge')
                            ->offIcon('heroicon-m-x-circle')
                            ->inline(false)
                            ->disabled(fn () => ! auth()->user()->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor', 'bendahara']))
                            ->dehydrated()
                            ->helperText('Aktifkan untuk menyetujui realisasi ini. Hanya Bendahara, Admin, dan Editor yang dapat mengubah status ini.'),
                    ]),

                Section::make('Lampiran Realisasi')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record && $record->exists)
                    ->schema([
                        Toggle::make('status_realisasi')
                            ->label('Status Siap Pelaporan')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->default(false)
                            ->hint('Tandai realisasi siap dilaporkan setelah data realisasi lengkap.')
                            ->hintIcon('heroicon-m-question-mark-circle')
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('realization_attachments')
                            ->label('Upload Lampiran Realisasi')
                            ->collection('realization-attachments')
                            ->multiple()
                            ->enableDownload()
                            ->enableOpen()
                            ->reorderable()
                            ->maxSize(10240)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'application/zip',
                            ])
                            ->helperText('Unggah dan lihat dokumen pendukung realisasi. Tersedia untuk semua role.')
                            ->preserveFilenames()
                            ->disk('public'),
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
