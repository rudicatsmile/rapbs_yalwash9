<?php

namespace App\Filament\Resources\FinancialRecords\Schemas;

use App\Models\Department;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
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
                        Hidden::make('wa_inactive_status_notified')
                            ->default(false)
                            ->dehydrated(false),
                        Toggle::make('status')
                            ->label(fn (Get $get) => (bool) $get('status') ? 'Sudah disetujui' : 'Belum disetujui')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true)
                            ->visible(fn () => auth()->user() && ! auth()->user()->hasRole('user'))
                            ->disabled(fn () => auth()->user() && auth()->user()->hasRole('user'))
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state, ?\Illuminate\Database\Eloquent\Model $record = null): void {
                                if ($record && $record->exists) {
                                    return;
                                }

                                $isActive = (bool) $state;

                                if ($isActive) {
                                    $set('wa_inactive_status_notified', false);

                                    return;
                                }

                                if ((bool) $get('wa_inactive_status_notified')) {
                                    return;
                                }

                                $departmentId = $get('department_id');

                                if (! $departmentId) {
                                    Log::warning('WhatsApp notification skipped: department not selected (status inactive)', [
                                        'user_id' => auth()->id(),
                                    ]);

                                    Notification::make()
                                        ->title('Departemen belum dipilih')
                                        ->body('Pilih departemen terlebih dahulu sebelum menonaktifkan status.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $department = Department::query()->find($departmentId);

                                if (! $department) {
                                    Log::warning('WhatsApp notification skipped: department not found (status inactive)', [
                                        'user_id' => auth()->id(),
                                        'department_id' => $departmentId,
                                    ]);

                                    Notification::make()
                                        ->title('Departemen tidak ditemukan')
                                        ->body('Departemen yang dipilih tidak valid.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $phone = (string) ($department->phone ?? '');
                                $waService = new WhatsAppService;

                                if (! $waService->isValidPhone($phone)) {
                                    Log::warning('WhatsApp notification skipped: invalid department phone (status inactive)', [
                                        'user_id' => auth()->id(),
                                        'department_id' => $department->id,
                                        'phone' => $phone,
                                    ]);

                                    Notification::make()
                                        ->title('Nomor WhatsApp departemen tidak valid')
                                        ->body('Periksa nomor telepon pada data departemen.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $recordDateState = $get('record_date');
                                $recordDateFormatted = '-';

                                if ($recordDateState) {
                                    try {
                                        $recordDateFormatted = Carbon::parse($recordDateState)->format('d-m-Y');
                                    } catch (\Throwable $e) {
                                        $recordDateFormatted = (string) $recordDateState;
                                    }
                                }

                                $monthNames = [
                                    1 => 'Januari',
                                    2 => 'Februari',
                                    3 => 'Maret',
                                    4 => 'April',
                                    5 => 'Mei',
                                    6 => 'Juni',
                                    7 => 'Juli',
                                    8 => 'Agustus',
                                    9 => 'September',
                                    10 => 'Oktober',
                                    11 => 'November',
                                    12 => 'Desember',
                                ];

                                $monthNumber = (int) ($get('month') ?? 0);
                                $monthLabel = $monthNames[$monthNumber] ?? '-';

                                $recordName = (string) ($get('record_name') ?: '-');
                                $incomeTotal = (float) self::parseMoney((string) ($get('income_total') ?? '0'));
                                $timestamp = now()->format('d-m-Y H:i');
                                $actorName = auth()->user()?->name ?? '-';

                                $message = "*Ef-Fin9 Sistem*\n\n"
                                    ."*Pengajuan RAPBS Belum disetujui*\n\n"
                                    ."Departemen: {$department->name}\n"
                                    ."Nama History: {$recordName}\n"
                                    ."Tanggal: {$recordDateFormatted}\n"
                                    ."Bulan: {$monthLabel}\n"
                                    .'Total Pemasukan: Rp '.number_format($incomeTotal, 0, ',', '.')."\n"
                                    ."Diubah oleh: {$actorName}\n"
                                    ."Waktu: {$timestamp}";

                                Log::info('Attempting WhatsApp notification (status inactive)', [
                                    'user_id' => auth()->id(),
                                    'department_id' => $department->id,
                                    'phone' => $waService->normalizePhone($phone),
                                    'record_name' => $recordName,
                                ]);

                                $success = $waService->sendMessage($phone, $message);

                                if ($success) {
                                    $set('wa_inactive_status_notified', true);

                                    Notification::make()
                                        ->title('Notifikasi WhatsApp terkirim')
                                        ->body("Departemen {$department->name} telah menerima pemberitahuan status tidak aktif.")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Gagal mengirim WhatsApp')
                                        ->body('Notifikasi alternatif ditampilkan. Silakan coba lagi atau periksa koneksi / token WhatsApp.')
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->columnSpanFull(),
                        Select::make('department_id')
                            ->relationship('department', 'name', modifyQueryUsing: function (Builder $query) {
                                $user = auth()->user();
                                if ($user && $user->hasRole('user') && ! $user->hasRole(['super_admin', 'admin'])) {
                                    $query->where('id', $user->department_id);
                                }

                                $query->orderBy('urut');
                            })
                            ->label('Departemen')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->user() && auth()->user()->hasRole('user') && ! auth()->user()->hasRole(['super_admin', 'admin']) ? auth()->user()->department_id : null)
                            ->disabled(fn () => auth()->user() && auth()->user()->hasRole('user') && ! auth()->user()->hasRole(['super_admin', 'admin']))
                            ->dehydrated(),
                        DatePicker::make('record_date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),
                        Select::make('month')
                            ->label('Bulan')
                            ->options([
                                '1' => 'Januari',
                                '2' => 'Februari',
                                '3' => 'Maret',
                                '4' => 'April',
                                '5' => 'Mei',
                                '6' => 'Juni',
                                '7' => 'Juli',
                                '8' => 'Agustus',
                                '9' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ])
                            ->default(fn () => (string) now()->month)
                            ->required()
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $allowed = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
                                if (! in_array((string) $state, $allowed, true)) {
                                    $set('month', (string) now()->month);
                                }
                            }),
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
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                self::calculateIncomeFixed($get, $set);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                            ])
                            ->columnSpanFull(),
                        TextInput::make('income_percentage')
                            ->label('Resiko tidak dibayar')
                            ->prefix('Rp')
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                self::calculateIncomeFixed($get, $set);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                            ])
                            ->columnSpanFull(),
                        TextInput::make('income_fixed')
                            ->label('Pemasukan (Rp) - Resiko tidak dibayar')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Rencana Pemasukan BOS dan Lainnya')
                    ->schema([
                        TextInput::make('income_bos')
                            ->label('Pemasukan BOS (Rp)')
                            ->placeholder('Masukkan rencana pemasukan BOS')
                            ->prefix('Rp')
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                self::calculateTotalIncome($get, $set);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                            ])
                            ->columnSpan(1),
                        TextInput::make('income_bos_other')
                            ->label('Pemasukan lainnya (Rp)')
                            ->placeholder('Masukkan rencana pemasukan lainnya')
                            ->prefix('Rp')
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                self::calculateTotalIncome($get, $set);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                            ])
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Total Pemasukan')
                    ->schema([
                        TextInput::make('income_total')
                            ->label('Total Pemasukan Keseluruhan')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->columnSpanFull(),
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
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state)),

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
                                        'md' => 6,
                                        'lg' => 6,
                                    ]),
                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->stripCharacters('.')
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        self::calculateTotalExpense($get, $set);
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                    ->extraInputAttributes([
                                        'inputmode' => 'numeric',
                                        'oninput' => "const el=this;let raw=el.value.replace(/\\D/g,'');if(!raw){el.value='';return;}let v=raw.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');el.value=v;el.setSelectionRange(v.length,v.length);",
                                    ])
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 6,
                                        'lg' => 6,
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
                            })
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['source_type'] = 'Mandiri';

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $data['source_type'] = 'Mandiri';

                                return $data;
                            }),
                    ])
                    ->columnSpanFull(),

                Section::make('Lampiran Financial Record')
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('financial_record_attachments')
                            ->label('Upload Lampiran')
                            ->collection('financial-record-attachments')
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
                            ->helperText('Format: PDF/DOC/DOCX/XLS/XLSX/JPG/PNG. Maks 10MB per file.')
                            ->preserveFilenames()
                            ->disk('public'),
                    ]),
            ]);
    }

    protected static function calculateIncomeFixed($get, $set): void
    {
        $amount = self::parseMoney($get('income_amount'));
        $risk = self::parseMoney($get('income_percentage'));

        if ($amount < 0) {
            $amount = 0;
        }
        if ($risk < 0) {
            $risk = 0;
        }
        if ($risk > $amount) {
            $risk = $amount;
        }

        $fixed = $amount - $risk;

        $set('income_fixed', number_format($fixed, 0, ',', '.'));
        self::calculateTotalIncome($get, $set);
    }

    protected static function calculateTotalIncome($get, $set): void
    {
        $incomeFixed = self::parseMoney($get('income_fixed'));
        $incomeBos = self::parseMoney($get('income_bos'));
        $incomeBosOther = self::parseMoney($get('income_bos_other'));

        if ($incomeFixed < 0) {
            $incomeFixed = 0;
        }
        if ($incomeBos < 0) {
            $incomeBos = 0;
        }
        if ($incomeBosOther < 0) {
            $incomeBosOther = 0;
        }

        $total = $incomeFixed + $incomeBos + $incomeBosOther;

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
